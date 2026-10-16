<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSkill;
use App\Models\Enrollment;
use App\Models\SkillAssessment;
use App\Models\SkillAssessmentResult;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Coach Skills Assessment page.
 *
 * The page lists every student THIS coach has logged attendance for (latest
 * log per student, with Time In / Time Out), each student's status, and the
 * categories/skills of the course they are enrolled in with any saved
 * Pass/Failed marks.
 */
class CoachSkillsAssessmentController extends Controller
{
    private const COACH_ROLE_ID = 2;

    // Column on the attendance table that holds the log's date.
    private const LOG_DATE_COLUMN = 'attendance_at';

    /**
     * GET /coach/skills-assessment/data?course_id=#
     */
    public function data(Request $request): JsonResponse
    {
        $coach = $request->user();

        if ((int) $coach->role_id !== self::COACH_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only coaches can view this page.'], 403);
        }

        $input = $request->validate([
            'course_id' => ['nullable', 'integer'],
        ]);

        $coachId  = $coach->getKey();
        $courseId = $input['course_id'] ?? null;

        // Class dropdown: every course this coach has ever logged a student in.
        $loggedEnrollmentIds = Attendance::query()
            ->where('logged_by', $coachId)
            ->whereNotNull('enrollment_id')
            ->select('enrollment_id');

        $courses = Course::query()
            ->whereIn(
                'course_id',
                Enrollment::query()->whereIn('enrollment_id', $loggedEnrollmentIds)->select('course_id')
            )
            ->orderBy('course_name')
            ->get(['course_id', 'course_name'])
            ->map(fn ($course) => ['id' => $course->course_id, 'name' => $course->course_name])
            ->values();

        // Every student this coach has logged, one row per enrollment showing
        // their most recent attendance log (newest first, then de-duplicated).
        $logs = Attendance::query()
            ->where('logged_by', $coachId)
            ->whereNotNull('enrollment_id')
            ->orderByDesc((new Attendance)->getKeyName())
            ->get()
            ->unique('enrollment_id')
            ->keyBy('enrollment_id');

        $enrollments = Enrollment::query()
            ->with(['user.profile', 'course'])
            ->whereIn('enrollment_id', $logs->keys())
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->get();

        // Saved assessments (with their Pass/Failed marks) for these enrollments.
        $assessments = SkillAssessment::query()
            ->with('results')
            ->whereIn('enrollment_id', $enrollments->pluck('enrollment_id'))
            ->get()
            ->keyBy('enrollment_id');

        // Active (non-archived) categories and skills for each course shown.
        $checklists = Course::query()
            ->whereIn('course_id', $enrollments->pluck('course_id')->unique())
            ->with([
                'activeCategories'        => fn ($query) => $query->orderBy('course_category_id'),
                'activeCategories.skills' => fn ($query) => $query->orderBy('course_skill_id'),
            ])
            ->get()
            ->keyBy('course_id')
            ->map(fn ($course) => $course->activeCategories->map(fn ($category) => [
                'id'     => $category->course_category_id,
                'name'   => $category->course_category_name,
                'skills' => $category->skills->map(fn ($skill) => [
                    'id'   => $skill->course_skill_id,
                    'name' => $skill->course_skill_name,
                ])->values()->all(),
            ])->values()->all());

        $rows = $enrollments
            ->map(function ($enrollment) use ($logs, $assessments, $checklists) {
                $log        = $logs->get($enrollment->enrollment_id);
                $assessment = $assessments->get($enrollment->enrollment_id);
                $timeIn     = Attendance::hhmm($log->time_in);

                // skill id => 'pass' | 'failed'
                $saved = $assessment ? $assessment->results->pluck('result', 'course_skill_id') : collect();

                $totalSkills = 0;
                $passedSkills = 0;
                $markedSkills = 0;

                $checklist = collect($checklists->get($enrollment->course_id, []))
                    ->map(function ($category) use ($saved, &$totalSkills, &$passedSkills, &$markedSkills) {
                        $category['skills'] = collect($category['skills'])
                            ->map(function ($skill) use ($saved, &$totalSkills, &$passedSkills, &$markedSkills) {
                                $skill['result'] = $saved->get($skill['id']);   // null = not marked yet

                                $totalSkills++;
                                if ($skill['result'] !== null) {
                                    $markedSkills++;
                                }
                                if ($skill['result'] === SkillAssessmentResult::PASS) {
                                    $passedSkills++;
                                }

                                return $skill;
                            })
                            ->values()
                            ->all();

                        return $category;
                    })
                    ->values()
                    ->all();

                $ready    = (bool) ($assessment->ready_to_graduate ?? false);
                $eligible = $assessment && ($ready || ($totalSkills > 0 && $passedSkills === $totalSkills));

                return [
                    'id'                => $enrollment->enrollment_id,
                    'name'              => $this->displayName($enrollment),
                    'course_id'         => $enrollment->course_id,
                    'course'            => $enrollment->course?->course_name,
                    'date'              => $this->logDate($log),
                    'time_in'           => $timeIn,
                    'time_out'          => Attendance::hhmm($log->time_out),
                    'state'             => $log->attendance_state ?: ($timeIn ? Attendance::STATE_PRESENT : null),
                    'assessment'        => ! $assessment ? 'Not yet assessed' : ($eligible ? 'Eligible to graduate' : ($markedSkills < $totalSkills ? 'In progress' : 'Assessed')),
                    'ready_to_graduate' => $ready,
                    'checklist'         => $checklist,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'rows'    => $rows,
        ]);
    }

    /**
     * PUT /coach/skills-assessment/{enrollment}
     * Body: { results: { "<course_skill_id>": "pass" | "failed", ... }, ready_to_graduate: bool }
     *
     * Skills can be saved one at a time; saving again replaces the marks sent.
     * Every active skill must have a remark only when ready_to_graduate is true.
     */
    public function save(Request $request, int $enrollment): JsonResponse
    {
        $coach = $request->user();

        if ((int) $coach->role_id !== self::COACH_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only coaches can save an assessment.'], 403);
        }

        $enrollmentRow = Enrollment::find($enrollment);

        if (! $enrollmentRow) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        // A coach can only assess a student they have logged attendance for.
        $loggedByCoach = Attendance::query()
            ->where('enrollment_id', $enrollment)
            ->where('logged_by', $coach->getKey())
            ->exists();

        if (! $loggedByCoach) {
            return response()->json(['success' => false, 'message' => 'You can only assess students you have logged.'], 403);
        }

        $input = $request->validate([
            'results'           => ['nullable', 'array'],
            'results.*'         => ['required', 'in:' . SkillAssessmentResult::PASS . ',' . SkillAssessmentResult::FAILED],
            'ready_to_graduate' => ['nullable', 'boolean'],
        ]);

        // Active skills on this course (archived categories/skills are skipped
        // automatically by their soft-delete scopes).
        $requiredSkills = CourseSkill::query()
            ->whereIn(
                'course_category_id',
                CourseCategory::query()->where('course_id', $enrollmentRow->course_id)->select('course_category_id')
            )
            ->orderBy('course_skill_id')
            ->get(['course_skill_id', 'course_skill_name']);

        if ($requiredSkills->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'This course has no skills set up yet.'], 422);
        }

