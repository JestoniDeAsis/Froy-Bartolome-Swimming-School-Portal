<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data for the coach dashboard (coach_dashboard_blade).
 *
 * Same pattern as the student and admin dashboards: the page is rendered by name
 * from role_menus.php (no route), so the blade calls pageData($user) itself.
 *
 * A coach only ever sees students enrolled in the courses assigned to them in
 * tbl_coach_courses (tbl_coach_courses.coach_id = tbl_users.user_id).
 *
 * What the page answers, in the order a coach works through a day:
 *   1. Who is in my classes?                -> roster per course
 *   2. Who showed up today?                 -> checked in today
 *   3. Who is waiting on me?                -> pending assessments, ready to graduate
 *   4. Who is slipping?                     -> inactive students
 *   5. What did I do recently?              -> recent assessments (+ athlete records)
 */
class CoachDashboardController extends Controller
{
    /** Students shown in each course card before "+ N more". */
    private const ROSTER_PREVIEW = 4;

    /** Rows shown in each short list. */
    private const LIST_LIMIT = 5;

    /** An enrolled student with no attended session for this many days counts as inactive. */
    private const INACTIVE_DAYS = 14;

    public function pageData(User $user): array
    {
        $coachId = $user->getKey();
        $today   = Carbon::today();

        $profile   = $user->profile;
        $firstName = trim((string) ($profile->first_name ?? '')) ?: (string) ($user->username ?? 'Coach');

        // Assigned courses (a course with no students still gets a card).
        $courses = DB::table('tbl_coach_courses as cc')
            ->join('tbl_courses as c', 'c.course_id', '=', 'cc.course_id')
            ->where('cc.coach_id', $coachId)
            ->orderBy('c.course_name')
            ->get(['c.course_id', 'c.course_name', 'c.course_session_count']);

        $courseIds = $courses->pluck('course_id')->map(fn ($id) => (int) $id)->all();

        $students  = $this->activeStudents($courseIds);
        $checkedIn = $this->checkedInToday($courseIds, $today);
        $inIds     = $checkedIn->pluck('enrollment_id')->map(fn ($id) => (int) $id)->unique()->all();

        // ---- Who is waiting on the coach ---------------------------------------------
        $pending = $students->filter(fn ($s) => $s->total > 0 && $s->done >= $s->total && ! $s->ready)->values();
        $ready   = $students->filter(fn ($s) => $s->ready)->values();

        $cutoff   = $today->copy()->subDays(self::INACTIVE_DAYS);
        $inactive = $students->filter(function ($s) use ($cutoff) {
            if ($s->total > 0 && $s->done >= $s->total) {
                return false;   // finished the sessions; waiting on assessment, not absent
            }
            $reference = $s->last_session ?: $s->enrollment_at;
            return $reference && Carbon::parse($reference)->lt($cutoff);
        })->sortBy(fn ($s) => $s->last_session ?: $s->enrollment_at)->values();

        $athletes = $user->handles_athletes ? $this->athleteRecords($coachId) : null;

        return [
            'firstName'    => $firstName,
            'dateLabel'    => Carbon::now()->format('l, F j, Y'),
            'hasCourses'   => $courses->isNotEmpty(),
            'myAttendance' => $this->myAttendance($coachId, $today),

            'kpis' => [
                'students'  => $students->count(),
                'pending'   => $pending->count(),
                'ready'     => $ready->count(),
                'checkedIn' => count($inIds),
            ],

            'classes' => $courses->map(function ($course) use ($students, $inIds) {
                $group = $students->where('course_id', (int) $course->course_id)->sortBy('student_name')->values();

                return [
                    'name'      => $course->course_name ?: 'Course',
                    'enrolled'  => $group->count(),
                    'checkedIn' => $group->filter(fn ($s) => in_array((int) $s->enrollment_id, $inIds, true))->count(),
                    'preview'   => $group->take(self::ROSTER_PREVIEW)->map(fn ($s) => [
                        'name'     => $s->student_name,
                        'schedule' => $s->scheduled_time ?: null,
                        'done'     => $s->done,
                        'total'    => $s->total,
                        'in'       => in_array((int) $s->enrollment_id, $inIds, true),
                    ])->all(),
                    'more'      => max(0, $group->count() - self::ROSTER_PREVIEW),
                ];
            })->all(),

            'today' => [
                'total' => $checkedIn->count(),
                'rows'  => $checkedIn->take(8)->map(fn ($r) => [
                    'name'   => $r->student_name ?: 'Unknown',
                    'course' => $r->course_name ?: '-',
                    'in'     => $r->time_in ? Carbon::parse($r->time_in)->format('g:i A') : null,
                    'out'    => $r->time_out ? Carbon::parse($r->time_out)->format('g:i A') : null,
                ])->all(),
                'more'  => max(0, $checkedIn->count() - 8),
            ],

            'pending' => $pending->take(self::LIST_LIMIT)->map(fn ($s) => [
                'name'   => $s->student_name,
                'course' => $s->course_name,
                'done'   => $s->done,
                'total'  => $s->total,
            ])->all(),
            'pendingMore' => max(0, $pending->count() - self::LIST_LIMIT),

            'ready' => $ready->take(self::LIST_LIMIT)->map(fn ($s) => [
                'name'    => $s->student_name,
                'course'  => $s->course_name,
                'cleared' => $s->cleared_for_graduation_at !== null,
            ])->all(),
            'readyMore' => max(0, $ready->count() - self::LIST_LIMIT),

            'inactive' => $inactive->take(self::LIST_LIMIT)->map(fn ($s) => [
                'name'   => $s->student_name,
                'course' => $s->course_name,
                'last'   => $s->last_session ? Carbon::parse($s->last_session)->diffForHumans() : null,
            ])->all(),
            'inactiveMore' => max(0, $inactive->count() - self::LIST_LIMIT),

            'recentAssessments' => $this->recentAssessments($coachId),
            'athletes'          => $athletes,
        ];
    }

