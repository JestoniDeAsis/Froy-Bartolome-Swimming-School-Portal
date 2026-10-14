<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\SkillAssessment;
use App\Models\SkillAssessmentResult;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class GraduationController extends Controller
{
    /**
     * Data for the admin Graduation page (graduation_blade).
     *
     * - active:      enrollments not graduated yet, account status Active
     * - graduated:   enrollments that have a graduation date
     * - assessments: skills assessment + payment details, keyed by enrollment id,
     *                for the "Ready for Assessment" students (every session
     *                attended) and the graduated ones. The page's popup uses it.
     *
     * Sessions attended = attendance rows with a Time In (same rule as the
     * Attendance page).
     *
     * @return array{active: array<int, array<string, mixed>>, graduated: array<int, array<string, mixed>>, assessments: array<int, array<string, mixed>>}
     */
    public function pageData(): array
    {
        $columns = [
            'e.enrollment_id',
            'e.enrollment_at',
            'e.graduation_at',
            'e.scheduled_time',
            'c.' . CoachController::COURSE_ID_COL . ' as course_id',
            'c.' . CoachController::COURSE_NAME_COL . ' as course_name',
            'c.course_session_count',
            DB::raw('COALESCE(e.course_price_at_enrollment, c.course_price) as price'),
            'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix',
        ];

        $activeEnrollments = DB::table('tbl_enrollments as e')
            ->join(CoachController::USERS_TABLE . ' as u', 'u.' . CoachController::USER_PK, '=', 'e.user_id')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
            ->join(CoachController::STATUS_TABLE . ' as st', 'st.' . CoachController::STATUS_ID_COL, '=', 'u.' . CoachController::STATUS_COL)
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->whereNull('e.graduation_at')
            ->where('st.' . CoachController::STATUS_NAME_COL, 'Active')
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->get($columns);

        $graduatedEnrollments = DB::table('tbl_enrollments as e')
            ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
            ->leftJoin(CoachController::COURSES_TABLE . ' as c', 'c.' . CoachController::COURSE_ID_COL, '=', 'e.course_id')
            ->whereNotNull('e.graduation_at')
            ->orderByDesc('e.graduation_at')
            ->get($columns);

        $enrollmentById = $activeEnrollments->concat($graduatedEnrollments)->keyBy('enrollment_id');
        $allIds         = $enrollmentById->keys();

        // Sessions attended per enrollment.
        $sessions = $allIds->isEmpty()
            ? collect()
            : DB::table('tbl_attendances')
                ->whereIn('enrollment_id', $allIds)
                ->whereNotNull('time_in')
                ->groupBy('enrollment_id')
                ->selectRaw('enrollment_id, COUNT(*) as sessions, MAX(attendance_at) as last_session')
                ->get()
                ->keyBy('enrollment_id');

        // Total paid per enrollment (tbl_course_payments.student_id points at the enrollment).
        $paidByEnrollment = $allIds->isEmpty()
            ? collect()
            : DB::table('tbl_course_payments')
                ->whereIn('student_id', $allIds)
                ->groupBy('student_id')
                ->selectRaw('student_id, SUM(amount) as paid')
                ->pluck('paid', 'student_id');

        $toRow = function ($e) use ($sessions) {
            $given = trim($e->first_name . ' ' . ($e->middle_name ?? ''));
            $name  = trim($e->last_name . ', ' . $given . ($e->name_suffix ? ' ' . $e->name_suffix : ''));
            $row   = $sessions->get($e->enrollment_id);

            return [
                'id'          => (int) $e->enrollment_id,
                'name'        => $name,
                'courseId'    => $e->course_id !== null ? (int) $e->course_id : null,
                'course'      => $e->course_name ?? 'No course',
                'schedule'    => $e->scheduled_time ?: null,
                'enrolledAt'  => $e->enrollment_at ? substr((string) $e->enrollment_at, 0, 10) : null,
                'graduatedAt' => $e->graduation_at ? substr((string) $e->graduation_at, 0, 10) : null,
                'done'        => $row ? (int) $row->sessions : 0,
                'total'       => max(0, (int) $e->course_session_count),
                'lastSession' => $row && $row->last_session ? substr((string) $row->last_session, 0, 10) : null,
            ];
        };

        $active    = $activeEnrollments->map($toRow)->values();
        $graduated = $graduatedEnrollments->map($toRow)->values();

        // Students who attended every session are "Ready for Assessment".
        $readyIds = $active
            ->filter(fn ($s) => $s['total'] > 0 && $s['done'] >= $s['total'])
            ->pluck('id');

        $assessmentIds = $readyIds->merge($graduated->pluck('id'))->unique()->values();

        $assessments = [];

        if ($assessmentIds->isNotEmpty()) {
            $saved = SkillAssessment::query()
                ->with(['results', 'assessedBy.profile'])
                ->whereIn('enrollment_id', $assessmentIds)
                ->get()
                ->keyBy('enrollment_id');

            $courseIds = $assessmentIds
                ->map(fn ($id) => $enrollmentById[$id]->course_id)
                ->filter()
                ->unique();

            // Active (non-archived) categories and skills for each course.
            $courses = Course::query()
                ->whereIn('course_id', $courseIds)
                ->with([
                    'activeCategories'        => fn ($query) => $query->orderBy('course_category_id'),
                    'activeCategories.skills' => fn ($query) => $query->orderBy('course_skill_id'),
                ])
                ->get()
                ->keyBy('course_id');

            foreach ($assessmentIds as $id) {
                $enrollment = $enrollmentById[$id];
                $assessment = $saved->get($id);
                $course     = $courses->get($enrollment->course_id);

                // skill id => 'pass' | 'failed'
                $results = $assessment ? $assessment->results->pluck('result', 'course_skill_id') : collect();

                $totalSkills  = 0;
                $passedSkills = 0;
                $categories   = [];

                foreach ($course?->activeCategories ?? [] as $category) {
                    $skills         = [];
                    $categoryPassed = 0;

                    foreach ($category->skills as $skill) {
                        $result = $results->get($skill->course_skill_id);   // null = not marked

                        $totalSkills++;
                        if ($result === SkillAssessmentResult::PASS) {
                            $passedSkills++;
                            $categoryPassed++;
                        }

                        $skills[] = ['name' => $skill->course_skill_name, 'result' => $result];
                    }

                    $categories[] = [
                        'name'   => $category->course_category_name,
                        'passed' => $categoryPassed,
                        'total'  => count($skills),
                        'skills' => $skills,
                    ];
                }

                $ready    = (bool) ($assessment->ready_to_graduate ?? false);
                $eligible = $assessment && ($ready || ($totalSkills > 0 && $passedSkills === $totalSkills));

                $price   = (float) ($enrollment->price ?? 0);
                $paid    = (float) ($paidByEnrollment->get($id) ?? 0);
                $balance = max(0, $price - $paid);

                $assessments[$id] = [
                    'status'            => ! $assessment ? 'not_assessed' : ($eligible ? 'eligible' : 'assessed'),
                    'ready_to_graduate' => $ready,
                    'assessed_by'       => $assessment ? $this->coachName($assessment) : null,
                    'assessed_at'       => $assessment && $assessment->updated_at
                        ? substr((string) $assessment->updated_at, 0, 10)
                        : ($assessment && $assessment->assessed_at ? substr((string) $assessment->assessed_at, 0, 10) : null),
                    'passed'            => $passedSkills,
                    'total'             => $totalSkills,
                    'categories'        => $categories,
                    'price'             => $price,
                    'paid'              => $paid,
                    'balance'           => $balance,
                ];
            }
        }

        return [
            'active'      => $active->all(),
            'graduated'   => $graduated->all(),
            'assessments' => $assessments,
        ];
    }

    /** "First Last" of the coach who saved the assessment; falls back to the username. */
    private function coachName(SkillAssessment $assessment): ?string
    {
        $coach   = $assessment->assessedBy;
        $profile = $coach?->profile;

        $name = trim((string) ($profile->first_name ?? '') . ' ' . (string) ($profile->last_name ?? ''));

        return $name !== '' ? $name : ($coach->username ?? null);
    }

    /**
     * Build the Active / Ready for Assessment / Graduated tabs data
     * used by the graduation page.
     *
     * @return array<string, array{label: string, icon: string, students: array<int, array<string, int|string|null>>}>
     */
    public function graduationTabs(): array
    {
        $lastSessions = DB::table('tbl_attendances')
            ->select('user_id')
            ->selectRaw('MAX(attendance_at) AS last_session')
            ->groupBy('user_id');

        $students = DB::table('tbl_students as s')
            ->join('tbl_users as u', 'u.user_id', '=', 's.user_id')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 's.user_id')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 's.course_id')
            ->leftJoinSub($lastSessions, 'attendance_summary', function (JoinClause $join) {
                $join->on('attendance_summary.user_id', '=', 's.user_id');
            })
            ->where('u.role_id', 3)
            ->orderBy('p.last_name')
            ->orderBy('p.first_name')
            ->select([
                's.student_id',
                's.session_count',
                's.graduation_at',
                'c.course_name',
                'c.course_session_count',
                'p.first_name',
                'p.middle_name',
                'p.last_name',
                'p.name_suffix',
                'attendance_summary.last_session',
            ])
            ->get();

        $graduationTabs = [
            'active' => ['label' => 'Active Students', 'icon' => 'bi-clock-fill', 'students' => []],
            'ready' => ['label' => 'Ready for Assessment', 'icon' => 'bi-mortarboard-fill', 'students' => []],
            'graduated' => ['label' => 'Graduated', 'icon' => 'bi-check-circle-fill', 'students' => []],
        ];

        foreach ($students as $student) {
            $middleName = trim((string) $student->middle_name);
            $middleInitial = $middleName !== '' ? mb_substr($middleName, 0, 1) . '.' : '';
            $firstAndMiddle = trim((string) $student->first_name . ' ' . $middleInitial);
            $nameParts = array_filter([
                $firstAndMiddle,
                trim((string) $student->name_suffix),
            ], fn (string $part): bool => $part !== '');
            $lastName = trim((string) $student->last_name);
            $studentName = $lastName !== ''
                ? $lastName . ', ' . implode(' ', $nameParts)
                : ($firstAndMiddle !== '' ? $firstAndMiddle : 'Unnamed student');

            $completedSessions = max(0, (int) $student->session_count);
            $requiredSessions = max(0, (int) $student->course_session_count);
            $sessionsLabel = $requiredSessions > 0
                ? $completedSessions . ' / ' . $requiredSessions . ' sessions' . ($completedSessions >= $requiredSessions ? ' completed' : '')
                : $completedSessions . ' sessions';

            $group = 'active';
            if ($student->graduation_at !== null) {
                $group = 'graduated';
                $sessionsLabel = 'Graduated on ' . Carbon::parse($student->graduation_at)->format('m / d / Y');
            } elseif ($requiredSessions > 0 && $completedSessions >= $requiredSessions) {
                $group = 'ready';
            }

            $graduationTabs[$group]['students'][] = [
                'student_id' => (int) $student->student_id,
                'name' => $studentName,
                'level' => (string) ($student->course_name ?? 'Course not assigned'),
                'sessions' => $sessionsLabel,
                'last_session' => $student->last_session !== null
                    ? Carbon::parse($student->last_session)->format('m / d / Y')
                    : null,
                'certificate' => 'Certificate not recorded',
            ];
        }

        return $graduationTabs;
    }
}