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
 *   - Coaches tab: records, edits or removes each coach's Time In / Time Out,
 *     shows their logs, and has a Monthly view (days present and total hours)
 *     that can be used as a base for salary.
 *   - Students tab and Athletes tab (role_id = 4): the admin can do everything a
 *     coach can (Time In / Time Out and Absent, each added, edited or removed) with no class or coach restrictions: see record().
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
            ->when($month, function ($q) use ($month) {
                $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();

                $q->whereBetween('attendance_at', [
                    $start->toDateTimeString(),
                    $start->copy()->endOfMonth()->toDateTimeString(),
                ]);
            })
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
     * POST /admin/attendance/records
     * { kind, enrollment_id | athlete_id, date, time?, remove? }
     *
     * kind: in | out | absent
     * The admin can do everything a coach can for students and athletes,
     * without the coach rules: no Time In needed first, no class ownership,
     * and any mark or time can be changed or removed. The row is saved with
     * the admin as "logged by".
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
        $remove = in_array($kind, ['in', 'out', 'absent'], true) && (bool) ($v['remove'] ?? false);
        $time = $v['time'] ?? null;
        $isAthlete = !empty($v['athlete_id']);

        if ($isAthlete) {
            $athleteId = (int) $v['athlete_id'];

            $exists = DB::table(CoachController::USERS_TABLE . ' as u')
                ->where('u.' . CoachController::USER_PK, $athleteId)
                ->where('u.' . CoachController::ROLE_COL, self::ROLE_ATHLETE)
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
                ->first(['e.enrollment_id', 'e.user_id', 'u.' . CoachController::ROLE_COL . ' as role_id']);

            if (!$enrollment) {
                return $this->fail('Student not found.', 404);
            }

            $attrs = [
                'user_id' => $enrollment->user_id,
                'role_id' => $enrollment->role_id,
                'attendance_at' => $v['date'],
                'enrollment_id' => $enrollment->enrollment_id,
            ];
            $who = 'student';
        }

        return DB::transaction(function () use ($v, $kind, $remove, $time, $adminId, $isAthlete, $attrs, $who) {
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

            if ($kind === 'absent') {
                return $this->applyMark($row, $kind, $remove, $adminId, $attrs);
            }

            return $this->applyInOut($row, $kind, $remove, $time, $adminId, $attrs, $who);
        });
    }

    /**
     * Marks someone Absent, or removes that mark. A mark row holds no times,
     * so removing deletes it. The admin may change any coach's mark.
     */
    private function applyMark(?Attendance $row, string $kind, bool $remove, int $adminId, array $createAttrs): JsonResponse
    {
        $state = 'Absent';
        $label = 'Absent';

        if ($remove) {
            if (!$row || $row->attendance_state !== $state) {
                return $this->fail('Not marked as ' . $label . '.');
            }

            if ($row->time_in) {
                return $this->fail('A Time In is already recorded for that date.');
            }

            $row->delete();

            return response()->json(['success' => true, 'message' => $label . ' removed.']);
        }

        if ($row && $row->time_in) {
            return $this->fail('Someone who has timed in cannot be marked absent. Remove their Time In first.');
        }

        if ($row && $row->attendance_state === $state) {
            return $this->fail('Already marked as ' . $label . '.');
        }

        if ($row) {
            $row->update(['attendance_state' => $state, 'logged_by' => $adminId]);
        } else {
            Attendance::create($createAttrs + ['attendance_state' => $state, 'logged_by' => $adminId]);
        }

        return response()->json(['success' => true, 'message' => 'Marked as ' . $label . '.']);
    }

    /**
     * Adds a Time In / Time Out, overwrites one that exists (edit), or clears
     * it when $remove is true. Same rules as the coach side, minus the coach checks.
     */
    private function applyInOut(?Attendance $row, string $kind, bool $remove, ?string $time, int $adminId, array $createAttrs, string $who): JsonResponse
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

                if ($row->attendance_state === CoachAttendanceController::STATE_PRESENT_LATE) {
                    // Back to Expected, as it was before the late Time In.
                    $row->update([
                        'time_in' => null,
                        'attendance_state' => CoachAttendanceController::STATE_EXPECTING,
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
                $row->update(['time_in' => $time, 'logged_by' => $adminId]);

                return response()->json(['success' => true, 'message' => 'Time In saved.']);
            }

            if ($row) {
                $row->update([
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                    'logged_by' => $adminId,
                ]);
            } else {
                Attendance::create($createAttrs + [
                    'time_in' => $time,
                    'attendance_state' => Attendance::STATE_PRESENT,
                    'logged_by' => $adminId,
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