    /* =====================================================================
     |  Students in the coach's courses
     | ===================================================================== */

    /** Active (not graduated) enrollments in the given courses, with sessions attended and the assessment flag. */
    private function activeStudents(array $courseIds): Collection
    {
        if (empty($courseIds)) {
            return collect();
        }

        $sessions = DB::table('tbl_attendances')
            ->selectRaw('enrollment_id, COUNT(*) AS sessions_done, MAX(attendance_at) AS last_session')
            ->whereNotNull('enrollment_id')
            ->whereNotNull('time_in')
            ->groupBy('enrollment_id');

        return DB::table('tbl_enrollments as e')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 'e.course_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_skill_assessments as sa', 'sa.enrollment_id', '=', 'e.enrollment_id')
            ->leftJoinSub($sessions, 'att', 'att.enrollment_id', '=', 'e.enrollment_id')
            ->whereIn('e.course_id', $courseIds)
            ->whereNull('e.graduation_at')
            ->whereNull('e.dropped_at')   // a dropped student is no longer in the class
            ->select([
                'e.enrollment_id', 'e.user_id', 'e.course_id', 'e.enrollment_at',
                'e.scheduled_time', 'e.cleared_for_graduation_at',
                'c.course_name', 'c.course_session_count',
                'sa.ready_to_graduate',
                'att.sessions_done', 'att.last_session',
            ])
            ->selectRaw($this->nameSql() . ' AS student_name')
            ->get()
            ->map(function ($s) {
                $s->course_name  = $s->course_name ?: 'Course not assigned';
                $s->student_name = $s->student_name ?: 'Unknown';
                $s->total        = max(0, (int) ($s->course_session_count ?? 0));
                $s->done         = (int) ($s->sessions_done ?? 0);
                $s->ready        = (bool) $s->ready_to_graduate;

                return $s;
            });
    }

    /** Today's attendance rows (with a Time In) for students in the coach's courses, latest first. */
    private function checkedInToday(array $courseIds, Carbon $today): Collection
    {
        if (empty($courseIds)) {
            return collect();
        }

        return DB::table('tbl_attendances as a')
            ->join('tbl_enrollments as e', 'e.enrollment_id', '=', 'a.enrollment_id')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 'e.course_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'e.user_id')
            ->whereIn('e.course_id', $courseIds)
            ->whereDate('a.attendance_at', $today->toDateString())
            ->whereNotNull('a.time_in')
            ->whereNull('e.dropped_at')   // checked in earlier today, dropped since
            ->orderByDesc('a.time_in')
            ->select(['a.enrollment_id', 'a.time_in', 'a.time_out', 'c.course_name'])
            ->selectRaw($this->nameSql() . ' AS student_name')
            ->get();
    }

    /** The coach's own attendance row for today, or null if not logged yet. */
    private function myAttendance(int $coachId, Carbon $today): ?array
    {
        $row = DB::table('tbl_attendances')
            ->where('user_id', $coachId)
            ->whereDate('attendance_at', $today->toDateString())
            ->whereNotNull('time_in')
            ->orderByDesc('time_in')
            ->first(['time_in', 'time_out']);

        return $row ? [
            'in'  => Carbon::parse($row->time_in)->format('g:i A'),
            'out' => $row->time_out ? Carbon::parse($row->time_out)->format('g:i A') : null,
        ] : null;
    }

    /* =====================================================================
     |  Coach's own work
     | ===================================================================== */

    /** The latest assessments this coach saved, with how many skills were passed. */
    private function recentAssessments(int $coachId): array
    {
        $results = DB::table('tbl_skill_assessment_results')
            ->selectRaw("skill_assessment_id, COUNT(*) AS marked, SUM(result = 'pass') AS passed")
            ->groupBy('skill_assessment_id');

        return DB::table('tbl_skill_assessments as sa')
            ->join('tbl_enrollments as e', 'e.enrollment_id', '=', 'sa.enrollment_id')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 'e.course_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'e.user_id')
            ->leftJoinSub($results, 'r', 'r.skill_assessment_id', '=', 'sa.skill_assessment_id')
            ->where('sa.assessed_by', $coachId)
            ->whereNull('e.dropped_at')   // a dropped student's assessment is no longer shown
            ->orderByRaw('COALESCE(sa.updated_at, sa.assessed_at) DESC')
            ->limit(self::LIST_LIMIT)
            ->select(['sa.ready_to_graduate', 'sa.assessed_at', 'sa.updated_at', 'c.course_name', 'r.marked', 'r.passed'])
            ->selectRaw($this->nameSql() . ' AS student_name')
            ->get()
            ->map(fn ($a) => [
                'name'   => $a->student_name ?: 'Unknown',
                'course' => $a->course_name ?: '-',
                'passed' => (int) ($a->passed ?? 0),
                'marked' => (int) ($a->marked ?? 0),
                'ready'  => (bool) $a->ready_to_graduate,
                'at'     => Carbon::parse($a->updated_at ?: $a->assessed_at)->format('M d, Y'),
            ])
            ->all();
    }

    /** Only for coaches with handles_athletes: the latest times they recorded. */
    private function athleteRecords(int $coachId): array
    {
        $base = DB::table('tbl_athlete_records')
            ->where('recorded_by', $coachId)
            ->whereNull('removed_at');

        $rows = DB::table('tbl_athlete_records as r')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'r.athlete_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'r.athlete_id')
            ->where('r.recorded_by', $coachId)
            ->whereNull('r.removed_at')
            ->orderByDesc('r.recorded_on')
            ->orderByDesc('r.record_id')
            ->limit(self::LIST_LIMIT)
            ->select(['r.stroke', 'r.distance_m', 'r.time_cs', 'r.recorded_on'])
            ->selectRaw($this->nameSql() . ' AS athlete_name')
            ->get();

        return [
            'last30' => (clone $base)->where('recorded_on', '>=', Carbon::today()->subDays(30))->count(),
            'rows'   => $rows->map(fn ($r) => [
                'name'  => $r->athlete_name ?: 'Unknown',
                'event' => $r->distance_m . 'm ' . ucfirst($r->stroke),
                'time'  => $this->swimTime((int) $r->time_cs),
                'on'    => Carbon::parse($r->recorded_on)->format('M d, Y'),
            ])->all(),
        ];
    }

    /* =====================================================================
     |  Helpers
     | ===================================================================== */

    /** Centiseconds -> 1:05.32 (or 28.40 under a minute). */
    private function swimTime(int $cs): string
    {
        $minutes = intdiv($cs, 6000);
        $seconds = ($cs % 6000) / 100;

        return $minutes > 0 ? sprintf('%d:%05.2f', $minutes, $seconds) : sprintf('%.2f', $seconds);
    }

    /** "First Last", falling back to the username. Expects joins aliased `pr` (profile) and `u` (users). */
    private function nameSql(): string
    {
        return "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', pr.first_name, pr.last_name)), ''), u.username)";
    }
}