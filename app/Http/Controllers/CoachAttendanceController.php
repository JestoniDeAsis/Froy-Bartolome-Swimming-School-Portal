<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAttendanceData;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Coach > Attendance.
 *
 * A coach can always MONITOR the attendance of the students in their assigned
 * classes. They can only LOG (Time In / Time Out) a student while the admin
 * has timed THEM in for that day and has not timed them out:
 *
 *   no Time In yet  -> logging is closed ("wait for the admin")
 *   Time In only    -> logging is open
 *   Time Out set    -> logging is closed for good that day
 *
 * The rule is enforced here on the server (not just by disabling buttons).
 * A coach only ever sees / logs enrollments whose course is one of the
 * courses assigned to them in tbl_coach_courses.
 */
class CoachAttendanceController extends Controller
{
    use HandlesAttendanceData;

    private function denyUnlessCoach(Request $request): ?JsonResponse
    {
        $user = $request->user();
        $isCoach = $user && (int) ($user->{CoachController::ROLE_COL} ?? 0) === CoachController::ROLE_COACH;

        return $isCoach
            ? null
            : response()->json(['success' => false, 'message' => 'You are not allowed to do that.'], 403);
    }

    /** @return int[] course ids assigned to this coach */
    private function coachCourseIds(int $coachId): array
    {
        return DB::table(CoachController::COACH_COURSES_TABLE)
            ->where('coach_id', $coachId)
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function isActiveCoach(int $coachId): bool
    {
        return DB::table(CoachController::USERS_TABLE . ' as u')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('u.' . CoachController::USER_PK, $coachId)
            ->where('u.' . CoachController::ROLE_COL, CoachController::ROLE_COACH)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->exists();
    }

    /** The coach's own attendance row (recorded by the admin) for a date. */
    private function coachRow(int $coachId, string $date, bool $sharedLock = false): ?Attendance
    {
        $query = Attendance::ofRole(CoachController::ROLE_COACH)
            ->forUser($coachId)
            ->onDate($date)
            ->earliestFirst();

        if ($sharedLock) {
            // Makes the admin's Time Out (which locks this row for update)
            // wait until a student log being saved right now has finished.
            $query->sharedLock();
        }

        return $query->first();
    }

    /**
     * Why this coach may NOT log attendance for $date, or null when they may.
     */
    private function blockReason(int $coachId, string $date, ?Attendance $row): ?string
    {
        if (!$this->isActiveCoach($coachId)) {
            return 'Your account is not active, so you cannot log attendance.';
        }

        // One day of slack either side so a server clock in another timezone
        // (e.g. UTC vs Manila) never blocks "today".
        if ($date < Carbon::now()->subDay()->toDateString() || $date > Carbon::now()->addDay()->toDateString()) {
            return 'Attendance can only be logged for today.';
        }

        if (!$row || !$row->time_in) {
            return 'The admin has not recorded your Time In yet. You can log attendance once you are timed in.';
        }

        if ($row->time_out) {
            return 'You have been timed out, so you can no longer log attendance.';
        }

        return null;
    }

    /* =========================================================
     * GET /coach/attendance/data?date=YYYY-MM-DD&course_id=
     * Students in the coach's classes + the coach's own session state.
     * ========================================================= */
    public function data(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessCoach($request)) {
            return $denied;
        }

        $v = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'course_id' => ['nullable', 'integer'],
        ]);

        $coachId = (int) Auth::id();
        $courseIds = $this->coachCourseIds($coachId);
        $courseId = isset($v['course_id']) ? (int) $v['course_id'] : null;

        $courses = $courseIds
            ? DB::table(CoachController::COURSES_TABLE)
                ->whereIn(CoachController::COURSE_ID_COL, $courseIds)
                ->orderBy(CoachController::COURSE_ID_COL)
                ->get([CoachController::COURSE_ID_COL . ' as id', CoachController::COURSE_NAME_COL . ' as name'])
                ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
                ->values()
            : collect();

        $row = $this->coachRow($coachId, $v['date']);
        $reason = $this->blockReason($coachId, $v['date'], $row);

        $state = !$row || !$row->time_in ? 'not_in' : ($row->time_out ? 'out' : 'in');

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'session' => [
                'state' => $state,
                'time_in' => $this->hhmm($row->time_in ?? null),
                'time_out' => $this->hhmm($row->time_out ?? null),
            ],
            'can_log' => $reason === null,
            'block_reason' => $reason,
            'rows' => $this->studentRows($v['date'], $courseId, $courseIds),
        ]);
    }

    /* =========================================================
     * GET /coach/attendance/student-logs?enrollment_id=&filter=all|<enrollment_id>
     * ========================================================= */
    public function studentLogs(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessCoach($request)) {
            return $denied;
        }

        $v = $request->validate([
            'enrollment_id' => ['required', 'integer'],
            'filter' => ['nullable', 'regex:/^(all|\d+)$/'],
        ]);

        return $this->studentLogsPayload(
            (int) $v['enrollment_id'],
            $v['filter'] ?? null,
            $this->coachCourseIds((int) Auth::id())
        );
    }

    /* =========================================================
     * POST /coach/attendance/students/time-in   { enrollment_id, date, time }
     * POST /coach/attendance/students/time-out  { enrollment_id, date, time }
     * ========================================================= */
    public function studentTimeIn(Request $request): JsonResponse
    {
        return $this->recordStudentTime($request, 'in');
    }

    public function studentTimeOut(Request $request): JsonResponse
    {
        return $this->recordStudentTime($request, 'out');
    }

    private function recordStudentTime(Request $request, string $kind): JsonResponse
    {
        if ($denied = $this->denyUnlessCoach($request)) {
            return $denied;
        }

        $v = $request->validate([
            'enrollment_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . Carbon::now()->addDay()->toDateString()],
            'time' => ['required', 'date_format:H:i'],
        ], [
            'time.date_format' => 'Please enter a valid time.',
            'date.before_or_equal' => 'You cannot record attendance for a future date.',
        ]);

        $coachId = (int) Auth::id();

        // The enrollment must be an active one, of an Active user, in one of
        // this coach's classes.
        $enrollment = DB::table(Attendance::ENROLLMENTS_TABLE . ' as e')
            ->join(CoachController::USERS_TABLE . ' as u', 'u.' . CoachController::USER_PK, '=', 'e.user_id')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('e.enrollment_id', $v['enrollment_id'])
            ->whereNull('e.graduation_at')
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->first(['e.enrollment_id', 'e.user_id', 'e.course_id', 'u.' . CoachController::ROLE_COL . ' as role_id']);

        if (!$enrollment) {
            return $this->fail('Active student not found.', 404);
        }

        if (!in_array((int) $enrollment->course_id, $this->coachCourseIds($coachId), true)) {
            return $this->fail('This student is not in one of your classes.', 403);
        }

        return DB::transaction(function () use ($v, $kind, $coachId, $enrollment) {
            // The gate is checked inside the transaction, against the coach's
            // row as it is right now.
            $coachRow = $this->coachRow($coachId, $v['date'], true);

            if ($reason = $this->blockReason($coachId, $v['date'], $coachRow)) {
                return $this->fail($reason, 403);
            }

            if ($v['time'] < $this->hhmm($coachRow->time_in)) {
                return $this->fail('The time cannot be earlier than your own Time In (' . $this->hhmm($coachRow->time_in) . ').');
            }

            $row = Attendance::forEnrollment((int) $enrollment->enrollment_id)
                ->onDate($v['date'])
                ->earliestFirst()
                ->lockForUpdate()
                ->first();

            if ($kind === 'in') {
                if ($row && $row->time_in) {
                    return $this->fail('This student already has a Time In for that date.');
                }

                if ($row) {
                    $row->update([
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                        'logged_by' => $coachId,
                    ]);
                } else {
                    Attendance::create([
                        'user_id' => $enrollment->user_id,
                        'role_id' => $enrollment->role_id,
                        'attendance_at' => $v['date'],
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                        'logged_by' => $coachId,
                        'enrollment_id' => $enrollment->enrollment_id,
                    ]);
                }

                return response()->json(['success' => true, 'message' => 'Time In recorded.']);
            }

            // Time Out
            if (!$row || !$row->time_in) {
                return $this->fail('Record the Time In first.');
            }

            if ($row->time_out) {
                return $this->fail('This student already has a Time Out for that date.');
            }

            if ($v['time'] <= $this->hhmm($row->time_in)) {
                return $this->fail('Time Out must be later than Time In.');
            }

            $row->update(['time_out' => $v['time']]);

            return response()->json(['success' => true, 'message' => 'Time Out recorded.']);
        });
    }
}