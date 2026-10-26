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
 * The page lists the students THIS coach has an attendance record for TODAY
 * (a Time In, or marked "Expected" or "Absent" on the Attendance page), with their Time In /
 * Time Out, each student's status, and the categories/skills of the course they
 * are enrolled in with any saved Pass/Failed marks.
 */
class CoachSkillsAssessmentController extends Controller
{
    private const COACH_ROLE_ID = 2;

    // Passing grade: a student can only be marked eligible to graduate when at
    // least this percent of ALL the course's active skills are passed. This is a
    // temporary value until the real passing grade is known; change it here only
    // (the page reads it from the data response).
    public const PASSING_PERCENT = 80;

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
            'date'      => ['nullable', 'date_format:Y-m-d'],   // the browser's local "today"
        ]);

        $coachId  = $coach->getKey();
        $courseId = $input['course_id'] ?? null;
        $date     = $this->today($input['date'] ?? null);

        // Logging attendance here needs the coach to have timed in (Attendance page).
        $blockReason = $this->sessionBlockReason((int) $coachId, $date);

        // Class dropdown: the courses of the students this coach has a record for today.
        $loggedEnrollmentIds = Attendance::query()
            ->where('logged_by', $coachId)
            ->whereNotNull('enrollment_id')
            ->onDate($date)
            ->select('enrollment_id');

        $courses = Course::query()
            ->whereIn(
                'course_id',
                Enrollment::query()->whereIn('enrollment_id', $loggedEnrollmentIds)->whereNull('dropped_at')->select('course_id')
            )
            ->orderBy('course_name')
            ->get(['course_id', 'course_name'])
            ->map(fn ($course) => ['id' => $course->course_id, 'name' => $course->course_name])
            ->values();

        // Every student this coach has an attendance record for TODAY (timed in
        // or marked Expecting), one row per enrollment.
        $logs = Attendance::query()
            ->where('logged_by', $coachId)
            ->whereNotNull('enrollment_id')
            ->onDate($date)
            ->orderByDesc((new Attendance)->getKeyName())
            ->get()
            ->unique('enrollment_id')
            ->keyBy('enrollment_id');

        $enrollments = Enrollment::query()
            ->with(['user.profile', 'course'])
            ->whereIn('enrollment_id', $logs->keys())
            // A dropped student is gone from this page, even with a record logged before the drop.
            ->whereNull('dropped_at')
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

                // The admin confirmed the graduation: this enrollment is closed (view only here).
                $graduated   = $enrollment->graduation_at !== null;
                $graduatedAt = $graduated ? Carbon::parse($enrollment->graduation_at)->toDateString() : null;

                return [
                    'id'                => $enrollment->enrollment_id,
                    'name'              => $this->displayName($enrollment),
                    'course_id'         => $enrollment->course_id,
                    'course'            => $enrollment->course?->course_name,
                    'date'              => $this->logDate($log),
                    'time_in'           => $timeIn,
                    'time_out'          => Attendance::hhmm($log->time_out),
                    'state'             => $log->attendance_state ?: ($timeIn ? Attendance::STATE_PRESENT : null),
                    // Expecting + no Time In yet = can be timed in late.
                    'is_expecting'      => $log->attendance_state === CoachAttendanceController::STATE_EXPECTING && ! $timeIn,
                    'assessment'        => $graduated
                        ? 'Graduated'
                        : (! $assessment ? 'Not yet assessed' : ($eligible ? 'Eligible to graduate' : ($markedSkills < $totalSkills ? 'In progress' : 'Assessed'))),
                    'graduated'         => $graduated,
                    'graduated_at'      => $graduatedAt,
                    'ready_to_graduate' => $ready,
                    'checklist'         => $checklist,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return response()->json([
            'success'      => true,
            'courses'      => $courses,
            'rows'         => $rows,
            'passing_percent' => self::PASSING_PERCENT,
            'can_log'      => $blockReason === null,
            'block_reason' => $blockReason,
        ]);
    }

    /**
     * POST /coach/skills-assessment/absent
     * Body: { enrollment_id, date }
     * Marks a student who has not timed in as absent from the Skill Assessment page.
     */
    public function markAbsent(Request $request): JsonResponse
    {
        $coach = $request->user();

        if (! $coach || (int) $coach->role_id !== self::COACH_ROLE_ID) {
            return response()->json([
                'success' => false,
                'message' => 'Only coaches can mark students absent.',
            ], 403);
        }

        $input = $request->validate([
            'enrollment_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $this->today($input['date']);
        $coachId = (int) $coach->getKey();

        if ($closed = $this->graduatedResponse((int) $input['enrollment_id'])) {
            return $closed;
        }

        if ($dropped = $this->droppedResponse((int) $input['enrollment_id'])) {
            return $dropped;
        }

        return DB::transaction(function () use ($input, $date, $coachId) {
            if ($reason = $this->sessionBlockReason($coachId, $date)) {
                return response()->json(['success' => false, 'message' => $reason], 403);
            }

            // Only update an attendance record this coach logged for this date.
            // Skill Assessment only lists students with an existing attendance record.
            $log = Attendance::query()
                ->where('enrollment_id', (int) $input['enrollment_id'])
                ->where('logged_by', $coachId)
                ->onDate($date)
                ->orderByDesc((new Attendance)->getKeyName())
                ->lockForUpdate()
                ->first();

            if (! $log) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attendance record was found for this student today.',
                ], 404);
            }

            if ($log->time_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'A student who has timed in cannot be marked absent.',
                ], 422);
            }

            if ($log->attendance_state === 'Absent') {
                return response()->json([
                    'success' => false,
                    'message' => 'This student is already marked absent.',
                ], 422);
            }

            $log->update([
                'attendance_state' => 'Absent',
                'time_out' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student marked as absent.',
            ]);
        });
    }

    /**
     * POST /coach/skills-assessment/remove-mark
     * Body: { enrollment_id, date }
     * Removes an Expected or Absent mark this coach set (a mark holds no times,
     * so the attendance row is deleted and the student leaves this list).
     * A student who has timed in is never touched here.
     */
    public function removeMark(Request $request): JsonResponse
    {
        $coach = $request->user();

        if (! $coach || (int) $coach->role_id !== self::COACH_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only coaches can remove a mark.'], 403);
        }

        $input = $request->validate([
            'enrollment_id' => ['required', 'integer'],
            'date'          => ['required', 'date_format:Y-m-d'],
        ]);

        $date    = $this->today($input['date']);
        $coachId = (int) $coach->getKey();

        if ($closed = $this->graduatedResponse((int) $input['enrollment_id'])) {
            return $closed;
        }

        if ($dropped = $this->droppedResponse((int) $input['enrollment_id'])) {
            return $dropped;
        }

        return DB::transaction(function () use ($input, $date, $coachId) {
            if ($reason = $this->sessionBlockReason($coachId, $date)) {
                return response()->json(['success' => false, 'message' => $reason], 403);
            }

            $log = Attendance::query()
                ->where('enrollment_id', (int) $input['enrollment_id'])
                ->where('logged_by', $coachId)
                ->onDate($date)
                ->orderByDesc((new Attendance)->getKeyName())
                ->lockForUpdate()
                ->first();

            if (! $log) {
                return response()->json(['success' => false, 'message' => 'No attendance record was found for this student today.'], 404);
            }

            if ($log->time_in) {
                return response()->json(['success' => false, 'message' => 'This student has already timed in, so there is no mark to remove.'], 422);
            }

            if (! in_array($log->attendance_state, [CoachAttendanceController::STATE_EXPECTING, 'Absent'], true)) {
                return response()->json(['success' => false, 'message' => 'This student is not marked Expected or Absent.'], 422);
            }

            $label = $log->attendance_state === 'Absent' ? 'Absent' : 'Expected';

            $log->delete();

            return response()->json(['success' => true, 'message' => $label . ' removed.']);
        });
    }

    /**
     * PUT /coach/skills-assessment/{enrollment}
     * Body: { results: { "<course_skill_id>": "pass" | "failed", ... }, ready_to_graduate: bool }
     *
     * Skills can be saved one at a time; saving again replaces the marks sent.
     * ready_to_graduate is only accepted when at least PASSING_PERCENT % of the
     * course's active skills are passed (saved marks + the ones sent now).
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

        if ($enrollmentRow->graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has already graduated, so the assessment can no longer be changed.'], 422);
        }

        // A dropped student's assessment cannot be viewed or changed from here anymore.
        if ($dropped = $this->droppedResponse((int) $enrollmentRow->enrollment_id)) {
            return $dropped;
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
        // to graduate needs the passing grade: at least PASSING_PERCENT % of
        // all active skills passed. Saved marks count together with the ones
        // sent now (a mark sent now replaces the saved one); a skill with no
        // mark counts as not passed.
        if ($ready) {
            $saved = SkillAssessmentResult::query()
                ->whereIn(
                    'skill_assessment_id',
                    SkillAssessment::query()->where('enrollment_id', $enrollmentRow->enrollment_id)->select('skill_assessment_id')
                )
                ->pluck('result', 'course_skill_id')
                ->mapWithKeys(fn ($result, $skillId) => [(int) $skillId => $result]);

            $merged = $submitted->union($saved);

            $total  = $requiredIds->count();
            $passed = $requiredIds->filter(fn ($id) => $merged->get($id) === SkillAssessmentResult::PASS)->count();

            if ($passed * 100 < self::PASSING_PERCENT * $total) {
                $needed  = (int) ceil($total * self::PASSING_PERCENT / 100);
                $percent = round($passed / $total * 100, 1);

                return response()->json([
                    'success' => false,
                    'message' => 'To mark as eligible to graduate, at least ' . self::PASSING_PERCENT . "% of the skills must be passed ({$needed} of {$total}). This student has {$passed} ({$percent}%).",
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
     * A graduated enrollment is closed (the admin confirmed the graduation), so
     * nothing about it can be changed from the coach pages. Returns the error
     * reply when it has graduated, null otherwise.
     */
    private function graduatedResponse(int $enrollmentId): ?JsonResponse
    {
        $graduated = Enrollment::query()
            ->where('enrollment_id', $enrollmentId)
            ->whereNotNull('graduation_at')
            ->exists();

        return $graduated
            ? response()->json(['success' => false, 'message' => 'This student has already graduated, so this can no longer be changed.'], 422)
            : null;
    }

    /**
     * A dropped student is no longer in the coach's class (soft drop on the admin
     * Registers page). Nothing about the enrollment can be changed from the coach
     * pages. "dropped" => true lets the page close its modals and reload the list.
     * Returns the error reply when the enrollment is dropped, null otherwise.
     */
    private function droppedResponse(int $enrollmentId): ?JsonResponse
    {
        $dropped = Enrollment::query()
            ->where('enrollment_id', $enrollmentId)
            ->whereNotNull('dropped_at')
            ->exists();

        return $dropped
            ? response()->json([
                'success' => false,
                'dropped' => true,
                'message' => 'This student has been dropped and is no longer available here.',
            ], 422)
            : null;
    }

    /**
     * Same rule as the Attendance page: the coach must have timed in for the
     * day before logging anything. (Timing out does not lock it again.)
     */
    private function sessionBlockReason(int $coachId, string $date): ?string
    {
        $timedIn = Attendance::query()
            ->where('user_id', $coachId)
            ->where('role_id', self::COACH_ROLE_ID)
            ->onDate($date)
            ->whereNotNull('time_in')
            ->exists();

        return $timedIn ? null : 'You need to Time In first (on the Attendance page) before you can log attendance.';
    }

    /**
     * "Today" as YYYY-MM-DD. The browser sends its own local date; it is only
     * trusted within a day of the server clock (e.g. UTC server, Manila user).
     */
    private function today(?string $clientDate): string
    {
        $now = Carbon::now();

        if ($clientDate
            && $clientDate >= $now->copy()->subDay()->toDateString()
            && $clientDate <= $now->copy()->addDay()->toDateString()) {
            return $clientDate;
        }

        return $now->toDateString();
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