<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin > Attendance.
 *   - Coaches tab:  the admin records each coach's Time In / Time Out.
 *   - Students tab: read-only view of what coaches logged for students.
 *
 * Both live in tbl_attendances:
 *   attendance_id, user_id, role_id, attendance_at (DATE), time_in (TIME),
 *   time_out (TIME), attendance_state (varchar), logged_by (int), enrollment_id.
 * Coach rows have role_id = 2 and no enrollment_id; student rows point to
 * their tbl_enrollments row through enrollment_id.
 *
 * Shared table/column names (users, status, courses, coach_courses) come from
 * CoachController's constants so there is one place to change them.
 */
class AdminAttendanceController extends Controller
{
    public const ATT_TABLE = 'tbl_attendances';
    public const ENROLLMENTS_TABLE = 'tbl_enrollments';

    /**
     * ASSUMED: value stored in attendance_state when the admin records a
     * coach's Time In. Change it to whatever your coach/student logging uses.
     */
    public const STATE_PRESENT = 'Present';

    private function denyUnlessAdmin(Request $request): ?JsonResponse
    {
        return CoachController::userIsAdmin($request->user())
            ? null
            : response()->json(['success' => false, 'message' => 'You are not allowed to do that.'], 403);
    }

    /* =========================================================
     * GET /admin/attendance/data?tab=coaches|students&date=YYYY-MM-DD&course_id=
     * ========================================================= */
    public function data(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $v = $request->validate([
            'tab' => ['required', Rule::in(['coaches', 'students'])],
            'date' => ['required', 'date_format:Y-m-d'],
            'course_id' => ['nullable', 'integer'],
        ]);

        $courseId = isset($v['course_id']) ? (int) $v['course_id'] : null;

        $courses = DB::table(CoachController::COURSES_TABLE)
            ->orderBy(CoachController::COURSE_ID_COL)
            ->get([CoachController::COURSE_ID_COL . ' as id', CoachController::COURSE_NAME_COL . ' as name'])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
            ->values();

        $rows = $v['tab'] === 'coaches'
            ? $this->coachRows($v['date'], $courseId)
            : $this->studentRows($v['date'], $courseId);

        return response()->json(['success' => true, 'courses' => $courses, 'rows' => $rows]);
    }

    /* =========================================================
     * Coaches: Active coaches (optionally only those assigned to a course)
     * + their attendance row for the chosen date.
     * ========================================================= */
    private function coachRows(string $date, ?int $courseId): array
    {
        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        $query = DB::table("$users as u")
            ->join('tbl_user_profile as p', 'p.user_id', '=', "u.$pk")
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('u.' . CoachController::ROLE_COL, CoachController::ROLE_COACH)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active');

        if ($courseId) {
            $query->whereExists(function ($sub) use ($pk, $courseId) {
                $sub->selectRaw('1')
                    ->from(CoachController::COACH_COURSES_TABLE . ' as cc')
                    ->whereColumn('cc.coach_id', "u.$pk")
                    ->where('cc.course_id', $courseId);
            });
        }

        $coaches = $query->orderBy('p.last_name')->orderBy('p.first_name')->get([
            "u.$pk as id",
            'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix',
        ]);

        if ($coaches->isEmpty()) {
            return [];
        }

        $ids = $coaches->pluck('id');

        $assigned = DB::table(CoachController::COACH_COURSES_TABLE . ' as cc')
            ->join(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'cc.course_id')
            ->whereIn('cc.coach_id', $ids)
            ->orderBy('c.' . CoachController::COURSE_ID_COL)
            ->get(['cc.coach_id', 'c.' . CoachController::COURSE_NAME_COL . ' as name'])
            ->groupBy('coach_id');

        $logs = DB::table(self::ATT_TABLE)
            ->whereIn('user_id', $ids)
            ->where('role_id', CoachController::ROLE_COACH)
            ->where('attendance_at', $date)
            ->orderByRaw('time_in IS NULL, time_in')
            ->get(['user_id', 'time_in', 'time_out', 'attendance_state'])
            ->groupBy('user_id');

        return $coaches->map(function ($c) use ($assigned, $logs) {
            $log = ($logs->get($c->id) ?? collect())->first();

            return [
                'id' => (int) $c->id,
                'name' => $this->formatName($c),
                'classes' => ($assigned->get($c->id) ?? collect())->pluck('name')->implode(', '),
                'time_in' => $this->hhmm($log->time_in ?? null),
                'time_out' => $this->hhmm($log->time_out ?? null),
                'state' => $log->attendance_state ?? null,
            ];
        })->values()->all();
    }

