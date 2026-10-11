<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Controllers\CoachController;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Attendance queries and small helpers shared by the admin and coach
 * attendance controllers, so both pages read the same data the same way.
 *
 * Every student query takes an optional $limitToCourseIds: null means
 * "no limit" (admin); an array limits everything to those courses (coach).
 */
trait HandlesAttendanceData
{
    /* =========================================================
     * Students: ACTIVE ENROLLMENTS (tbl_enrollments, not yet graduated)
     * of Active users, with what was logged against each enrollment on
     * the chosen date. One row per enrollment.
     *
     *  - Course is read live from the enrollment, so changing an
     *    enrollment's course never requires touching the attendance logs.
     *  - "Sessions completed" = attended sessions (rows with a time_in)
     *    logged against the enrollment_id, out of the course's session count.
     *  - "Coach" is whoever logged the attendance (logged_by).
     * ========================================================= */
    protected function studentRows(string $date, ?int $courseId, ?array $limitToCourseIds = null): array
    {
        if ($limitToCourseIds !== null && !$limitToCourseIds) {
            return [];
        }

        $users = CoachController::USERS_TABLE;
        $pk = CoachController::USER_PK;

        $query = DB::table(Attendance::ENROLLMENTS_TABLE . ' as e')
            ->join("$users as u", "u.$pk", '=', 'e.user_id')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->whereNull('e.graduation_at')
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active');

        if ($limitToCourseIds !== null) {
            $query->whereIn('e.course_id', $limitToCourseIds);
        }

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
        $completed = Attendance::forEnrollment($ids)
            ->attended()
            ->groupBy('enrollment_id')
            ->selectRaw('enrollment_id, COUNT(*) as total')
            ->pluck('total', 'enrollment_id');

        // What was logged on the chosen date.
        $logs = Attendance::forEnrollment($ids)
            ->onDate($date)
            ->earliestFirst()
            ->get(['enrollment_id', 'time_in', 'time_out', 'attendance_state', 'logged_by'])
            ->groupBy('enrollment_id');

        $loggerNames = $this->loggerNames($logs->flatten(1)->pluck('logged_by'));

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
     * Every attendance log of the student that an enrollment belongs to.
     * The filter narrows it to one of the student's enrollments (so logs
     * from courses they used to be enrolled in can be reviewed too);
     * "all" also includes older logs that have no enrollment_id yet.
     *
     * With $limitToCourseIds (coach), only enrollments in those courses
     * are visible, and "all" never includes logs without an enrollment.
     * ========================================================= */
    protected function studentLogsPayload(int $enrollmentId, ?string $filter, ?array $limitToCourseIds = null): JsonResponse
    {
        $anchorQuery = DB::table(Attendance::ENROLLMENTS_TABLE . ' as e')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
            ->where('e.enrollment_id', $enrollmentId);

        if ($limitToCourseIds !== null) {
            $anchorQuery->whereIn('e.course_id', $limitToCourseIds);
        }

        $anchor = $anchorQuery->first(['e.user_id', 'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix']);

        if (!$anchor) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        $enrollmentQuery = DB::table(Attendance::ENROLLMENTS_TABLE . ' as e')
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->where('e.user_id', $anchor->user_id);

        if ($limitToCourseIds !== null) {
            $enrollmentQuery->whereIn('e.course_id', $limitToCourseIds);
        }

        $enrollments = $enrollmentQuery
            ->orderByDesc('e.enrollment_at')
            ->get([
                'e.enrollment_id as id',
                'c.' . CoachController::COURSE_NAME_COL . ' as course_name',
                'e.enrollment_at',
                'e.graduation_at',
            ]);

        $filter = $filter ?? (string) $enrollmentId;

        if ($filter !== 'all' && !$enrollments->contains(fn ($e) => (int) $e->id === (int) $filter)) {
            return $this->fail('That enrollment does not belong to this student.');
        }

        $query = Attendance::query()
            ->from((new Attendance())->getTable() . ' as a')
            ->leftJoin(Attendance::ENROLLMENTS_TABLE . ' as e', 'e.enrollment_id', '=', 'a.enrollment_id')
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->where('a.user_id', $anchor->user_id);

        if ($filter !== 'all') {
            $query->where('a.enrollment_id', (int) $filter);
        } elseif ($limitToCourseIds !== null) {
            $query->whereIn('a.enrollment_id', $enrollments->pluck('id'));
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

        $loggerNames = $this->loggerNames($logs->pluck('logged_by'));

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

    /* ---------------------------------------------------------
     * Helpers
     * --------------------------------------------------------- */

    /** @return array<int, string> user_id => "Last, First M." */
    protected function loggerNames(Collection $userIds): array
    {
        $userIds = $userIds->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        return DB::table('tbl_user_profile')
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'first_name', 'middle_name', 'last_name', 'name_suffix'])
            ->mapWithKeys(fn ($u) => [$u->user_id => $this->formatName($u)])
            ->all();
    }

    protected function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    /** "Last, First M. Suffix" — same style as the Graduation page. */
    protected function formatName(object $r): string
    {
        $middle = trim((string) ($r->middle_name ?? ''));
        $initial = $middle !== '' ? mb_substr($middle, 0, 1) . '.' : '';
        $given = trim(trim((string) $r->first_name . ' ' . $initial) . ' ' . trim((string) ($r->name_suffix ?? '')));
        $last = trim((string) $r->last_name);

        return $last !== '' ? $last . ', ' . $given : $given;
    }

    /** TIME value ("08:05:00") -> "HH:MM" (24h), or null. */
    protected function hhmm($value): ?string
    {
        return Attendance::hhmm($value);
    }
}