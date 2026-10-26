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
 * classes. They time THEMSELVES in and out (selfTimeIn / selfTimeOut) and the
 * time always comes from the server's clock: the browser sends no time, so it
 * cannot be chosen or changed. Only the admin can edit a coach's times later.
 *
 * A coach can only LOG (Time In / Time Out) a student once they have timed in
 * for that day:
 *
 *   no Time In yet  -> logging is closed (the coach must Time In first)
 *   Time In set     -> logging is open, with or without a Time Out (timing
 *                      out does not lock anything; the coach can keep logging
 *                      or cancel the Time Out)
 *
 * Once the coach has logged any student / athlete attendance for that day,
 * they cannot cancel their Time In until that logged attendance is removed.
 *
 * The rules are enforced here on the server (not just by disabling buttons).
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

    // Former (retired) athlete role ID. Their old athlete logs still say role 4.
    private const FORMER_ATHLETE_ROLE_ID = 6;

    // attendance_state values for the "Expecting" flow (also read by the
    // skills assessment controller). The STORED value stays 'Expecting' so
    // existing rows keep working; STATE_EXPECTING_LABEL is only the wording
    // people see (the blade page has the same label in EXPECTING_LABEL).
    public const STATE_EXPECTING = 'Expecting';
    public const STATE_EXPECTING_LABEL = 'Expected';
    public const STATE_PRESENT_LATE = 'Present-Late';

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
        // Athletes who already have an athlete log on this date stay listed.
        $loggedThatDay = Attendance::ofRole(self::ATHLETE_ROLE_ID)
            ->onDate($date)
            ->select('user_id');

        // Someone who already has a STUDENT log on this date (and no athlete log)
        // stays on the student list only, so a person promoted on the same day
        // they attended as a student does not show twice.
        $athleteLogged = Attendance::ofRole(self::ATHLETE_ROLE_ID)
            ->onDate($date)
            ->pluck('user_id');

        $studentOnlyIds = Attendance::onDate($date)
            ->whereNotNull('enrollment_id')
            ->pluck('user_id')
            ->diff($athleteLogged)
            ->unique()
            ->values()
            ->all();

        $athletes = DB::table(CoachController::USERS_TABLE . ' as u')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'u.' . CoachController::USER_PK)
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->whereIn('u.' . CoachController::ROLE_COL, [self::ATHLETE_ROLE_ID, self::FORMER_ATHLETE_ROLE_ID])
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            // athlete_since NULL = promotion date unknown, so listed on every date.
            ->where(function ($q) use ($date, $loggedThatDay) {
                $roleCol = 'u.' . CoachController::ROLE_COL;

                // Current athletes: from their promotion date onward.
                $q->where(function ($cur) use ($date, $roleCol) {
                    $cur->where($roleCol, self::ATHLETE_ROLE_ID)
                        ->where(fn ($s) => $s->whereNull('u.athlete_since')->orWhereDate('u.athlete_since', '<=', $date));
                })
                // Former athletes: only the dates they were athletes, up to the day they retired.
                ->orWhere(function ($old) use ($date, $roleCol) {
                    $old->where($roleCol, self::FORMER_ATHLETE_ROLE_ID)
                        ->where(fn ($s) => $s->whereNull('u.athlete_since')->orWhereDate('u.athlete_since', '<=', $date))
                        ->whereDate('u.athlete_until', '>=', $date);
                })
                // Anyone who has an athlete log on this date.
                ->orWhereIn('u.' . CoachController::USER_PK, $loggedThatDay);
            })
            ->when($studentOnlyIds, fn ($q) => $q->whereNotIn('u.' . CoachController::USER_PK, $studentOnlyIds))
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
                // No attendance row yet = no status (it used to say "Absent").
                'state' => $log
                    ? ($log->attendance_state ?: ($log->time_in ? Attendance::STATE_PRESENT : 'Absent'))
                    : '',
            ];
        })->values();
    }

    /** The log history of one athlete (attendance rows saved with the athlete role). */
    private function athleteLogs(int $athleteId): JsonResponse
    {
        $isAthlete = DB::table(CoachController::USERS_TABLE)
            ->where(CoachController::USER_PK, $athleteId)
            ->whereIn(CoachController::ROLE_COL, [self::ATHLETE_ROLE_ID, self::FORMER_ATHLETE_ROLE_ID])
            ->exists();

        if (!$isAthlete) {
            return $this->fail('Athlete not found.', 404);
        }

        $logs = Attendance::ofRole(self::ATHLETE_ROLE_ID)
            ->forUser($athleteId)
            ->orderByDesc('attendance_at')
            ->orderByDesc('time_in')
            ->get();

        $loggers = $this->loggerNames($logs->pluck('logged_by')->merge($logs->pluck('last_edited_by')));

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
                'last_edited_by' => $log->last_edited_by ? ($loggers->get((int) $log->last_edited_by) ?? null) : null,
                'last_edited_at' => $log->last_edited_at,
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
            return 'You need to Time In first before you can log attendance.';
        }

        return null;
    }

    /* =========================================================
     * The coach's OWN Time In / Time Out
     *
     * POST /coach/attendance/self/time-in
     * POST /coach/attendance/self/time-out
     * POST /coach/attendance/self/cancel-time-in
     * POST /coach/attendance/self/cancel-time-out
     *
     * No date or time is accepted from the browser: the server uses its own
     * clock and only ever for today. The admin edits coach times from the
     * admin attendance page.
     * ========================================================= */

    /** Whether this coach has logged any student / athlete attendance on $date. */
    private function hasLoggedAttendance(int $coachId, string $date): bool
    {
        return Attendance::onDate($date)
            ->where('logged_by', $coachId)
            ->where('role_id', '!=', CoachController::ROLE_COACH)
            ->exists();
    }

    /**
     * The coach's row for $date, locked for update. A student log being saved
     * holds a shared lock on this row (see coachRow()), so Time Out / Cancel
     * wait for it to finish and then see the attendance it just wrote.
     */
    private function lockedCoachRow(int $coachId, string $date): ?Attendance
    {
        return Attendance::ofRole(CoachController::ROLE_COACH)
            ->forUser($coachId)
            ->onDate($date)
            ->earliestFirst()
            ->lockForUpdate()
            ->first();
    }

    private function guardSelfService(Request $request): ?JsonResponse
    {
        if ($denied = $this->denyUnlessCoach($request)) {
            return $denied;
        }

        if (!$this->isActiveCoach((int) Auth::id())) {
            return $this->fail('Your account is not active.', 403);
        }

        return null;
    }

    /** "08:05" -> "8:05 AM" */
    private function clockLabel(string $hhmm): string
    {
        return Carbon::createFromFormat('H:i', $hhmm)->format('g:i A');
    }

    public function selfTimeIn(Request $request): JsonResponse
    {
        if ($denied = $this->guardSelfService($request)) {
            return $denied;
        }

        $coachId = (int) Auth::id();
        $now = Carbon::now();
        $date = $now->toDateString();
        $time = $now->format('H:i');

        return DB::transaction(function () use ($coachId, $date, $time) {
            $row = $this->lockedCoachRow($coachId, $date);

            if ($row && $row->time_in) {
                return $this->fail('You are already timed in for today.');
            }

            if ($row) {
                // The row already exists (e.g. the admin marked them Absent): keep
                // logged_by and record this edit as the last one.
                $row->update([
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                ] + Attendance::editStamp($coachId));
            } else {
                Attendance::create([
                    'user_id' => $coachId,
                    'role_id' => CoachController::ROLE_COACH,
                    'attendance_at' => $date,
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                    'logged_by' => $coachId,
                    'enrollment_id' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'You timed in at ' . $this->clockLabel($time) . '.',
            ]);
        });
    }

    public function selfTimeOut(Request $request): JsonResponse
    {
        if ($denied = $this->guardSelfService($request)) {
            return $denied;
        }

        $coachId = (int) Auth::id();
        $now = Carbon::now();
        $date = $now->toDateString();
        $time = $now->format('H:i');

        return DB::transaction(function () use ($coachId, $date, $time) {
            $row = $this->lockedCoachRow($coachId, $date);

            if (!$row || !$row->time_in) {
                return $this->fail('You have not timed in yet.');
            }

            if ($row->time_out) {
                return $this->fail('You are already timed out for today.');
            }

            if ($time <= $this->hhmm($row->time_in)) {
                return $this->fail('Please wait a minute after timing in before you time out.');
            }

            $row->update(['time_out' => $time] + Attendance::editStamp($coachId));

            return response()->json([
                'success' => true,
                'message' => 'You timed out at ' . $this->clockLabel($time) . '.',
            ]);
        });
    }

    /**
     * Undo the coach's own Time In. Not allowed once they have logged any
     * student / athlete attendance today (remove that first), or while a Time
     * Out is set (cancel the Time Out first).
     */
    public function selfCancelTimeIn(Request $request): JsonResponse
    {
        if ($denied = $this->guardSelfService($request)) {
            return $denied;
        }

        $coachId = (int) Auth::id();
        $date = Carbon::now()->toDateString();

        return DB::transaction(function () use ($coachId, $date) {
            $row = $this->lockedCoachRow($coachId, $date);

            if (!$row || !$row->time_in) {
                return $this->fail('You have not timed in yet.');
            }

            if ($row->time_out) {
                return $this->fail('Cancel your Time Out first if you need to cancel your Time In.', 403);
            }

            if ($this->hasLoggedAttendance($coachId, $date)) {
                return $this->fail('You have already logged attendance today. Remove it first to cancel your Time In.', 403);
            }

            $row->delete();

            return response()->json(['success' => true, 'message' => 'Your Time In was cancelled.']);
        });
    }

    /** Undo the coach's own Time Out so they are timed in again (they can time out later). */
    public function selfCancelTimeOut(Request $request): JsonResponse
    {
        if ($denied = $this->guardSelfService($request)) {
            return $denied;
        }

        $coachId = (int) Auth::id();
        $date = Carbon::now()->toDateString();

        return DB::transaction(function () use ($coachId, $date) {
            $row = $this->lockedCoachRow($coachId, $date);

            if (!$row || !$row->time_in || !$row->time_out) {
                return $this->fail('You have not timed out today.');
            }

            $row->update(['time_out' => null] + Attendance::editStamp($coachId));

            return response()->json(['success' => true, 'message' => 'Your Time Out was cancelled.']);
        });
    }

    /* =========================================================
     * GET /coach/attendance/my-logs?month=YYYY-MM
     * The coach's OWN attendance (view only) for one month, newest first,
     * with the month's totals. Defaults to the current month.
     * ========================================================= */
    public function myLogs(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessCoach($request)) {
            return $denied;
        }

        $v = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $coachId = (int) Auth::id();
        $month = $v['month'] ?? Carbon::now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

        $logs = Attendance::ofRole(CoachController::ROLE_COACH)
            ->forUser($coachId)
            ->whereBetween('attendance_at', [
                $start->toDateTimeString(),
                $start->copy()->endOfMonth()->toDateTimeString(),
            ])
            ->orderByDesc('attendance_at')
            ->orderByDesc('time_in')
            ->get(['attendance_at', 'time_in', 'time_out', 'attendance_state', 'logged_by', 'last_edited_by', 'last_edited_at']);

        $loggers = $this->loggerNames($logs->pluck('logged_by')->merge($logs->pluck('last_edited_by')));

        return response()->json([
            'success' => true,
            'logs' => $logs->map(fn ($log) => [
                'date' => Carbon::parse($log->attendance_at)->toDateString(),
                'time_in' => $this->hhmm($log->time_in),
                'time_out' => $this->hhmm($log->time_out),
                'state' => $log->attendance_state ?: ($log->time_in ? Attendance::STATE_PRESENT : 'Absent'),
                // Time Out minus Time In, when both exist.
                'worked' => $log->time_in && $log->time_out
                    ? $this->myHoursLabel($this->myMinutesBetween($log->time_in, $log->time_out))
                    : null,
                // The coach timing themselves in is "You"; otherwise the admin's name.
                'logged_by' => (int) $log->logged_by === $coachId
                    ? 'You'
                    : ($loggers->get((int) $log->logged_by) ?? '--'),
                // Whoever changed the row last (the admin, or the coach themselves).
                'last_edited_by' => $log->last_edited_by
                    ? ((int) $log->last_edited_by === $coachId
                        ? 'You'
                        : ($loggers->get((int) $log->last_edited_by) ?? '--'))
                    : null,
                'last_edited_at' => $log->last_edited_at,
            ])->values(),
            'summary' => $this->myMonthTotals($logs),
        ]);
    }

    /** Days present, worked time and days missing a Time Out for a set of the coach's own logs. */
    private function myMonthTotals($logs): array
    {
        $days = [];
        $minutes = 0;
        $incomplete = 0;

        foreach ($logs as $log) {
            if (!$log->time_in) {
                continue;
            }

            $days[Carbon::parse($log->attendance_at)->toDateString()] = true;

            if ($log->time_out) {
                $minutes += $this->myMinutesBetween($log->time_in, $log->time_out);
            } else {
                $incomplete++;
            }
        }

        return [
            'days' => count($days),
            'hours_label' => $this->myHoursLabel($minutes),
            'incomplete' => $incomplete,
        ];
    }

    private function myMinutesBetween($timeIn, $timeOut): int
    {
        $in = $this->hhmm($timeIn);
        $out = $this->hhmm($timeOut);

        if (!$in || !$out) {
            return 0;
        }

        [$ih, $im] = array_map('intval', explode(':', $in));
        [$oh, $om] = array_map('intval', explode(':', $out));

        return max(0, ($oh * 60 + $om) - ($ih * 60 + $im));
    }

    /** 135 -> "2 h 15 m" */
    private function myHoursLabel(int $minutes): string
    {
        return intdiv($minutes, 60) . ' h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . ' m';
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

        // The coach's own Time In / Out only ever applies to the server's today.
        $isToday = $v['date'] === Carbon::now()->toDateString();
        $isActive = $this->isActiveCoach($coachId);
        $hasLogs = $isToday && $state === 'in' && $this->hasLoggedAttendance($coachId, $v['date']);

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'session' => [
                'state' => $state,
                'time_in' => $this->hhmm($row->time_in ?? null),
                'time_out' => $this->hhmm($row->time_out ?? null),
                'can_time_in' => $isActive && $isToday && $state === 'not_in',
                'can_time_out' => $isActive && $isToday && $state === 'in',
                'can_cancel_in' => $isActive && $isToday && $state === 'in' && !$hasLogs,
                'can_cancel_out' => $isActive && $isToday && $state === 'out',
                'has_logs' => $hasLogs,
            ],
            'can_log' => $reason === null,
            'block_reason' => $reason,
            'rows' => $this->rowsFor($v['date'], $courseId, $courseIds, $onlyAthletes, $handlesAthletes, $coachId),
        ]);
    }

    /**
     * Students of the coach's classes, then the athletes (when this coach
     * handles them). A course filter shows only that course; the "Athletes"
     * filter shows only athletes.
     */
    private function rowsFor(string $date, ?int $courseId, array $courseIds, bool $onlyAthletes, bool $handlesAthletes, int $coachId): Collection
    {
        $students = $onlyAthletes ? collect() : collect($this->studentRows($date, $courseId, $courseIds));

        $athletes = ($handlesAthletes && ($onlyAthletes || $courseId === null))
            ? $this->athleteRows($date)
            : collect();

        // Which of these did THIS coach log that day? (the page's "Only my logs" filter)
        $mine = Attendance::onDate($date)
            ->where('logged_by', $coachId)
            ->get(['enrollment_id', 'user_id', 'role_id']);

        $myEnrollmentIds = $mine->pluck('enrollment_id')->filter()->map(fn ($id) => (int) $id)->flip();
        $myAthleteIds = $mine->where('role_id', self::ATHLETE_ROLE_ID)->pluck('user_id')->map(fn ($id) => (int) $id)->flip();

        return $students->concat($athletes)->map(function ($r) use ($myEnrollmentIds, $myAthleteIds) {
            $r = (array) $r;
            $r['logged_by_me'] = ($r['kind'] ?? '') === 'athlete'
                ? $myAthleteIds->has((int) ($r['user_id'] ?? 0))
                : $myEnrollmentIds->has((int) ($r['id'] ?? 0));

            return $r;
        })->values();
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

    /** POST /coach/attendance/students/absent { enrollment_id, date, remove? } */
    public function studentAbsent(Request $request): JsonResponse
    {
        return $this->recordStudentTime($request, 'absent');
    }

    /**
     * POST /coach/attendance/students/expecting   { enrollment_id, date, remove? }
     * Marks a student or athlete as "Expecting": attendance_state only, no Time In / Out.
     * The student then shows up on the coach's Skills Assessment page.
     */
    public function studentExpecting(Request $request): JsonResponse
    {
        return $this->recordStudentTime($request, 'expecting');
    }

    /**
     * POST /coach/attendance/students/time-in-late   { enrollment_id, date, time }
     * Only for a student this coach marked "Expecting"; becomes "Present-Late".
     */
    public function studentTimeInLate(Request $request): JsonResponse
    {
        return $this->recordStudentTime($request, 'late');
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
            'remove' => ['sometimes', 'boolean'],
            // No time is needed for Expecting / Absent, or when removing a time.
            'time' => [
                in_array($kind, ['expecting', 'absent'], true) || $request->boolean('remove') ? 'nullable' : 'required',
                'date_format:H:i',
            ],
        ], [
            'time.date_format' => 'Please enter a valid time.',
            'date.before_or_equal' => 'You cannot record attendance for a future date.',
        ]);

        $coachId = (int) Auth::id();

        // "remove" applies to Time In / Time Out (clears the time) and to
        // Expected / Absent (clears the mark).
        $v['remove'] = in_array($kind, ['in', 'out', 'expecting', 'absent'], true) && (bool) ($v['remove'] ?? false);

        if (!empty($v['athlete_id'])) {
            if ($kind === 'late') {
                return $this->fail('Time In (Late) is only available for enrolled students.');
            }

            return $this->recordAthleteTime($v, $kind, $coachId);
        }

        // A graduated enrollment is closed: say so instead of "not found".
        if (DB::table(Attendance::ENROLLMENTS_TABLE)
            ->where('enrollment_id', $v['enrollment_id'])
            ->whereNotNull('graduation_at')
            ->exists()) {
            return $this->fail('This student has already graduated, so their attendance can no longer be changed.', 422);
        }

        // A dropped student is closed for the coach too: no logging, marking or removing.
        if (DB::table(Attendance::ENROLLMENTS_TABLE)
            ->where('enrollment_id', $v['enrollment_id'])
            ->whereNotNull('dropped_at')
            ->exists()) {
            return $this->fail('This student has been dropped, so attendance can no longer be logged.', 422);
        }

        // The enrollment must be an active one, of an Active user, in one of
        // this coach's classes.
        $enrollment = DB::table(Attendance::ENROLLMENTS_TABLE . ' as e')
            ->join(CoachController::USERS_TABLE . ' as u', 'u.' . CoachController::USER_PK, '=', 'e.user_id')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->where('e.enrollment_id', $v['enrollment_id'])
            ->whereNull('e.graduation_at')
            ->whereNull('e.dropped_at')
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

            $time = $v['time'] ?? null;   // null only for "expecting"

            $row = Attendance::forEnrollment((int) $enrollment->enrollment_id)
                ->onDate($v['date'])
                ->earliestFirst()
                ->lockForUpdate()
                ->first();

            // Expected / Absent: add or remove the mark (no times).
            if (in_array($kind, ['expecting', 'absent'], true)) {
                return $this->applyMark($row, $kind, $v['remove'], $coachId, [
                    'user_id' => $enrollment->user_id,
                    'role_id' => $enrollment->role_id,
                    'attendance_at' => $v['date'],
                    'enrollment_id' => $enrollment->enrollment_id,
                ]);
            }

            // Time In (Late): only for a student this coach marked Expecting.
            if ($kind === 'late') {
                if (!$row || $row->attendance_state !== self::STATE_EXPECTING) {
                    return $this->fail('Only students marked as ' . self::STATE_EXPECTING_LABEL . ' can be timed in late.');
                }

                if ($row->time_in) {
                    return $this->fail('This student already has a Time In for that date.');
                }

                if ((int) $row->logged_by !== $coachId) {
                    return $this->fail('This student was marked as ' . self::STATE_EXPECTING_LABEL . ' by another coach.', 403);
                }

                $row->update([
                    'time_in' => $time,
                    'attendance_state' => self::STATE_PRESENT_LATE,
                ]);

                return response()->json(['success' => true, 'message' => 'Late Time In recorded.']);
            }

            // Time In / Time Out: add, edit or remove.
            return $this->applyInOut($row, $kind, $v['remove'], $time, $coachId, [
                'user_id' => $enrollment->user_id,
                'role_id' => $enrollment->role_id,
                'attendance_at' => $v['date'],
                'enrollment_id' => $enrollment->enrollment_id,
            ], 'student');
        });
    }

    /**
     * Shared by students and athletes. Marks someone Expected or Absent, or
     * removes that mark again. A mark row holds no times, so removing deletes
     * it, and only the coach who set it can remove it.
     *
     * @param array $createAttrs attributes for a brand new attendance row
     */
    private function applyMark(?Attendance $row, string $kind, bool $remove, int $coachId, array $createAttrs): JsonResponse
    {
        $state = $kind === 'absent' ? 'Absent' : self::STATE_EXPECTING;
        $label = $kind === 'absent' ? 'Absent' : self::STATE_EXPECTING_LABEL;

        if ($remove) {
            if (!$row || $row->attendance_state !== $state) {
                return $this->fail('Not marked as ' . $label . '.');
            }

            if ($row->time_in) {
                return $this->fail('A Time In is already recorded for that date.');
            }

            if ((int) $row->logged_by !== $coachId) {
                return $this->fail('This was marked as ' . $label . ' by another coach.', 403);
            }

            $row->delete();

            return response()->json(['success' => true, 'message' => $label . ' removed.']);
        }

        if ($row && $row->time_in) {
            return $this->fail($kind === 'absent'
                ? 'Someone who has timed in cannot be marked absent.'
                : 'A Time In is already recorded for that date.');
        }

        if ($row && $row->attendance_state === $state) {
            return $this->fail('Already marked as ' . $label . '.');
        }

        if ($row) {
            $row->update(['attendance_state' => $state, 'logged_by' => $coachId]);
        } else {
            Attendance::create($createAttrs + ['attendance_state' => $state, 'logged_by' => $coachId]);
        }

        return response()->json(['success' => true, 'message' => 'Marked as ' . $label . '.']);
    }

    /**
     * Shared by students and athletes. Adds a Time In / Time Out, overwrites
     * one that already exists (edit), or clears it when $remove is true.
     *
     * @param array $createAttrs attributes for a brand new attendance row
     */
    private function applyInOut(?Attendance $row, string $kind, bool $remove, ?string $time, int $coachId, array $createAttrs, string $who): JsonResponse
    {
        /* ---------------- Time In ---------------- */
        if ($kind === 'in') {
            if ($remove) {
                if (!$row || !$row->time_in) {
                    return $this->fail("This $who has no Time In to remove.");
                }

                if ($row->time_out) {
                    return $this->fail('Remove the Time Out first before removing the Time In.');
                }

                if ($row->attendance_state === self::STATE_PRESENT_LATE) {
                    // Back to what the coach set before the late Time In.
                    $row->update([
                        'time_in' => null,
                        'attendance_state' => self::STATE_EXPECTING,
                    ]);
                } else {
                    // Nothing left to keep for that day.
                    $row->delete();
                }

                return response()->json(['success' => true, 'message' => 'Time In removed.']);
            }

            if ($row && $row->time_out && $time >= $this->hhmm($row->time_out)) {
                return $this->fail('Time In must be earlier than Time Out.');
            }

            if ($row && $row->time_in) {
                // Edit: only the time changes (Present-Late stays Present-Late).
                $row->update([
                    'time_in' => $time,
                    'logged_by' => $coachId,
                ]);

                return response()->json(['success' => true, 'message' => 'Time In saved.']);
            }

            if ($row) {
                $row->update([
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                    'logged_by' => $coachId,
                ]);
            } else {
                Attendance::create($createAttrs + [
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                    'logged_by' => $coachId,
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Time In saved.']);
        }

        /* ---------------- Time Out ---------------- */
        if (!$row || !$row->time_in) {
            return $this->fail('Record the Time In first.');
        }

        if ($remove) {
            if (!$row->time_out) {
                return $this->fail("This $who has no Time Out to remove.");
            }

            $row->update(['time_out' => null]);

            return response()->json(['success' => true, 'message' => 'Time Out removed.']);
        }

        if ($time <= $this->hhmm($row->time_in)) {
            return $this->fail('Time Out must be later than Time In.');
        }

        $row->update(['time_out' => $time]);

        return response()->json(['success' => true, 'message' => 'Time Out saved.']);
    }

    /**
     * Same rules as a student log (the coach must be timed in), but for an
     * athlete: the row is
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
            ->whereIn('u.' . CoachController::ROLE_COL, [self::ATHLETE_ROLE_ID, self::FORMER_ATHLETE_ROLE_ID])
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

            $time = $v['time'] ?? null;   // null when removing a time

            $row = Attendance::ofRole(self::ATHLETE_ROLE_ID)
                ->forUser($athleteId)
                ->onDate($v['date'])
                ->earliestFirst()
                ->lockForUpdate()
                ->first();

            if (in_array($kind, ['expecting', 'absent'], true)) {
                return $this->applyMark($row, $kind, (bool) ($v['remove'] ?? false), $coachId, [
                    'user_id' => $athleteId,
                    'role_id' => self::ATHLETE_ROLE_ID,
                    'attendance_at' => $v['date'],
                    'enrollment_id' => null,
                ]);
            }

            return $this->applyInOut($row, $kind, (bool) ($v['remove'] ?? false), $time, $coachId, [
                'user_id' => $athleteId,
                'role_id' => self::ATHLETE_ROLE_ID,
                'attendance_at' => $v['date'],
                'enrollment_id' => null,
            ], 'athlete');
        });
    }
}