    /* =========================================================
     * Students (read-only): ACTIVE ENROLLMENTS (tbl_enrollments, not yet
     * graduated) of Active users, with what was logged against each
     * enrollment on the chosen date. One row per enrollment.
     *
     *  - Course is read live from the enrollment, so changing an enrollment's
     *    course never requires touching the attendance logs.
     *  - "Sessions completed" = attended sessions (rows with a time_in) logged
     *    against the enrollment_id, out of the course's session count.
     *  - "Coach" is whoever logged the attendance (logged_by).
     * ========================================================= */
    private function studentRows(string $date, ?int $courseId): array
    {
        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        $query = DB::table(self::ENROLLMENTS_TABLE . ' as e')
            ->join("$users as u", "u.$pk", '=', 'e.user_id')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->whereNull('e.graduation_at')
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active');

        if ($courseId) {
            $query->where('e.course_id', $courseId);
        }

        $enrollments = $query->orderBy('p.last_name')->orderBy('p.first_name')->get([
            'e.enrollment_id as id',
            'e.scheduled_time',
            'c.' . CoachController::COURSE_NAME_COL . ' as course_name',
            'c.course_session_count',
            'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix',
        ]);

        if ($enrollments->isEmpty()) {
            return [];
        }

        $ids = $enrollments->pluck('id');

        // Sessions completed so far (all dates), per enrollment.
        $completed = DB::table(self::ATT_TABLE)
            ->whereIn('enrollment_id', $ids)
            ->whereNotNull('time_in')
            ->groupBy('enrollment_id')
            ->selectRaw('enrollment_id, COUNT(*) as total')
            ->pluck('total', 'enrollment_id');

        // What was logged on the chosen date.
        $logs = DB::table(self::ATT_TABLE)
            ->whereIn('enrollment_id', $ids)
            ->where('attendance_at', $date)
            ->orderByRaw('time_in IS NULL, time_in')
            ->get(['enrollment_id', 'time_in', 'time_out', 'attendance_state', 'logged_by'])
            ->groupBy('enrollment_id');

        $loggerIds = $logs->flatten(1)->pluck('logged_by')->filter()->unique()->values();

        $loggerNames = $loggerIds->isEmpty()
            ? []
            : DB::table('tbl_user_profile')
                ->whereIn('user_id', $loggerIds)
                ->get(['user_id', 'first_name', 'middle_name', 'last_name', 'name_suffix'])
                ->mapWithKeys(fn ($u) => [$u->user_id => $this->formatName($u)])
                ->all();

        return $enrollments->map(function ($e) use ($logs, $completed, $loggerNames) {
            $log = ($logs->get($e->id) ?? collect())->first();

            $done = (int) $completed->get($e->id, 0);
            $required = max(0, (int) $e->course_session_count);

            return [
                'id' => (int) $e->id,
                'name' => $this->formatName($e),
                'coach' => $log ? ($loggerNames[$log->logged_by] ?? null) : null,
                'course' => $e->course_name,
                'schedule' => $e->scheduled_time,
                'sessions' => $required > 0 ? "$done / $required" : (string) $done,
                'time_in' => $this->hhmm($log->time_in ?? null),
                'time_out' => $this->hhmm($log->time_out ?? null),
                'state' => $log->attendance_state ?? null,
            ];
        })->values()->all();
    }

