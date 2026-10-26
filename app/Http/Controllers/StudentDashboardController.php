<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SkillAssessment;
use App\Models\SkillAssessmentResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data for the student / applicant dashboard (student_dashboard_blade).
 *
 * The page is rendered by name from role_menus.php (no route), so the blade
 * calls pageData() itself, the same way the Graduation page does.
 *
 * Four states, decided by the user's enrollments:
 *   none    -> no current, dropped or past enrollment
 *   past    -> enrolled before, nothing current (everything graduated)
 *   dropped -> nothing current, and the latest enrollment was dropped by the admin
 *   current -> has an enrollment with no graduation date yet that is not dropped
 */
class StudentDashboardController extends Controller
{
    /** Shown on the "not enrolled" and "finished" screens. Edit here if they change. */
    private const CONTACT = [
        'facebookLabel' => 'FBSS Swim School',
        'facebookUrl'   => 'https://www.facebook.com/FBSSswimschool',
        'email'         => 'fbssswimschool@gmail.com',
        'phoneLabel'    => '(+63) 966-203-1918',
        'phoneLink'     => '+639662031918',
    ];

    /** How many recent attendance rows the dashboard lists. */
    private const RECENT_ATTENDANCE = 5;

    public function pageData(User $user): array
    {
        $profile   = $user->profile;
        $firstName = trim((string) ($profile->first_name ?? '')) ?: (string) ($user->username ?? 'there');

        $enrollments = Enrollment::query()
            ->with('course')
            ->where('user_id', $user->getKey())
            ->orderByDesc('enrollment_at')
            ->orderByDesc('enrollment_id')
            ->get();

        // A dropped enrollment is not current; it only counts again if the admin reinstates it.
        $current = $enrollments->first(fn ($e) => $e->graduation_at === null && $e->dropped_at === null);
        $dropped = $current ? null : $enrollments->first(fn ($e) => $e->graduation_at === null && $e->dropped_at !== null);
        $past    = $enrollments->filter(fn ($e) => $e->graduation_at !== null)->values();

        return [
            'state'     => $current ? 'current' : ($dropped ? 'dropped' : ($past->isNotEmpty() ? 'past' : 'none')),
            'firstName' => $firstName,
            'contact'   => self::CONTACT,
            'current'   => $current ? $this->summarize($current, true) : null,
            // The dropped enrollment (course, dates, sessions done, balance). The admin's drop reason is not sent.
            'dropped'   => $dropped ? $this->summarize($dropped, false) : null,
            'past'      => $past->map(fn ($e) => $this->summarize($e, false))->all(),
        ];
    }

    /**
     * Sessions, skills assessment and payment summary for one enrollment.
     * Dates and times come back already formatted for display.
     */
    private function summarize(Enrollment $enrollment, bool $withAttendance): array
    {
        $id     = $enrollment->enrollment_id;
        $course = $enrollment->course;

        // Sessions attended = attendance rows with a Time In.
        $attended    = Attendance::query()->forEnrollment($id)->attended();
        $done        = (clone $attended)->count();
        $lastSession = (clone $attended)->max('attendance_at');
        $total       = max(0, (int) ($course->course_session_count ?? 0));

        // Skills: active categories/skills of the course + the coach's saved marks.
        $assessment = SkillAssessment::query()->with('results')->where('enrollment_id', $id)->first();
        $marks      = $assessment ? $assessment->results->pluck('result', 'course_skill_id') : collect();

        $course?->load([
            'activeCategories'        => fn ($query) => $query->orderBy('course_category_id'),
            'activeCategories.skills' => fn ($query) => $query->orderBy('course_skill_id'),
        ]);

        $categories   = [];
        $skillsTotal  = 0;
        $skillsPassed = 0;

        foreach ($course?->activeCategories ?? [] as $category) {
            $skills = [];
            $passed = 0;

            foreach ($category->skills as $skill) {
                $result = $marks->get($skill->course_skill_id);   // null = pending
                $skillsTotal++;

                if ($result === SkillAssessmentResult::PASS) {
                    $skillsPassed++;
                    $passed++;
                }

                $skills[] = ['name' => $skill->course_skill_name, 'result' => $result];
            }

            $categories[] = [
                'name'   => $category->course_category_name,
                'passed' => $passed,
                'total'  => count($skills),
                'skills' => $skills,
            ];
        }

        $ready    = (bool) ($assessment->ready_to_graduate ?? false);
        $eligible = $assessment && ($ready || ($skillsTotal > 0 && $skillsPassed === $skillsTotal));

        // Payments: tbl_course_payments.enrollment_id points at the enrollment.
        // Archived payments (archived_at set by the admin) don't count.
        $price = (float) ($enrollment->course_price_at_enrollment ?? $course->course_price ?? 0);
        $paid  = (float) DB::table('tbl_course_payments')->where('enrollment_id', $id)->whereNull('archived_at')->sum('amount');

        return [
            'courseName'   => $course->course_name ?? 'Course not assigned',
            'schedule'     => $enrollment->scheduled_time ?: null,
            'enrolledAt'   => $this->day($enrollment->enrollment_at),
            'graduatedAt'  => $this->day($enrollment->graduation_at),
            'droppedAt'    => $this->day($enrollment->dropped_at),
            'done'         => $done,
            'total'        => $total,
            'lastSession'  => $this->day($lastSession),
            'categories'   => $categories,
            'skillsTotal'  => $skillsTotal,
            'skillsPassed' => $skillsPassed,
            'eligible'     => (bool) $eligible,
            'price'        => $price,
            'paid'         => $paid,
            'balance'      => max(0, $price - $paid),
            'attendance'   => $withAttendance ? $this->recentAttendance($id) : [],
        ];
    }

    /** The latest few attendance rows for the enrollment, newest first. */
    private function recentAttendance(int $enrollmentId): array
    {
        return Attendance::query()
            ->forEnrollment($enrollmentId)
            ->orderByDesc('attendance_at')
            ->orderByDesc('time_in')
            ->limit(self::RECENT_ATTENDANCE)
            ->get()
            ->map(fn ($row) => [
                'date'  => $this->day($row->attendance_at),
                'in'    => $row->time_in ? Carbon::parse($row->time_in)->format('g:i A') : null,
                'out'   => $row->time_out ? Carbon::parse($row->time_out)->format('g:i A') : null,
                'state' => $row->attendance_state ?: ($row->time_in ? Attendance::STATE_PRESENT : 'Absent'),
            ])
            ->all();
    }

    private function day($value): ?string
    {
        return $value ? Carbon::parse($value)->format('M d, Y') : null;
    }
}