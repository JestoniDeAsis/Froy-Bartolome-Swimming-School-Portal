<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicantDashboardController extends Controller
{
    private const APPLICANT_ROLE_ID = 5;

    /**
     * Return the courses available to the signed-in applicant.
     */
    public function courses(Request $request): JsonResponse
    {
        if ((int) $request->user()->role_id !== self::APPLICANT_ROLE_ID) {
            abort(403);
        }

        $courses = Course::query()
            ->with('activeCategories:course_category_id,course_id,course_category_name')
            ->orderBy('course_name')
            ->get(['course_id', 'course_name', 'course_session_count', 'course_price'])
            ->map(fn (Course $course): array => [
                'id' => $course->course_id,
                'name' => $course->course_name,
                'sessionCount' => $course->course_session_count,
                'price' => $course->course_price,
                'categories' => $course->activeCategories
                    ->pluck('course_category_name')
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->values();

        return response()->json(['courses' => $courses]);
    }
}