    /* =========================================================
     * GET /admin/attendance/student-logs?enrollment_id=&filter=all|<enrollment_id>
     *
     * Every attendance log of the student that enrollment belongs to. The
     * filter narrows it to one of the student's enrollments (so logs from
     * courses they used to be enrolled in can be reviewed too); "all" also
     * includes older logs that have no enrollment_id yet.
     * ========================================================= */
    public function studentLogs(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $v = $request->validate([
            'enrollment_id' => ['required', 'integer'],
            'filter' => ['nullable', 'regex:/^(all|\d+)$/'],
        ]);

        $anchor = DB::table(self::ENROLLMENTS_TABLE . ' as e')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
            ->where('e.enrollment_id', $v['enrollment_id'])
            ->first(['e.user_id', 'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix']);

        if (!$anchor) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        $enrollments = DB::table(self::ENROLLMENTS_TABLE . ' as e')
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->where('e.user_id', $anchor->user_id)
            ->orderByDesc('e.enrollment_at')
            ->get([
                'e.enrollment_id as id',
                'c.' . CoachController::COURSE_NAME_COL . ' as course_name',
                'e.enrollment_at',
                'e.graduation_at',
            ]);

        $filter = $v['filter'] ?? (string) $v['enrollment_id'];

        if ($filter !== 'all' && !$enrollments->contains(fn ($e) => (int) $e->id === (int) $filter)) {
            return $this->fail('That enrollment does not belong to this student.');
        }

        $query = DB::table(self::ATT_TABLE . ' as a')
            ->leftJoin(self::ENROLLMENTS_TABLE . ' as e', 'e.enrollment_id', '=', 'a.enrollment_id')
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->where('a.user_id', $anchor->user_id);

        if ($filter !== 'all') {
            $query->where('a.enrollment_id', (int) $filter);
        }

        $logs = $query
            ->orderByDesc('a.attendance_at')
            ->orderByRaw('a.time_in IS NULL')
            ->orderByDesc('a.time_in')
            ->limit(1000)
            ->get([
                'a.attendance_id as id',
                'a.attendance_at',
                'a.time_in',
                'a.time_out',
                'a.attendance_state',
                'a.logged_by',
                'c.' . CoachController::COURSE_NAME_COL . ' as course_name',
            ]);

        $loggerIds = $logs->pluck('logged_by')->filter()->unique()->values();

        $loggerNames = $loggerIds->isEmpty()
            ? []
            : DB::table('tbl_user_profile')
                ->whereIn('user_id', $loggerIds)
                ->get(['user_id', 'first_name', 'middle_name', 'last_name', 'name_suffix'])
                ->mapWithKeys(fn ($u) => [$u->user_id => $this->formatName($u)])
                ->all();

        return response()->json([
            'success' => true,
            'student' => $this->formatName($anchor),
            'filter' => $filter,
            'enrollments' => $enrollments->map(function ($e) {
                $start = $e->enrollment_at ? Carbon::parse($e->enrollment_at)->format('M d, Y') : '?';
                $end = $e->graduation_at
                    ? 'Graduated ' . Carbon::parse($e->graduation_at)->format('M d, Y')
                    : 'Present';

                return [
                    'id' => (int) $e->id,
                    'label' => ($e->course_name ?? 'Course not assigned') . " ($start - $end)",
                ];
            })->values(),
            'logs' => $logs->map(fn ($l) => [
                'id' => (int) $l->id,
                'date' => (string) $l->attendance_at,
                'course' => $l->course_name,
                'time_in' => $this->hhmm($l->time_in),
                'time_out' => $this->hhmm($l->time_out),
                'state' => $l->attendance_state,
                'logged_by' => $loggerNames[$l->logged_by] ?? null,
            ])->values(),
        ]);
    }

