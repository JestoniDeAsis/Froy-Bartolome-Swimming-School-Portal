<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAttendanceData;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin > Attendance.
 *   - Coaches tab: records each coach's Time In / Time Out and shows their logs.
 *   - Students tab: read-only view of what coaches logged for students.
 *   - Athletes tab: read-only view of athlete attendance (role_id = 4).
 *
 * Rows live in tbl_attendances (see App\Models\Attendance).
 * Coach rows have role_id = 2 and no enrollment_id; student rows point
 * to their tbl_enrollments row through enrollment_id.
 *
 * Student queries are shared with the coach page through
 * HandlesAttendanceData.
 */
class AdminAttendanceController extends Controller
{
    use HandlesAttendanceData;

    // Kept for compatibility with any other file referencing these.
    public const ATT_TABLE = 'tbl_attendances';
    public const ENROLLMENTS_TABLE = Attendance::ENROLLMENTS_TABLE;
    public const STATE_PRESENT = Attendance::STATE_PRESENT;

    // Athlete role ID.
    private const ROLE_ATHLETE = 4;

    private function denyUnlessAdmin(Request $request): ?JsonResponse
    {
        return CoachController::userIsAdmin($request->user())
            ? null
            : response()->json([
                'success' => false,
                'message' => 'You are not allowed to do that.',
            ], 403);
    }

