<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSkill;
use Illuminate\Http\JsonResponse;
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