    /* =========================================================
     * POST /admin/attendance/coaches/time-in   { coach_id, date, time }
     * POST /admin/attendance/coaches/time-out  { coach_id, date, time }
     * ========================================================= */
    public function coachTimeIn(Request $request): JsonResponse
    {
        return $this->recordCoachTime($request, 'in');
    }

    public function coachTimeOut(Request $request): JsonResponse
    {
        return $this->recordCoachTime($request, 'out');
    }

    private function recordCoachTime(Request $request, string $kind): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $v = $request->validate([
            'coach_id' => ['required', 'integer'],
            // Allow up to tomorrow so a server clock behind the admin's
            // timezone (e.g. UTC vs Manila) never rejects "today".
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . Carbon::now()->addDay()->toDateString()],
            'time' => ['required', 'date_format:H:i'],
        ], [
            'time.date_format' => 'Please enter a valid time.',
            'date.before_or_equal' => 'You cannot record attendance for a future date.',
        ]);

        $pk = CoachController::USER_PK;

        $isActiveCoach = DB::table(CoachController::USERS_TABLE . ' as u')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where("u.$pk", $v['coach_id'])
            ->where('u.' . CoachController::ROLE_COL, CoachController::ROLE_COACH)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->exists();

        if (!$isActiveCoach) {
            return response()->json(['success' => false, 'message' => 'Active coach not found.'], 404);
        }

        $loggedBy = Auth::id();

        return DB::transaction(function () use ($v, $kind, $loggedBy) {
            $scope = fn () => DB::table(self::ATT_TABLE)
                ->where('user_id', $v['coach_id'])
                ->where('role_id', CoachController::ROLE_COACH)
                ->where('attendance_at', $v['date']);

            $row = $scope()->orderByRaw('time_in IS NULL, time_in')->lockForUpdate()->first();

            if ($kind === 'in') {
                if ($row && $row->time_in) {
                    return $this->fail('This coach already has a Time In for that date.');
                }

                if ($row) {
                    // e.g. a row that was created without a time yet
                    $scope()->where('attendance_id', $row->attendance_id)->update([
                        'time_in' => $v['time'],
                        'attendance_state' => self::STATE_PRESENT,
                        'logged_by' => $loggedBy,
                    ]);
                } else {
                    DB::table(self::ATT_TABLE)->insert([
                        'user_id' => $v['coach_id'],
                        'role_id' => CoachController::ROLE_COACH,
                        'attendance_at' => $v['date'],
                        'time_in' => $v['time'],
                        'attendance_state' => self::STATE_PRESENT,
                        'logged_by' => $loggedBy,
                    ]);
                }

                return response()->json(['success' => true, 'message' => 'Time In recorded.']);
            }

            // Time Out
            if (!$row || !$row->time_in) {
                return $this->fail('Record the Time In first.');
            }

            if ($row->time_out) {
                return $this->fail('This coach already has a Time Out for that date.');
            }

            if ($v['time'] <= $this->hhmm($row->time_in)) {
                return $this->fail('Time Out must be later than Time In.');
            }

            $scope()->where('attendance_id', $row->attendance_id)->update(['time_out' => $v['time']]);

            return response()->json(['success' => true, 'message' => 'Time Out recorded.']);
        });
    }

    /* ---------------------------------------------------------
     * Helpers
     * --------------------------------------------------------- */

    private function fail(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }

    /** "Last, First M. Suffix" — same style as the Graduation page. */
    private function formatName(object $r): string
    {
        $middle = trim((string) ($r->middle_name ?? ''));
        $initial = $middle !== '' ? mb_substr($middle, 0, 1) . '.' : '';
        $given = trim(trim((string) $r->first_name . ' ' . $initial) . ' ' . trim((string) ($r->name_suffix ?? '')));
        $last = trim((string) $r->last_name);

        return $last !== '' ? $last . ', ' . $given : $given;
    }

    /** TIME value ("08:05:00") -> "HH:MM" (24h), or null. */
    private function hhmm($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('H:i');
    }
}