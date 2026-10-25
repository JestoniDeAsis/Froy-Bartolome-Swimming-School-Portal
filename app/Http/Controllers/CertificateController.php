<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\SkillAssessment;
use App\Models\SkillAssessmentResult;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Graduation certificates. The PDF is stored in the database
 * (tbl_enrollment_certificates) and can only be read through here:
 *   - the admin can open any certificate
 *   - a student can open their own, once they have graduated
 */
class CertificateController extends Controller
{
    private const ADMIN_ROLE_ID = 1;

    /**
     * Data for the student Certificates page (certificates_blade).
     * Only graduated enrollments that have an uploaded certificate are listed,
     * newest first. The PDF itself is not loaded here, just its details.
     */
    public function pageData(User $user): array
    {
        $certificates = DB::table('tbl_enrollment_certificates as cert')
            ->join('tbl_enrollments as e', 'e.enrollment_id', '=', 'cert.enrollment_id')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 'e.course_id')
            ->where('e.user_id', $user->getKey())
            ->whereNotNull('e.graduation_at')
            ->orderByDesc('e.graduation_at')
            ->get(['e.enrollment_id', 'e.course_id', 'e.enrollment_at', 'e.graduation_at', 'c.course_name', 'cert.file_name']);

        $assessments = $this->assessmentsFor($certificates);

        $day = fn ($value) => $value ? Carbon::parse($value)->format('M d, Y') : null;

        return [
            'certificates' => $certificates->map(fn ($row) => [
                'id'          => (int) $row->enrollment_id,
                'course'      => $row->course_name ?? 'Course',
                'enrolledAt'  => $day($row->enrollment_at),
                'graduatedAt' => $day($row->graduation_at),
                'fileName'    => $row->file_name,
                'assessment'  => $assessments[(int) $row->enrollment_id] ?? null,
            ])->all(),
        ];
    }

    /**
     * The student's skill assessment for each certificate, keyed by enrollment id.
     * Same data as the admin Graduation popup: categories, each skill's result,
     * who assessed and when. null when the coach never saved an assessment.
     */
    private function assessmentsFor($rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $saved = SkillAssessment::query()
            ->with(['results', 'assessedBy.profile'])
            ->whereIn('enrollment_id', $rows->pluck('enrollment_id'))
            ->get()
            ->keyBy('enrollment_id');

        $courses = Course::query()
            ->whereIn('course_id', $rows->pluck('course_id')->filter()->unique())
            ->with([
                'activeCategories'        => fn ($query) => $query->orderBy('course_category_id'),
                'activeCategories.skills' => fn ($query) => $query->orderBy('course_skill_id'),
            ])
            ->get()
            ->keyBy('course_id');

        $out = [];

        foreach ($rows as $row) {
            $assessment = $saved->get($row->enrollment_id);

            if (! $assessment) {
                continue;
            }

            $results = $assessment->results->pluck('result', 'course_skill_id');
            $total   = 0;
            $passed  = 0;
            $groups  = [];

            foreach ($courses->get($row->course_id)?->activeCategories ?? [] as $category) {
                $skills         = [];
                $categoryPassed = 0;

                foreach ($category->skills as $skill) {
                    $result = $results->get($skill->course_skill_id);   // null = not marked

                    $total++;
                    if ($result === SkillAssessmentResult::PASS) {
                        $passed++;
                        $categoryPassed++;
                    }

                    $skills[] = ['name' => $skill->course_skill_name, 'result' => $result];
                }

                $groups[] = [
                    'name'   => $category->course_category_name,
                    'passed' => $categoryPassed,
                    'total'  => count($skills),
                    'skills' => $skills,
                ];
            }

            $coach   = $assessment->assessedBy;
            $name    = trim(($coach?->profile->first_name ?? '') . ' ' . ($coach?->profile->last_name ?? ''));
            $dateRaw = $assessment->updated_at ?? $assessment->assessed_at;

            $out[(int) $row->enrollment_id] = [
                'passed'     => $passed,
                'total'      => $total,
                'assessedBy' => $name !== '' ? $name : ($coach->username ?? null),
                'assessedAt' => $dateRaw ? Carbon::parse($dateRaw)->format('M d, Y') : null,
                'categories' => $groups,
            ];
        }

        return $out;
    }

    /**
     * GET /certificates/{enrollment}            opens the PDF in the browser
     * GET /certificates/{enrollment}?download=1 downloads it
     */
    public function show(Request $request, int $enrollment)
    {
        $user = $request->user();
        $row  = Enrollment::find($enrollment);

        abort_unless($row, 404);

        $isAdmin = (int) $user->role_id === self::ADMIN_ROLE_ID;
        $isOwner = (int) $row->user_id === (int) $user->getKey() && $row->graduation_at !== null;

        abort_unless($isAdmin || $isOwner, 403);

        $certificate = DB::table('tbl_enrollment_certificates')
            ->where('enrollment_id', $enrollment)
            ->first(['file_name', 'file_data']);

        abort_unless($certificate, 404);

        $name     = $certificate->file_name ?: 'certificate.pdf';
        $fallback = str_replace(['%', '/', '\\', '"'], '', Str::ascii($name)) ?: 'certificate.pdf';

        $disposition = $request->boolean('download')
            ? HeaderUtils::DISPOSITION_ATTACHMENT
            : HeaderUtils::DISPOSITION_INLINE;

        return response($certificate->file_data, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name, $fallback),
        ]);
    }
}