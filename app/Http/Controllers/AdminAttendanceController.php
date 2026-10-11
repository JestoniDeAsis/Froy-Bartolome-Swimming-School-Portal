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
 *   - Coaches tab:  the admin records each coach's Time In / Time Out.
 *   - Students tab: read-only view of what coaches logged for students.
 *
 * Rows live in tbl_attendances (see App\Models\Attendance). Coach rows have
 * role_id = 2 and no enrollment_id; student rows point to their
 * tbl_enrollments row through enrollment_id.
 *
 * The student queries are shared with the coach page through
 * HandlesAttendanceData. Shared table/column names (users, status, courses,
 * coach_courses) come from CoachController's constants.
 */
class AdminAttendanceController extends Controller
{
    use HandlesAttendanceData;

    // Kept so any other file still referencing these keeps working;
    // new code should use App\Models\Attendance instead.
    public const ATT_TABLE = 'tbl_attendances';
    public const ENROLLMENTS_TABLE = Attendance::ENROLLMENTS_TABLE;
    public const STATE_PRESENT = Attendance::STATE_PRESENT;

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

        $logs = Attendance::ofRole(CoachController::ROLE_COACH)
            ->forUser($ids)
            ->onDate($date)
            ->earliestFirst()
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
     * GET /admin/attendance/student-logs?enrollment_id=&filter=all|<enrollment_id>
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

        return $this->studentLogsPayload((int) $v['enrollment_id'], $v['filter'] ?? null);
    }

    /* =========================================================
     * POST /admin/attendance/coaches/time-in   { coach_id, date, time }
     * POST /admin/attendance/coaches/time-out  { coach_id, date, time }
     *
     * The coach's Time In is what opens student logging on the coach page;
     * their Time Out closes it for the rest of that day.
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
            $row = Attendance::ofRole(CoachController::ROLE_COACH)
                ->forUser((int) $v['coach_id'])
                ->onDate($v['date'])
                ->earliestFirst()
                ->lockForUpdate()
                ->first();

            if ($kind === 'in') {
                if ($row && $row->time_in) {
                    return $this->fail('This coach already has a Time In for that date.');
                }

                if ($row) {
                    // e.g. a row that was created without a time yet
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

            $row->update(['time_out' => $v['time']]);

            return response()->json(['success' => true, 'message' => 'Time Out recorded.']);
        });
    }
}