        $submitted   = collect($input['results'] ?? [])->mapWithKeys(fn ($value, $skillId) => [(int) $skillId => $value]);
        $requiredIds = $requiredSkills->pluck('course_skill_id')->map(fn ($id) => (int) $id);

        if ($submitted->keys()->diff($requiredIds)->isNotEmpty()) {
            return response()->json(['success' => false, 'message' => 'Some skills do not belong to this student\'s course.'], 422);
        }

        $ready = (bool) ($input['ready_to_graduate'] ?? false);

        // Skills can be logged one by one. Only marking the student as eligible
        // to graduate requires every skill to have a remark (already saved or
        // submitted now).
        if ($ready) {
            $alreadySaved = SkillAssessmentResult::query()
                ->whereIn(
                    'skill_assessment_id',
                    SkillAssessment::query()->where('enrollment_id', $enrollmentRow->enrollment_id)->select('skill_assessment_id')
                )
                ->pluck('course_skill_id')
                ->map(fn ($id) => (int) $id);

            $marked  = $submitted->keys()->merge($alreadySaved)->unique();
            $missing = $requiredSkills->filter(fn ($skill) => ! $marked->contains((int) $skill->course_skill_id));

            if ($missing->isNotEmpty()) {
                $names = $missing->take(3)->pluck('course_skill_name')->implode(', ');
                $more  = $missing->count() > 3 ? ' and ' . ($missing->count() - 3) . ' more' : '';

                return response()->json([
                    'success' => false,
                    'message' => "To mark as eligible to graduate, every skill needs a Pass or Failed. Missing: {$names}{$more}.",
                ], 422);
            }
        }

        DB::transaction(function () use ($enrollmentRow, $coach, $input, $submitted) {
            $assessment = SkillAssessment::updateOrCreate(
                ['enrollment_id' => $enrollmentRow->enrollment_id],
                [
                    'assessed_by'       => $coach->getKey(),
                    'ready_to_graduate' => (bool) ($input['ready_to_graduate'] ?? false),
                    'updated_at'        => now(),
                ]
            );

            if ($submitted->isNotEmpty()) {
                SkillAssessmentResult::upsert(
                    $submitted->map(fn ($result, $skillId) => [
                        'skill_assessment_id' => $assessment->skill_assessment_id,
                        'course_skill_id'     => $skillId,
                        'result'              => $result,
                    ])->values()->all(),
                    ['skill_assessment_id', 'course_skill_id'],
                    ['result']
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'Skills assessment saved.']);
    }

    /**
     * The attendance log's date as YYYY-MM-DD, or null when unavailable.
     */
    private function logDate(Attendance $log): ?string
    {
        $value = $log->{self::LOG_DATE_COLUMN} ?? null;

        if (! $value) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }

    /**
     * "Last, First M." from the profile; falls back to the username.
     */
    private function displayName(Enrollment $enrollment): string
    {
        $user    = $enrollment->user;
        $profile = $user?->profile;

        $last   = trim((string) ($profile->last_name ?? ''));
        $first  = trim((string) ($profile->first_name ?? ''));
        $middle = trim((string) ($profile->middle_name ?? ''));

        $middleInitial  = $middle !== '' ? mb_substr($middle, 0, 1) . '.' : '';
        $firstAndMiddle = trim($first . ' ' . $middleInitial);

        if ($last !== '' && $firstAndMiddle !== '') {
            return $last . ', ' . $firstAndMiddle;
        }

        return (string) ($user->username ?? 'Unknown');
    }
}