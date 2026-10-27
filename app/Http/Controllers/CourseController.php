<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSkill;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Equivalent of get_course.php.
     * Not using route-model binding so a missing course returns a plain
     * JSON 404 instead of Laravel's default (HTML) 404 page.
     */
    public function show(int $course_id): JsonResponse
    {
        $course = Course::find($course_id);

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        // Pulls both active and archived rows — the edit modal needs to
        // show archived categories/skills too (grayed out), it just can't
        // delete them for good.
        $categories = $course->allCategories()
            ->orderByRaw('deleted_at IS NULL DESC')
            ->orderBy('course_category_id')
            ->with(['allSkills' => function ($query) {
                $query->orderByRaw('deleted_at IS NULL DESC')->orderBy('course_skill_id');
            }])
            ->get()
            ->map(fn ($category) => [
                'id'      => $category->course_category_id,
                'name'    => $category->course_category_name,
                'deleted' => $category->deleted_at !== null,
                'skills'  => $category->allSkills->map(fn ($skill) => [
                    'id'      => $skill->course_skill_id,
                    'name'    => $skill->course_skill_name,
                    'deleted' => $skill->deleted_at !== null,
                ]),
            ]);

        return response()->json([
            'success' => true,
            'course'  => [
                'course_id'            => $course->course_id,
                'course_name'          => $course->course_name,
                'course_session_count' => $course->course_session_count,
                'course_price'         => $course->course_price,
                'categories'           => $categories,
            ],
        ]);
    }

    /**
     * Statistics for one course (the "Stats" modal on the Courses page).
     *
     * Definitions:
     *  - dropped   = enrollment has dropped_at
     *  - graduated = enrollment has graduation_at
     *  - active    = neither of the above
     *  - skill pass rate uses each enrollment's LATEST skill assessment only,
     *    so re-assessments replace old results instead of piling up.
     */
    public function stats(int $course_id): JsonResponse
    {
        $course = Course::find($course_id);

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        $enrollments = DB::table('tbl_enrollments')
            ->where('course_id', $course_id)
            ->get(['enrollment_id', 'course_price_at_enrollment', 'enrollment_at', 'graduation_at', 'dropped_at']);

        $ids = $enrollments->pluck('enrollment_id');

        $dropped   = $enrollments->filter(fn ($e) => $e->dropped_at !== null);
        $graduated = $enrollments->filter(fn ($e) => $e->graduation_at !== null && $e->dropped_at === null);
        $active    = $enrollments->filter(fn ($e) => $e->graduation_at === null && $e->dropped_at === null);

        // Of everyone who has finished one way or the other, how many graduated.
        $finished       = $graduated->count() + $dropped->count();
        $completionRate = $finished > 0 ? round($graduated->count() / $finished * 100, 1) : null;

        $daysToGraduate = $graduated
            ->filter(fn ($e) => $e->enrollment_at !== null)
            ->map(fn ($e) => Carbon::parse($e->enrollment_at)->diffInDays(Carbon::parse($e->graduation_at)));
        $avgDays = $daysToGraduate->isNotEmpty() ? (int) round($daysToGraduate->avg()) : null;

        // Money. Archived payments are corrections/voids, so they don't count.
        $paidByEnrollment = DB::table('tbl_course_payments')
            ->whereIn('enrollment_id', $ids)
            ->whereNull('archived_at')
            ->groupBy('enrollment_id')
            ->selectRaw('enrollment_id, SUM(amount) as total')
            ->pluck('total', 'enrollment_id');

        $collected   = (float) $paidByEnrollment->sum();
        $outstanding = $enrollments->reject(fn ($e) => $e->dropped_at !== null)->sum(function ($e) use ($course, $paidByEnrollment) {
            $price = (float) ($e->course_price_at_enrollment ?? $course->course_price ?? 0);
            return max(0, $price - (float) $paidByEnrollment->get($e->enrollment_id, 0));
        });

        // Latest assessment per enrollment.
        $latestIds = DB::table('tbl_skill_assessments')
            ->whereIn('enrollment_id', $ids)
            ->groupBy('enrollment_id')
            ->selectRaw('MAX(skill_assessment_id) as id')
            ->pluck('id');

        $latest = DB::table('tbl_skill_assessments')
            ->whereIn('skill_assessment_id', $latestIds)
            ->get(['skill_assessment_id', 'enrollment_id', 'ready_to_graduate']);

        $activeIds     = $active->pluck('enrollment_id')->all();
        $readyToGrad   = $latest->filter(fn ($a) => $a->ready_to_graduate && in_array($a->enrollment_id, $activeIds))->count();

        $resultsBySkill = DB::table('tbl_skill_assessment_results')
            ->whereIn('skill_assessment_id', $latest->pluck('skill_assessment_id'))
            ->groupBy('course_skill_id')
            ->selectRaw("course_skill_id, SUM(result = 'pass') as passed, COUNT(*) as total")
            ->get()
            ->keyBy('course_skill_id');

        $skills = DB::table('tbl_course_categories as c')
            ->join('tbl_course_skills as s', 's.course_category_id', '=', 'c.course_category_id')
            ->where('c.course_id', $course_id)
            ->whereNull('c.deleted_at')
            ->whereNull('s.deleted_at')
            ->orderBy('c.course_category_id')
            ->orderBy('s.course_skill_id')
            ->get(['c.course_category_name', 's.course_skill_id', 's.course_skill_name'])
            ->map(function ($skill) use ($resultsBySkill) {
                $r     = $resultsBySkill->get($skill->course_skill_id);
                $total = $r ? (int) $r->total : 0;

                return [
                    'category'  => $skill->course_category_name,
                    'name'      => $skill->course_skill_name,
                    'assessed'  => $total,
                    'pass_rate' => $total > 0 ? round($r->passed / $total * 100) : null,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'stats'   => [
                'course_name'     => $course->course_name,
                'enrolled_total'  => $enrollments->count(),
                'active'          => $active->count(),
                'graduated'       => $graduated->count(),
                'dropped'         => $dropped->count(),
                'ready_to_grad'   => $readyToGrad,
                'completion_rate' => $completionRate,
                'avg_days_to_graduate' => $avgDays,
                'collected'       => round($collected, 2),
                'outstanding'     => round($outstanding, 2),
                'skills'          => $skills,
            ],
        ]);
    }

    /**
     * Equivalent of add_course.php.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($this->nameTaken($data['course_name'])) {
            return response()->json([
                'success' => false,
                'message' => "A course named \"{$data['course_name']}\" already exists.",
            ], 422);
        }

        try {
            $course = DB::transaction(function () use ($data) {
                $course = Course::create([
                    'course_name'          => $data['course_name'],
                    'course_session_count' => $data['course_session_count'],
                    'course_price'         => $data['course_price'],
                ]);

                $seenCategoryNames = [];

                foreach ($data['categories'] ?? [] as $categoryInput) {
                    $categoryName = trim($categoryInput['name'] ?? '');

                    // New courses only: a "deleted" category the client
                    // never actually saved is just skipped.
                    if ($categoryName === '' || !empty($categoryInput['deleted'])) {
                        continue;
                    }

                    $key = mb_strtolower($categoryName);
                    if (isset($seenCategoryNames[$key])) {
                        throw new \RuntimeException("Duplicate category name: \"{$categoryName}\".");
                    }
                    $seenCategoryNames[$key] = true;

                    $category = $course->activeCategories()->create([
                        'course_category_name' => $categoryName,
                    ]);

                    $seenSkillNames = [];

                    foreach ($categoryInput['skills'] ?? [] as $skillInput) {
                        // Skills may arrive as plain strings or as
                        // { id, name, deleted } objects.
                        $skillName    = trim(is_array($skillInput) ? ($skillInput['name'] ?? '') : (string) $skillInput);
                        $skillDeleted = is_array($skillInput) && !empty($skillInput['deleted']);

                        if ($skillName === '' || $skillDeleted) {
                            continue;
                        }

                        $skillKey = mb_strtolower($skillName);
                        if (isset($seenSkillNames[$skillKey])) {
                            throw new \RuntimeException("Duplicate skill \"{$skillName}\" in category \"{$categoryName}\".");
                        }
                        $seenSkillNames[$skillKey] = true;

                        $category->skills()->create(['course_skill_name' => $skillName]);
                    }
                }

                return $course;
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Course added successfully.',
            'course_id' => $course->course_id,
        ]);
    }

    /**
     * Equivalent of edit_course.php.
     *
     * Categories/skills are upserted, not replaced: a row with an id gets
     * updated in place (name + deleted_at); a row without one is brand new,
     * so it gets inserted. "deleted" from the client means archive it, never
     * a hard delete. Anything the client already had but didn't send back
     * gets archived too, so a stale request can't silently resurrect an item
     * as active forever.
     */
    public function update(UpdateCourseRequest $request, int $course_id): JsonResponse
    {
        $data = $request->validated();

        $course = Course::find($course_id);

        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        if ($this->nameTaken($data['course_name'], $course_id)) {
            return response()->json([
                'success' => false,
                'message' => "A course named \"{$data['course_name']}\" already exists.",
            ], 422);
        }

        try {
            DB::transaction(function () use ($course, $data) {
                $course->update([
                    'course_name'          => $data['course_name'],
                    'course_session_count' => $data['course_session_count'],
                    'course_price'         => $data['course_price'],
                ]);

                // Existing categories (active + archived), each with its own
                // existing skills (active + archived), keyed by id.
                $existingCategories = $course->allCategories()->with('allSkills')->get()->keyBy('course_category_id');

                $seenCategoryNames = [];
                $touchedCategoryIds = [];

                foreach ($data['categories'] ?? [] as $categoryInput) {
                    $categoryName = trim($categoryInput['name'] ?? '');

                    if ($categoryName === '') {
                        continue;
                    }

                    $key = mb_strtolower($categoryName);
                    if (isset($seenCategoryNames[$key])) {
                        throw new \RuntimeException("Duplicate category name: \"{$categoryName}\".");
                    }
                    $seenCategoryNames[$key] = true;

                    $categoryDeleted = !empty($categoryInput['deleted']);
                    $categoryIdRaw   = $categoryInput['id'] ?? null;

                    if ($categoryIdRaw !== null && is_numeric($categoryIdRaw)) {
                        $categoryId = (int) $categoryIdRaw;

                        if (!$existingCategories->has($categoryId)) {
                            throw new \RuntimeException("Category \"{$categoryName}\" was not found on this course.");
                        }

                        $category = $existingCategories->get($categoryId);
                        $category->course_category_name = $categoryName;
                        $category->deleted_at = $categoryDeleted ? now() : null;
                        $category->save();
                    } else {
                        $category = $course->activeCategories()->create([
                            'course_category_name' => $categoryName,
                        ]);
                        if ($categoryDeleted) {
                            $category->deleted_at = now();
                            $category->save();
                        }
                        $existingCategories->put($category->course_category_id, $category);
                    }

                    $touchedCategoryIds[] = $category->course_category_id;

                    $existingSkillsById = ($category->allSkills ?? collect())->keyBy('course_skill_id');

                    $seenSkillNames = [];
                    $touchedSkillIds = [];

                    foreach ($categoryInput['skills'] ?? [] as $skillInput) {
                        $skillName = trim(is_array($skillInput) ? ($skillInput['name'] ?? '') : (string) $skillInput);

                        if ($skillName === '') {
                            continue;
                        }

                        $skillKey = mb_strtolower($skillName);
                        if (isset($seenSkillNames[$skillKey])) {
                            throw new \RuntimeException("Duplicate skill \"{$skillName}\" in category \"{$categoryName}\".");
                        }
                        $seenSkillNames[$skillKey] = true;

                        $skillDeleted = is_array($skillInput) && !empty($skillInput['deleted']);
                        $skillIdRaw   = is_array($skillInput) ? ($skillInput['id'] ?? null) : null;

                        if ($skillIdRaw !== null && is_numeric($skillIdRaw)) {
                            $skillId = (int) $skillIdRaw;

                            if (!$existingSkillsById->has($skillId)) {
                                throw new \RuntimeException("Skill \"{$skillName}\" was not found in category \"{$categoryName}\".");
                            }

                            $skill = $existingSkillsById->get($skillId);
                            $skill->course_skill_name = $skillName;
                            $skill->deleted_at = $skillDeleted ? now() : null;
                            $skill->save();
                            $touchedSkillIds[] = $skillId;
                        } else {
                            $skill = $category->skills()->create(['course_skill_name' => $skillName]);
                            $touchedSkillIds[] = $skill->course_skill_id;
                        }
                    }

                    // Skills the client already had for this category but
                    // didn't send back get archived.
                    $orphanedSkillIds = $existingSkillsById->keys()->diff($touchedSkillIds);
                    if ($orphanedSkillIds->isNotEmpty()) {
                        CourseSkill::whereIn('course_skill_id', $orphanedSkillIds)
                            ->whereNull('deleted_at')
                            ->update(['deleted_at' => now()]);
                    }
                }

                // Categories (and their skills) the client already had but
                // didn't send back get archived too.
                $orphanedCategoryIds = $existingCategories->keys()->diff($touchedCategoryIds);
                if ($orphanedCategoryIds->isNotEmpty()) {
                    CourseSkill::whereIn('course_category_id', $orphanedCategoryIds)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);

                    CourseCategory::whereIn('course_category_id', $orphanedCategoryIds)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success'   => true,
            'message'   => 'Course updated successfully.',
            'course_id' => $course->course_id,
        ]);
    }

    private function nameTaken(string $name, ?int $excludingCourseId = null): bool
    {
        return Course::whereRaw('LOWER(course_name) = ?', [mb_strtolower($name)])
            ->when($excludingCourseId, fn ($query) => $query->where('course_id', '!=', $excludingCourseId))
            ->exists();
    }
}