    /* =========================================================
     * GET /admin/attendance/data?tab=coaches|students|athletes
     *     &date=YYYY-MM-DD&course_id=
     * ========================================================= */
    public function data(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $v = $request->validate([
            'tab' => ['required', Rule::in(['coaches', 'students', 'athletes'])],
            'date' => ['required', 'date_format:Y-m-d'],
            'course_id' => ['nullable', 'integer'],
        ]);

        $courseId = isset($v['course_id'])
            ? (int) $v['course_id']
            : null;

        $courses = DB::table(CoachController::COURSES_TABLE)
            ->orderBy(CoachController::COURSE_ID_COL)
            ->get([
                CoachController::COURSE_ID_COL . ' as id',
                CoachController::COURSE_NAME_COL . ' as name',
            ])
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => $c->name,
            ])
            ->values();

        if ($v['tab'] === 'coaches') {
            $rows = $this->coachRows($v['date'], $courseId);
        } elseif ($v['tab'] === 'students') {
            $rows = $this->studentRows($v['date'], $courseId);
        } else {
            // The athletes tab does not use the class/course filter.
            $rows = $this->athleteRows($v['date']);
        }

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'rows' => $rows,
        ]);
    }

    /* =========================================================
     * Coaches: Active coaches (optionally filtered by assigned course)
     * and their attendance row for the chosen date.
     * ========================================================= */
    private function coachRows(string $date, ?int $courseId): array
    {
        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        $query = DB::table("$users as u")
            ->join('tbl_user_profile as p', 'p.user_id', '=', "u.$pk")
            ->join(
                CoachController::STATUS_TABLE . ' as st',
                'st.' . CoachController::STATUS_ID_COL,
                '=',
                'u.' . CoachController::STATUS_COL
            )
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

        $coaches = $query
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->get([
                "u.$pk as id",
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix',
            ]);

        if ($coaches->isEmpty()) {
            return [];
        }

        $ids = $coaches->pluck('id');

        $assigned = DB::table(CoachController::COACH_COURSES_TABLE . ' as cc')
            ->join(
                CoachController::COURSES_TABLE . ' as c',
                'c.' . CoachController::COURSE_ID_COL,
                '=',
                'cc.course_id'
            )
            ->whereIn('cc.coach_id', $ids)
            ->orderBy('c.' . CoachController::COURSE_ID_COL)
            ->get([
                'cc.coach_id',
                'c.' . CoachController::COURSE_NAME_COL . ' as name',
            ])
            ->groupBy('coach_id');

        $logs = Attendance::ofRole(CoachController::ROLE_COACH)
            ->forUser($ids)
            ->onDate($date)
            ->earliestFirst()
            ->get([
                'user_id',
                'time_in',
                'time_out',
                'attendance_state',
            ])
            ->groupBy('user_id');

        return $coaches->map(function ($c) use ($assigned, $logs) {
            $log = ($logs->get($c->id) ?? collect())->first();

            return [
                'id' => (int) $c->id,
                'name' => $this->formatName($c),
                'classes' => ($assigned->get($c->id) ?? collect())
                    ->pluck('name')
                    ->implode(', '),
                'time_in' => $this->hhmm($log->time_in ?? null),
                'time_out' => $this->hhmm($log->time_out ?? null),
                'state' => $log->attendance_state ?? null,
            ];
        })->values()->all();
    }

    /* =========================================================
     * Athletes: all users with role_id = 4 and their attendance
     * for the chosen date. This tab is read-only.
     * ========================================================= */
    private function athleteRows(string $date): array
    {
        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        $athletes = DB::table("$users as u")
            ->join('tbl_user_profile as p', 'p.user_id', '=', "u.$pk")
            ->where('u.' . CoachController::ROLE_COL, self::ROLE_ATHLETE)
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->get([
                "u.$pk as id",
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix',
            ]);

        if ($athletes->isEmpty()) {
            return [];
        }

        $ids = $athletes->pluck('id');

        $logs = Attendance::ofRole(self::ROLE_ATHLETE)
            ->forUser($ids)
            ->onDate($date)
            ->earliestFirst()
            ->get([
                'user_id',
                'time_in',
                'time_out',
                'attendance_state',
            ])
            ->groupBy('user_id');

        return $athletes->map(function ($athlete) use ($logs) {
            $log = ($logs->get($athlete->id) ?? collect())->first();

            return [
                'id' => (int) $athlete->id,
                'name' => $this->formatName($athlete),
                'time_in' => $this->hhmm($log->time_in ?? null),
                'time_out' => $this->hhmm($log->time_out ?? null),
                'state' => $log->attendance_state ?? null,
            ];
        })->values()->all();
    }

    /* =========================================================
     * GET /admin/attendance/student-logs
     *     ?enrollment_id=&filter=all|<enrollment_id>
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

        return $this->studentLogsPayload(
            (int) $v['enrollment_id'],
            $v['filter'] ?? null
        );
    }

    /* =========================================================
     * POST /admin/attendance/coaches/time-in
     * POST /admin/attendance/coaches/time-out
     *
     * The coach's Time In opens student logging on the coach page;
     * their Time Out closes it for the rest of that day.
     * ========================================================= */
    public function coachTimeIn(Request $request): JsonResponse
    {
        return $this->recordCoachTime($request, 'in');
    }

    /**
     * GET /admin/attendance/athlete-logs?athlete_id=
     *
     * Returns the complete attendance history for one athlete.
     */
    public function athleteLogs(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $v = $request->validate([
            'athlete_id' => ['required', 'integer'],
        ]);

        return $this->roleLogsPayload(
            (int) $v['athlete_id'],
            self::ROLE_ATHLETE,
            'Athlete not found.'
        );
    }

    /**
     * GET /admin/attendance/coach-logs?coach_id=
     *
     * Returns the complete attendance history for one coach.
     */
    public function coachLogs(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $v = $request->validate([
            'coach_id' => ['required', 'integer'],
        ]);

        return $this->roleLogsPayload(
            (int) $v['coach_id'],
            CoachController::ROLE_COACH,
            'Coach not found.'
        );
    }

    /**
     * Shared by the athlete and coach log endpoints: the full attendance
     * history of one user with the given role.
     */
    private function roleLogsPayload(int $userId, int $roleId, string $notFoundMessage): JsonResponse
    {
        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        $exists = DB::table($users . ' as u')
            ->where("u.$pk", $userId)
            ->where('u.' . CoachController::ROLE_COL, $roleId)
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => $notFoundMessage,
            ], 404);
        }

        $attendance = Attendance::ofRole($roleId)
            ->forUser($userId)
            ->earliestFirst()
            ->get([
                'attendance_at',
                'time_in',
                'time_out',
                'attendance_state',
                'logged_by',
            ]);

        $loggerIds = $attendance
            ->pluck('logged_by')
            ->filter()
            ->unique()
            ->values();

        $loggers = collect();

        if ($loggerIds->isNotEmpty()) {
            $loggers = DB::table("$users as u")
                ->leftJoin(
                    'tbl_user_profile as p',
                    'p.user_id',
                    '=',
                    "u.$pk"
                )
                ->whereIn("u.$pk", $loggerIds)
                ->get([
                    "u.$pk as id",
                    'p.first_name',
                    'p.middle_name',
                    'p.last_name',
                    'p.name_suffix',
                ])
                ->keyBy('id');
        }

        $logs = $attendance->map(function ($log) use ($loggers) {
            $logger = $log->logged_by
                ? $loggers->get($log->logged_by)
                : null;

            return [
                'date' => Carbon::parse($log->attendance_at)->toDateString(),
                'time_in' => $this->hhmm($log->time_in),
                'time_out' => $this->hhmm($log->time_out),
                'state' => $log->attendance_state,
                'logged_by' => $logger
                    ? $this->formatName($logger)
                    : '--',
            ];
        })->values();

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
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
            // Allow up to tomorrow to account for timezone differences.
            'date' => [
                'required',
                'date_format:Y-m-d',
                'before_or_equal:' . Carbon::now()->addDay()->toDateString(),
            ],
            'remove' => ['sometimes', 'boolean'],
            // Time is only needed when saving; not when removing.
            'time' => [
                Rule::requiredIf(!$request->boolean('remove')),
                'nullable',
                'date_format:H:i',
            ],
        ], [
            'time.date_format' => 'Please enter a valid time.',
            'date.before_or_equal' => 'You cannot record attendance for a future date.',
        ]);

        $pk = CoachController::USER_PK;

        $isActiveCoach = DB::table(CoachController::USERS_TABLE . ' as u')
            ->join(
                CoachController::STATUS_TABLE . ' as st',
                'st.' . CoachController::STATUS_ID_COL,
                '=',
                'u.' . CoachController::STATUS_COL
            )
            ->where("u.$pk", $v['coach_id'])
            ->where('u.' . CoachController::ROLE_COL, CoachController::ROLE_COACH)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->exists();

        if (!$isActiveCoach) {
            return response()->json([
                'success' => false,
                'message' => 'Active coach not found.',
            ], 404);
        }

        $loggedBy = Auth::id();

        $remove = (bool) ($v['remove'] ?? false);

        return DB::transaction(function () use ($v, $kind, $loggedBy, $remove) {
            $row = Attendance::ofRole(CoachController::ROLE_COACH)
                ->forUser((int) $v['coach_id'])
                ->onDate($v['date'])
                ->earliestFirst()
                ->lockForUpdate()
                ->first();

            /* ---------------- Time In (add / edit / remove) ---------------- */
            if ($kind === 'in') {
                if ($remove) {
                    if (!$row || !$row->time_in) {
                        return $this->fail('This coach has no Time In to remove.');
                    }

                    if ($row->time_out) {
                        return $this->fail(
                            'Remove the Time Out first before removing the Time In.'
                        );
                    }

                    // No times left, so the day's row is no longer needed.
                    $row->delete();

                    return response()->json([
                        'success' => true,
                        'message' => 'Time In removed.',
                    ]);
                }

                if ($row && $row->time_out
                    && $v['time'] >= $this->hhmm($row->time_out)) {
                    return $this->fail('Time In must be earlier than Time Out.');
                }

                if ($row) {
                    // Adds or overwrites the Time In on the existing row.
                    $row->update([
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                        'logged_by' => $loggedBy,
                    ]);
                } else {
                    Attendance::create([
                        'user_id' => $v['coach_id'],
                        'role_id' => CoachController::ROLE_COACH,
                        'attendance_at' => $v['date'],
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                        'logged_by' => $loggedBy,
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Time In saved.',
                ]);
            }

            /* ---------------- Time Out (add / edit / remove) ---------------- */
            if (!$row || !$row->time_in) {
                return $this->fail('Record the Time In first.');
            }

            if ($remove) {
                if (!$row->time_out) {
                    return $this->fail('This coach has no Time Out to remove.');
                }

                $row->update(['time_out' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'Time Out removed.',
                ]);
            }

            if ($v['time'] <= $this->hhmm($row->time_in)) {
                return $this->fail('Time Out must be later than Time In.');
            }

            // Adds or overwrites the Time Out.
            $row->update(['time_out' => $v['time']]);

            return response()->json([
                'success' => true,
                'message' => 'Time Out saved.',
            ]);
        });
    }
}