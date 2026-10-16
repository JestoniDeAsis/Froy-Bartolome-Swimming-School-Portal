<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAttendanceData;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
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
 *
 * A coach marked "handles athletes" (tbl_users.handles_athletes) also sees
 * every Active athlete (role 4) and logs them with the same rules. Athletes
 * are not enrolled in a course, so their rows are keyed by user id: the
 * attendance row has role_id 4 and no enrollment_id.
 */
class CoachAttendanceController extends Controller
{
    use HandlesAttendanceData;

    private const ATHLETE_ROLE_ID = 4;

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

    /** Whether the admin marked this coach as also handling athletes. */
    private function handlesAthletes(int $coachId): bool
    {
        return (int) DB::table(CoachController::USERS_TABLE)
            ->where(CoachController::USER_PK, $coachId)
            ->value(CoachController::HANDLES_ATHLETES_COL) === 1;
    }

    /** user id => "First Last" for the people who logged attendance (coaches). */
    private function loggerNames(iterable $userIds): Collection
    {
        $ids = collect($userIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return DB::table('tbl_user_profile')
            ->whereIn('user_id', $ids)
            ->get(['user_id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($p) => [(int) $p->user_id => trim($p->first_name . ' ' . $p->last_name)]);
    }

    /**
     * Rows for the table: every Active athlete with their attendance for $date.
     * Same fields as the student rows; "id" is a string so it can never clash
     * with an enrollment id, and "kind" tells the page to send athlete_id.
     */
    private function athleteRows(string $date): Collection
    {
        $athletes = DB::table(CoachController::USERS_TABLE . ' as u')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'u.' . CoachController::USER_PK)
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('u.' . CoachController::ROLE_COL, self::ATHLETE_ROLE_ID)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->get(['u.' . CoachController::USER_PK . ' as id', 'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix']);

        if ($athletes->isEmpty()) {
            return collect();
        }

        $ids = $athletes->pluck('id');

        $logs = Attendance::ofRole(self::ATHLETE_ROLE_ID)
            ->forUser($ids)
            ->onDate($date)
            ->earliestFirst()
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $sessions = Attendance::ofRole(self::ATHLETE_ROLE_ID)
            ->forUser($ids)
            ->attended()
            ->selectRaw('user_id, COUNT(*) as n')
            ->groupBy('user_id')
            ->pluck('n', 'user_id');

        $loggers = $this->loggerNames($logs->pluck('logged_by'));

        return $athletes->map(function ($a) use ($logs, $sessions, $loggers) {
            $log = $logs->get($a->id);

            $middle = trim((string) $a->middle_name);
            $given = trim($a->first_name . ' ' . ($middle !== '' ? mb_substr($middle, 0, 1) . '.' : ''));
            $name = trim($a->last_name . ', ' . $given . ($a->name_suffix ? ' ' . $a->name_suffix : ''));

            return [
                'id' => 'athlete-' . $a->id,
                'kind' => 'athlete',
                'user_id' => (int) $a->id,
                'name' => $name,
                'coach' => $log ? ($loggers->get((int) $log->logged_by) ?? '') : '',
                'course' => 'Athlete',
                'schedule' => '',
                'sessions' => (string) ($sessions->get($a->id) ?? 0),
                'time_in' => $this->hhmm($log->time_in ?? null),
                'time_out' => $this->hhmm($log->time_out ?? null),
                'state' => $log
                    ? ($log->attendance_state ?: ($log->time_in ? Attendance::STATE_PRESENT : 'Absent'))
                    : 'Absent',
            ];
        })->values();
    }

    /** The log history of one athlete (attendance rows saved with the athlete role). */
    private function athleteLogs(int $athleteId): JsonResponse
    {
        $isAthlete = DB::table(CoachController::USERS_TABLE)
            ->where(CoachController::USER_PK, $athleteId)
            ->where(CoachController::ROLE_COL, self::ATHLETE_ROLE_ID)
            ->exists();

        if (!$isAthlete) {
            return $this->fail('Athlete not found.', 404);
        }

        $logs = Attendance::ofRole(self::ATHLETE_ROLE_ID)
            ->forUser($athleteId)
            ->orderByDesc('attendance_at')
            ->orderByDesc('time_in')
            ->get();

        $loggers = $this->loggerNames($logs->pluck('logged_by'));

        return response()->json([
            'success' => true,
            'enrollments' => [],   // athletes have no courses to filter by
            'logs' => $logs->map(fn ($log) => [
                'date' => (string) $log->attendance_at,
                'course' => 'Athlete',
                'time_in' => $this->hhmm($log->time_in),
                'time_out' => $this->hhmm($log->time_out),
                'state' => $log->attendance_state ?: ($log->time_in ? Attendance::STATE_PRESENT : 'Absent'),
                'logged_by' => $loggers->get((int) $log->logged_by) ?? '',
            ])->values(),
        ]);
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
            'course_id' => ['nullable', 'regex:/^(\d+|athletes)$/'],   // a course id, or "athletes"
        ]);

        $coachId = (int) Auth::id();
        $courseIds = $this->coachCourseIds($coachId);
        $handlesAthletes = $this->handlesAthletes($coachId);

        $filter = $v['course_id'] ?? null;
        $onlyAthletes = $filter === 'athletes';
        $courseId = ($filter !== null && !$onlyAthletes) ? (int) $filter : null;

        $courses = $courseIds
            ? DB::table(CoachController::COURSES_TABLE)
                ->whereIn(CoachController::COURSE_ID_COL, $courseIds)
                ->orderBy(CoachController::COURSE_ID_COL)
                ->get([CoachController::COURSE_ID_COL . ' as id', CoachController::COURSE_NAME_COL . ' as name'])
                ->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name])
                ->values()
            : collect();

        // Coaches who handle athletes get an "Athletes" choice in the class filter.
        if ($handlesAthletes) {
            $courses = collect($courses)->concat([['id' => 'athletes', 'name' => 'Athletes']])->values();
        }

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
            'rows' => $this->rowsFor($v['date'], $courseId, $courseIds, $onlyAthletes, $handlesAthletes),
        ]);
    }

    /**
     * Students of the coach's classes, then the athletes (when this coach
     * handles them). A course filter shows only that course; the "Athletes"
     * filter shows only athletes.
     */
    private function rowsFor(string $date, ?int $courseId, array $courseIds, bool $onlyAthletes, bool $handlesAthletes): Collection
    {
        $students = $onlyAthletes ? collect() : collect($this->studentRows($date, $courseId, $courseIds));

        $athletes = ($handlesAthletes && ($onlyAthletes || $courseId === null))
            ? $this->athleteRows($date)
            : collect();

        return $students->concat($athletes)->values();
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
            'enrollment_id' => ['required_without:athlete_id', 'nullable', 'integer'],
            'athlete_id' => ['required_without:enrollment_id', 'nullable', 'integer'],
            'filter' => ['nullable', 'regex:/^(all|\d+)$/'],
        ]);

        // An athlete's history (only for coaches who handle athletes).
        if (!empty($v['athlete_id'])) {
            if (!$this->handlesAthletes((int) Auth::id())) {
                return $this->fail('You are not set up to handle athletes.', 403);
            }

            return $this->athleteLogs((int) $v['athlete_id']);
        }

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
            'enrollment_id' => ['required_without:athlete_id', 'nullable', 'integer'],
            'athlete_id' => ['required_without:enrollment_id', 'nullable', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . Carbon::now()->addDay()->toDateString()],
            'time' => ['required', 'date_format:H:i'],
        ], [
            'time.date_format' => 'Please enter a valid time.',
            'date.before_or_equal' => 'You cannot record attendance for a future date.',
        ]);

        $coachId = (int) Auth::id();

        if (!empty($v['athlete_id'])) {
            return $this->recordAthleteTime($v, $kind, $coachId);
        }

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

    /**
     * Same rules as a student log (the coach must be timed in and not timed
     * out, no earlier than their own Time In), but for an athlete: the row is
     * found / created by user id with role 4 and no enrollment.
     */
    private function recordAthleteTime(array $v, string $kind, int $coachId): JsonResponse
    {
        if (!$this->handlesAthletes($coachId)) {
            return $this->fail('You are not set up to handle athletes.', 403);
        }

        $athleteId = (int) $v['athlete_id'];

        $athlete = DB::table(CoachController::USERS_TABLE . ' as u')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('u.' . CoachController::USER_PK, $athleteId)
            ->where('u.' . CoachController::ROLE_COL, self::ATHLETE_ROLE_ID)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->exists();

        if (!$athlete) {
            return $this->fail('Active athlete not found.', 404);
        }

        return DB::transaction(function () use ($v, $kind, $coachId, $athleteId) {
            $coachRow = $this->coachRow($coachId, $v['date'], true);

            if ($reason = $this->blockReason($coachId, $v['date'], $coachRow)) {
                return $this->fail($reason, 403);
            }

            if ($v['time'] < $this->hhmm($coachRow->time_in)) {
                return $this->fail('The time cannot be earlier than your own Time In (' . $this->hhmm($coachRow->time_in) . ').');
            }

            $row = Attendance::ofRole(self::ATHLETE_ROLE_ID)
                ->forUser($athleteId)
                ->onDate($v['date'])
                ->earliestFirst()
                ->lockForUpdate()
                ->first();

            if ($kind === 'in') {
                if ($row && $row->time_in) {
                    return $this->fail('This athlete already has a Time In for that date.');
                }

                if ($row) {
                    $row->update([
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                        'logged_by' => $coachId,
                    ]);
                } else {
                    Attendance::create([
                        'user_id' => $athleteId,
                        'role_id' => self::ATHLETE_ROLE_ID,
                        'attendance_at' => $v['date'],
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                        'logged_by' => $coachId,
                        'enrollment_id' => null,
                    ]);
                }

                return response()->json(['success' => true, 'message' => 'Time In recorded.']);
            }

            // Time Out
            if (!$row || !$row->time_in) {
                return $this->fail('Record the Time In first.');
            }

            if ($row->time_out) {
                return $this->fail('This athlete already has a Time Out for that date.');
            }

            if ($v['time'] <= $this->hhmm($row->time_in)) {
                return $this->fail('Time Out must be later than Time In.');
            }

            $row->update(['time_out' => $v['time']]);

            return response()->json(['success' => true, 'message' => 'Time Out recorded.']);
        });
    }
}