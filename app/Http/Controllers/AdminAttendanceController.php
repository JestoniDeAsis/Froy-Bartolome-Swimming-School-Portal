<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesAttendanceData;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Admin > Attendance.
 *   - Coaches tab: records, edits or removes each coach's Time In / Time Out,
 *     shows their logs, and has a Monthly view (days present and total hours)
 *     that can be used as a base for salary.
 *   - Students tab and Athletes tab (role_id = 4): the admin can do everything a
 *     coach can (Time In / Time Out and Absent, each added, edited or removed) with no class or coach restrictions: see record().
 *
 * Who is recorded on a row:
 *   - logged_by: whoever first logged it. For a student / athlete row created
 *     by the admin this is the coach chosen in the dropdown (only coaches who
 *     can handle that student's course, or who handle athletes). For a coach's
 *     own row created by the admin it is the admin.
 *   - last_edited_by / last_edited_at: whoever touched the row last and when
 *     (see Attendance::editStamp()). Every admin edit sets it; logged_by is
 *     left alone once the row exists.
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

    // Former (retired) athlete role ID. Their old athlete logs still say role 4.
    private const ROLE_FORMER_ATHLETE = 6;

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
            // Coaches tab, Monthly view: totals for the whole month instead of one day.
            'month' => ['nullable', 'date_format:Y-m'],
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
            $rows = !empty($v['month'])
                ? $this->coachMonthlyRows($v['month'], $courseId)
                : $this->coachRows($v['date'], $courseId);
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
                'u.handles_athletes',
            ]);

        if ($coaches->isEmpty()) {
            return [];
        }

        $ids = $coaches->pluck('id');
        $totalCourses = DB::table(CoachController::COURSES_TABLE)->count();

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
                'logged_by',
                'last_edited_by',
                'last_edited_at',
            ])
            ->groupBy('user_id');

        $names = $this->loggerNames(
            $logs->flatten(1)->pluck('logged_by')->merge($logs->flatten(1)->pluck('last_edited_by'))
        );

        return $coaches->map(function ($c) use ($assigned, $logs, $names, $totalCourses) {
            $log = ($logs->get($c->id) ?? collect())->first();

            return [
                'id' => (int) $c->id,
                'name' => $this->formatName($c),
                'classes' => $this->classesLabel(
                    ($assigned->get($c->id) ?? collect())->pluck('name'),
                    (bool) $c->handles_athletes,
                    $totalCourses
                ),
                'time_in' => $this->hhmm($log->time_in ?? null),
                'time_out' => $this->hhmm($log->time_out ?? null),
                'state' => $log->attendance_state ?? null,
                'logged_by' => $log ? ($names[$log->logged_by] ?? null) : null,
                'last_edited_by' => $log ? ($names[$log->last_edited_by] ?? null) : null,
                'last_edited_at' => $log->last_edited_at ?? null,
            ];
        })->values()->all();
    }

    /**
     * "Assigned Classes" text: the course names plus "Athletes" when the coach
     * handles athletes. Every course + athletes -> "Handles all"; every course
     * without athletes -> "All classes".
     */
    private function classesLabel(Collection $courseNames, bool $handlesAthletes, int $totalCourses): string
    {
        $courseNames = $courseNames->unique()->values();
        $hasAllCourses = $totalCourses > 0 && $courseNames->count() >= $totalCourses;

        if ($hasAllCourses) {
            return $handlesAthletes ? 'Handles all' : 'All classes';
        }

        if ($handlesAthletes) {
            $courseNames->push('Athletes');
        }

        return $courseNames->implode(', ');
    }

    /* =========================================================
     * Coaches, Monthly view: one row per Active coach with the month's
     * totals. Hours = Time Out minus Time In of every day that has both;
     * a day with a Time In but no Time Out is counted as present but
     * listed in "incomplete" (its hours cannot be known).
     * ========================================================= */
    private function coachMonthlyRows(string $month, ?int $courseId): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Same coaches (and assigned classes) as the daily list.
        $coaches = $this->coachRows($start->toDateString(), $courseId);

        if (!$coaches) {
            return [];
        }

        $logs = Attendance::ofRole(CoachController::ROLE_COACH)
            ->forUser(collect(array_column($coaches, 'id')))
            ->whereBetween('attendance_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->get(['user_id', 'attendance_at', 'time_in', 'time_out'])
            ->groupBy('user_id');

        return array_map(function ($c) use ($logs) {
            $t = $this->monthTotals($logs->get($c['id']) ?? collect());

            return [
                'id' => $c['id'],
                'name' => $c['name'],
                'classes' => $c['classes'],
                'days' => $t['days'],
                'hours' => $t['hours'],
                'hours_label' => $t['hours_label'],
                'incomplete' => $t['incomplete'],
            ];
        }, $coaches);
    }

    /** Days present, worked time and days missing a Time Out for a set of one coach's logs. */
    private function monthTotals($logs): array
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
                $minutes += $this->minutesBetween($log->time_in, $log->time_out);
            } else {
                $incomplete++;
            }
        }

        return [
            'days' => count($days),
            'minutes' => $minutes,
            'hours' => round($minutes / 60, 2),
            'hours_label' => $this->hoursLabel($minutes),
            'incomplete' => $incomplete,
        ];
    }

    private function minutesBetween($timeIn, $timeOut): int
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
    private function hoursLabel(int $minutes): string
    {
        return intdiv($minutes, 60) . ' h ' . str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) . ' m';
    }

    /* =========================================================
     * Athletes: users with role_id = 4 (and role 6, Former Athlete, on the
     * dates they were athletes) and their attendance
     * for the chosen date. This tab is read-only.
     * ========================================================= */
    private function athleteRows(string $date): array
    {
        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        // Athletes who already have an athlete log on this date stay listed.
        $loggedThatDay = Attendance::ofRole(self::ROLE_ATHLETE)
            ->onDate($date)
            ->select('user_id');

        // Someone who already has a STUDENT log on this date (and no athlete log)
        // stays on the Students tab only, so a person promoted on the same day
        // they attended as a student does not show in both tabs.
        $athleteLogged = Attendance::ofRole(self::ROLE_ATHLETE)
            ->onDate($date)
            ->pluck('user_id');

        $studentOnlyIds = Attendance::onDate($date)
            ->whereNotNull('enrollment_id')
            ->pluck('user_id')
            ->diff($athleteLogged)
            ->unique()
            ->values()
            ->all();

        $roleCol = 'u.' . CoachController::ROLE_COL;

        // athlete_since NULL = promotion date unknown, so listed on every date.
        $athletes = DB::table("$users as u")
            ->join('tbl_user_profile as p', 'p.user_id', '=', "u.$pk")
            ->whereIn($roleCol, [self::ROLE_ATHLETE, self::ROLE_FORMER_ATHLETE])
            ->where(function ($q) use ($date, $pk, $roleCol, $loggedThatDay) {
                // Current athletes: from their promotion date onward.
                $q->where(function ($cur) use ($date, $roleCol) {
                    $cur->where($roleCol, self::ROLE_ATHLETE)
                        ->where(fn ($s) => $s->whereNull('u.athlete_since')->orWhereDate('u.athlete_since', '<=', $date));
                })
                // Former athletes: only the dates they were athletes, up to the day they retired.
                ->orWhere(function ($old) use ($date, $roleCol) {
                    $old->where($roleCol, self::ROLE_FORMER_ATHLETE)
                        ->where(fn ($s) => $s->whereNull('u.athlete_since')->orWhereDate('u.athlete_since', '<=', $date))
                        ->whereDate('u.athlete_until', '>=', $date);
                })
                // Anyone who has an athlete log on this date.
                ->orWhereIn("u.$pk", $loggedThatDay);
            })
            ->when($studentOnlyIds, fn ($q) => $q->whereNotIn("u.$pk", $studentOnlyIds))
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
                'logged_by',
                'last_edited_by',
                'last_edited_at',
            ])
            ->groupBy('user_id');

        $names = $this->loggerNames(
            $logs->flatten(1)->pluck('logged_by')->merge($logs->flatten(1)->pluck('last_edited_by'))
        );

        return $athletes->map(function ($athlete) use ($logs, $names) {
            $log = ($logs->get($athlete->id) ?? collect())->first();

            return [
                'id' => (int) $athlete->id,
                'name' => $this->formatName($athlete),
                'coach' => $log ? ($names[$log->logged_by] ?? null) : null,
                'time_in' => $this->hhmm($log->time_in ?? null),
                'time_out' => $this->hhmm($log->time_out ?? null),
                'state' => $log->attendance_state ?? null,
                'last_edited_by' => $log ? ($names[$log->last_edited_by] ?? null) : null,
                'last_edited_at' => $log->last_edited_at ?? null,
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
            // Only that month's days (and the month's totals), for the Monthly view.
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        return $this->roleLogsPayload(
            (int) $v['coach_id'],
            CoachController::ROLE_COACH,
            'Coach not found.',
            $v['month'] ?? null
        );
    }

    /**
     * Shared by the athlete and coach log endpoints: the full attendance
     * history of one user with the given role.
     */
    private function roleLogsPayload(int $userId, int $roleId, string $notFoundMessage, ?string $month = null): JsonResponse
    {
        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        $exists = DB::table($users . ' as u')
            ->where("u.$pk", $userId)
            ->whereIn('u.' . CoachController::ROLE_COL, $roleId === self::ROLE_ATHLETE
                ? [self::ROLE_ATHLETE, self::ROLE_FORMER_ATHLETE]
                : [$roleId])
            ->exists();

        if (!$exists) {
            return response()->json([
                'success' => false,
                'message' => $notFoundMessage,
            ], 404);
        }

        $attendance = Attendance::ofRole($roleId)
            ->forUser($userId)
            ->when($month, function ($q) use ($month) {
                $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

                $q->whereBetween('attendance_at', [
                    $start->toDateTimeString(),
                    $start->copy()->endOfMonth()->toDateTimeString(),
                ]);
            })
            // Newest day first (like the student logs); within a day, latest Time In first, rows without one last.
            ->orderByDesc('attendance_at')
            ->orderByRaw('time_in IS NULL')
            ->orderByDesc('time_in')
            ->get([
                'attendance_at',
                'time_in',
                'time_out',
                'attendance_state',
                'logged_by',
                'last_edited_by',
                'last_edited_at',
            ]);

        $loggerIds = $attendance
            ->pluck('logged_by')
            ->merge($attendance->pluck('last_edited_by'))
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
                'last_edited_by' => $log->last_edited_by && $loggers->get($log->last_edited_by)
                    ? $this->formatName($loggers->get($log->last_edited_by))
                    : null,
                'last_edited_at' => $log->last_edited_at,
                // Time Out minus Time In, when both exist.
                'worked' => $log->time_in && $log->time_out
                    ? $this->hoursLabel($this->minutesBetween($log->time_in, $log->time_out))
                    : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'logs' => $logs,
            'summary' => $this->monthTotals($attendance),
        ]);
    }

    /* =========================================================
     * GET /admin/attendance/handling-coaches
     *     ?enrollment_id=   (student: coaches assigned to that enrollment's course)
     *     (no enrollment_id: athlete: coaches marked "handles athletes")
     *     &date=YYYY-MM-DD  (optional: only coaches who have timed in that day are listed)
     *
     * Fills the "Coach" dropdown shown when the admin logs a student or
     * athlete who has no record yet for the date. "total" is how many coaches
     * can handle them at all, so the page can tell "nobody assigned" apart from
     * "assigned coaches have not timed in yet".
     * ========================================================= */
    public function handlingCoaches(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $v = $request->validate([
            'enrollment_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $forAthletes = empty($v['enrollment_id']);
        $courseId = null;

        if (!$forAthletes) {
            $enrollment = DB::table(Attendance::ENROLLMENTS_TABLE)
                ->where('enrollment_id', (int) $v['enrollment_id'])
                ->first(['course_id']);

            if (!$enrollment) {
                return $this->fail('Student not found.', 404);
            }

            $courseId = $enrollment->course_id ? (int) $enrollment->course_id : null;
        }

        $pk = CoachController::USER_PK;

        $coaches = ($forAthletes || $courseId)
            ? $this->eligibleCoachesQuery($courseId, $forAthletes)
                ->orderBy('p.last_name')
                ->orderBy('p.first_name')
                ->get(["u.$pk as id", 'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix'])
            : collect();

        $total = $coaches->count();

        // A coach can only log attendance for a day they have timed in.
        if (!empty($v['date']) && $coaches->isNotEmpty()) {
            $timedIn = Attendance::ofRole(CoachController::ROLE_COACH)
                ->forUser($coaches->pluck('id'))
                ->onDate($v['date'])
                ->attended()
                ->pluck('user_id')
                ->flip()
                ->all();

            $coaches = $coaches->filter(fn ($c) => isset($timedIn[$c->id]))->values();
        }

        return response()->json([
            'success' => true,
            'total' => $total,
            'coaches' => $coaches->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => $this->formatName($c),
            ])->values(),
        ]);
    }

    /**
     * Active coaches who can handle a student (assigned to $courseId) or,
     * with $forAthletes, athletes (tbl_users.handles_athletes = 1).
     */
    private function eligibleCoachesQuery(?int $courseId, bool $forAthletes)
    {
        $pk = CoachController::USER_PK;

        $query = DB::table(CoachController::USERS_TABLE . ' as u')
            ->join('tbl_user_profile as p', 'p.user_id', '=', "u.$pk")
            ->join(
                CoachController::STATUS_TABLE . ' as st',
                'st.' . CoachController::STATUS_ID_COL,
                '=',
                'u.' . CoachController::STATUS_COL
            )
            ->where('u.' . CoachController::ROLE_COL, CoachController::ROLE_COACH)
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active');

        if ($forAthletes) {
            return $query->where('u.handles_athletes', 1);
        }

        return $query->whereExists(function ($sub) use ($pk, $courseId) {
            $sub->selectRaw('1')
                ->from(CoachController::COACH_COURSES_TABLE . ' as cc')
                ->whereColumn('cc.coach_id', "u.$pk")
                ->where('cc.course_id', $courseId);
        });
    }

    private function coachCanHandle(int $coachId, ?int $courseId, bool $forAthletes): bool
    {
        if (!$forAthletes && !$courseId) {
            return false;
        }

        return $this->eligibleCoachesQuery($courseId, $forAthletes)
            ->where('u.' . CoachController::USER_PK, $coachId)
            ->exists();
    }

    /* =========================================================
     * POST /admin/attendance/records
     * { kind, enrollment_id | athlete_id, date, time?, remove?, coach_id? }
     *
     * kind: in | out | absent
     * The admin can do everything a coach can for students and athletes,
     * without the coach rules: no Time In needed first, no class ownership,
     * and any mark or time can be changed or removed.
     *
     * coach_id: REQUIRED when this creates the day's row (the student / athlete
     * has no record yet). It must be a coach who can handle that student's
     * course (or who handles athletes) and becomes "logged by". The admin is
     * stored as "last edited by", with the time. On an existing row, logged_by
     * is left alone and only last_edited_by / last_edited_at change.
     * ========================================================= */
    public function record(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $kind = (string) $request->input('kind');
        $isMark = $kind === 'absent';

        $v = $request->validate([
            'kind' => ['required', Rule::in(['in', 'out', 'absent'])],
            'enrollment_id' => ['required_without:athlete_id', 'nullable', 'integer'],
            'athlete_id' => ['required_without:enrollment_id', 'nullable', 'integer'],
            'coach_id' => ['nullable', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:' . Carbon::now()->addDay()->toDateString()],
            'remove' => ['sometimes', 'boolean'],
            // No time for Absent, or when removing a time.
            'time' => [
                $isMark || $request->boolean('remove') ? 'nullable' : 'required',
                'date_format:H:i',
            ],
        ], [
            'time.date_format' => 'Please enter a valid time.',
            'date.before_or_equal' => 'You cannot record attendance for a future date.',
        ]);

        $adminId = (int) Auth::id();
        $remove = (bool) ($v['remove'] ?? false);
        $time = $v['time'] ?? null;
        $isAthlete = !empty($v['athlete_id']);
        $courseId = null;
        $isDropped = false;

        if ($isAthlete) {
            $athleteId = (int) $v['athlete_id'];

            $exists = DB::table(CoachController::USERS_TABLE . ' as u')
                ->where('u.' . CoachController::USER_PK, $athleteId)
                ->whereIn('u.' . CoachController::ROLE_COL, [self::ROLE_ATHLETE, self::ROLE_FORMER_ATHLETE])
                ->exists();

            if (!$exists) {
                return $this->fail('Athlete not found.', 404);
            }

            $attrs = [
                'user_id' => $athleteId,
                'role_id' => self::ROLE_ATHLETE,
                'attendance_at' => $v['date'],
                'enrollment_id' => null,
            ];
            $who = 'athlete';
        } else {
            $enrollment = DB::table(Attendance::ENROLLMENTS_TABLE . ' as e')
                ->join(CoachController::USERS_TABLE . ' as u', 'u.' . CoachController::USER_PK, '=', 'e.user_id')
                ->where('e.enrollment_id', (int) $v['enrollment_id'])
                ->first(['e.enrollment_id', 'e.user_id', 'e.course_id', 'e.dropped_at', 'u.' . CoachController::ROLE_COL . ' as role_id']);

            if (!$enrollment) {
                return $this->fail('Student not found.', 404);
            }

            $courseId = $enrollment->course_id ? (int) $enrollment->course_id : null;
            $isDropped = $enrollment->dropped_at !== null;

            $attrs = [
                'user_id' => $enrollment->user_id,
                'role_id' => $enrollment->role_id,
                'attendance_at' => $v['date'],
                'enrollment_id' => $enrollment->enrollment_id,
            ];
            $who = 'student';
        }

        $coachId = isset($v['coach_id']) ? (int) $v['coach_id'] : null;

        return DB::transaction(function () use ($v, $kind, $remove, $time, $adminId, $isAthlete, $attrs, $who, $courseId, $coachId, $isDropped) {
            $row = $isAthlete
                ? Attendance::ofRole(self::ROLE_ATHLETE)
                    ->forUser((int) $v['athlete_id'])
                    ->onDate($v['date'])
                    ->earliestFirst()
                    ->lockForUpdate()
                    ->first()
                : Attendance::forEnrollment((int) $v['enrollment_id'])
                    ->onDate($v['date'])
                    ->earliestFirst()
                    ->lockForUpdate()
                    ->first();

            // A dropped student can't get new attendance rows. Rows that were
            // logged before the drop can still be edited or removed.
            if (!$row && $isDropped) {
                return $this->fail('This student has been dropped, so no new attendance can be logged. Existing logs can still be edited.', 422);
            }

            // A brand-new row needs the coach who handled this student / athlete.
            if (!$row && !$remove && $kind !== 'out') {
                if (!$coachId) {
                    return $this->fail("Choose the coach who handled this $who.");
                }

                if (!$this->coachCanHandle($coachId, $courseId, $isAthlete)) {
                    return $this->fail($isAthlete
                        ? 'That coach does not handle athletes.'
                        : 'That coach is not assigned to this student\'s course.');
                }

                // Same rule as the coach page: a coach must have timed in to log attendance.
                $coachTimedIn = Attendance::ofRole(CoachController::ROLE_COACH)
                    ->forUser($coachId)
                    ->onDate($v['date'])
                    ->attended()
                    ->exists();

                if (!$coachTimedIn) {
                    return $this->fail('That coach has not timed in on this date. Record their Time In first on the Coaches tab.');
                }
            }

            if ($kind === 'absent') {
                return $this->applyMark($row, $remove, $adminId, $attrs, $coachId);
            }

            return $this->applyInOut($row, $kind, $remove, $time, $adminId, $attrs, $who, $coachId);
        });
    }

    /**
     * Marks someone Absent, or removes that mark. A mark row holds no times,
     * so removing deletes it. A new row is logged by $coachId; an existing
     * row keeps its logged_by and only gets the last-edited stamp.
     */
    private function applyMark(?Attendance $row, bool $remove, int $adminId, array $createAttrs, ?int $coachId): JsonResponse
    {
        $state = 'Absent';

        if ($remove) {
            if (!$row || $row->attendance_state !== $state) {
                return $this->fail('Not marked as Absent.');
            }

            if ($row->time_in) {
                return $this->fail('A Time In is already recorded for that date.');
            }

            $row->delete();

            return response()->json(['success' => true, 'message' => 'Absent removed.']);
        }

        if ($row && $row->time_in) {
            return $this->fail('Someone who has timed in cannot be marked absent. Remove their Time In first.');
        }

        if ($row && $row->attendance_state === $state) {
            return $this->fail('Already marked as Absent.');
        }

        if ($row) {
            $row->update(['attendance_state' => $state] + Attendance::editStamp($adminId));
        } else {
            Attendance::create($createAttrs + [
                'attendance_state' => $state,
                'logged_by' => $coachId,
            ] + Attendance::editStamp($adminId));
        }

        return response()->json(['success' => true, 'message' => 'Marked as Absent.']);
    }

    /**
     * Adds a Time In / Time Out, overwrites one that exists (edit), or clears
     * it when $remove is true. Same rules as the coach side, minus the coach checks.
     * Every change to an existing row stamps last_edited_by / last_edited_at.
     */
    private function applyInOut(?Attendance $row, string $kind, bool $remove, ?string $time, int $adminId, array $createAttrs, string $who, ?int $coachId): JsonResponse
    {
        $stamp = Attendance::editStamp($adminId);

        /* ---------------- Time In ---------------- */
        if ($kind === 'in') {
            if ($remove) {
                if (!$row || !$row->time_in) {
                    return $this->fail("This $who has no Time In to remove.");
                }

                if ($row->time_out) {
                    return $this->fail('Remove the Time Out first before removing the Time In.');
                }

                if ($row->attendance_state === CoachAttendanceController::STATE_PRESENT_LATE) {
                    // Back to Expected, as it was before the late Time In.
                    $row->update([
                        'time_in' => null,
                        'attendance_state' => CoachAttendanceController::STATE_EXPECTING,
                    ] + $stamp);
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
                $row->update(['time_in' => $time] + $stamp);

                return response()->json(['success' => true, 'message' => 'Time In saved.']);
            }

            if ($row) {
                $row->update([
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                ] + $stamp);
            } else {
                Attendance::create($createAttrs + [
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                    'logged_by' => $coachId,
                ] + $stamp);
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

            $row->update(['time_out' => null] + $stamp);

            return response()->json(['success' => true, 'message' => 'Time Out removed.']);
        }

        if ($time <= $this->hhmm($row->time_in)) {
            return $this->fail('Time Out must be later than Time In.');
        }

        $row->update(['time_out' => $time] + $stamp);

        return response()->json(['success' => true, 'message' => 'Time Out saved.']);
    }

    /* =========================================================
     * Coaches: POST /admin/attendance/coaches/time-in | time-out | absent
     *
     * A row the admin creates is logged by the admin. After that, whoever
     * changes it last (admin here, or the coach on their own page) goes to
     * last_edited_by / last_edited_at; logged_by is never overwritten.
     * ========================================================= */
    public function coachTimeOut(Request $request): JsonResponse
    {
        return $this->recordCoachTime($request, 'out');
    }

    public function coachAbsent(Request $request): JsonResponse
    {
        return $this->recordCoachTime($request, 'absent');
    }

    /** Whether this coach logged any student / athlete attendance on $date (rows they are "logged by"). */
    private function coachHasLoggedAttendance(int $coachId, string $date): bool
    {
        return Attendance::onDate($date)
            ->where('logged_by', $coachId)
            ->where('role_id', '!=', CoachController::ROLE_COACH)
            ->exists();
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
            // Time is only needed when saving a Time In / Out; not for Absent or when removing.
            'time' => [
                Rule::requiredIf($kind !== 'absent' && !$request->boolean('remove')),
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

        $adminId = (int) Auth::id();
        $remove = (bool) ($v['remove'] ?? false);

        return DB::transaction(function () use ($v, $kind, $adminId, $remove) {
            $row = Attendance::ofRole(CoachController::ROLE_COACH)
                ->forUser((int) $v['coach_id'])
                ->onDate($v['date'])
                ->earliestFirst()
                ->lockForUpdate()
                ->first();

            $stamp = Attendance::editStamp($adminId);

            /* ---------------- Absent (mark / remove) ---------------- */
            if ($kind === 'absent') {
                if ($remove) {
                    if (!$row || $row->attendance_state !== 'Absent') {
                        return $this->fail('This coach is not marked as Absent.');
                    }

                    if ($row->time_in) {
                        return $this->fail('A Time In is already recorded for that date.');
                    }

                    $row->delete();

                    return response()->json(['success' => true, 'message' => 'Absent removed.']);
                }

                if ($row && $row->time_in) {
                    return $this->fail('A coach who has timed in cannot be marked absent. Remove their Time In first.');
                }

                if ($row && $row->attendance_state === 'Absent') {
                    return $this->fail('Already marked as Absent.');
                }

                if ($row) {
                    $row->update(['attendance_state' => 'Absent'] + $stamp);
                } else {
                    Attendance::create([
                        'user_id' => (int) $v['coach_id'],
                        'role_id' => CoachController::ROLE_COACH,
                        'attendance_at' => $v['date'],
                        'attendance_state' => 'Absent',
                        'logged_by' => $adminId,
                        'enrollment_id' => null,
                    ]);
                }

                return response()->json(['success' => true, 'message' => 'Marked as Absent.']);
            }

            /* ---------------- Time In (add / edit / remove) ---------------- */
            if ($kind === 'in') {
                if ($remove) {
                    if (!$row || !$row->time_in) {
                        return $this->fail('This coach has no Time In to remove.');
                    }

                    if ($row->time_out) {
                        return $this->fail('Remove the Time Out first before removing the Time In.');
                    }

                    // Same rule as the coach's own Cancel Time In: not while they have
                    // student / athlete attendance logged that day.
                    if ($this->coachHasLoggedAttendance((int) $v['coach_id'], $v['date'])) {
                        return $this->fail('This coach has already logged attendance on that date. Remove it first to remove their Time In.', 403);
                    }

                    // No times left, so the day's row is no longer needed.
                    $row->delete();

                    return response()->json(['success' => true, 'message' => 'Time In removed.']);
                }

                if ($row && $row->time_out && $v['time'] >= $this->hhmm($row->time_out)) {
                    return $this->fail('Time In must be earlier than Time Out.');
                }

                if ($row) {
                    // Adds or overwrites the Time In on the existing row (a mark such as Absent is replaced).
                    $row->update([
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                    ] + $stamp);
                } else {
                    Attendance::create([
                        'user_id' => (int) $v['coach_id'],
                        'role_id' => CoachController::ROLE_COACH,
                        'attendance_at' => $v['date'],
                        'time_in' => $v['time'],
                        'attendance_state' => Attendance::STATE_PRESENT,
                        'logged_by' => $adminId,
                        'enrollment_id' => null,
                    ]);
                }

                return response()->json(['success' => true, 'message' => 'Time In saved.']);
            }

            /* ---------------- Time Out (add / edit / remove) ---------------- */
            if (!$row || !$row->time_in) {
                return $this->fail('Record the Time In first.');
            }

            if ($remove) {
                if (!$row->time_out) {
                    return $this->fail('This coach has no Time Out to remove.');
                }

                $row->update(['time_out' => null] + $stamp);

                return response()->json(['success' => true, 'message' => 'Time Out removed.']);
            }

            if ($v['time'] <= $this->hhmm($row->time_in)) {
                return $this->fail('Time Out must be later than Time In.');
            }

            $row->update(['time_out' => $v['time']] + $stamp);

            return response()->json(['success' => true, 'message' => 'Time Out saved.']);
        });
    }
}