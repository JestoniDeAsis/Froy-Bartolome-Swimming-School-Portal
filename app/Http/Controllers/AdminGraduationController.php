<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSkill;
use App\Models\Enrollment;
use App\Models\SkillAssessment;
use App\Models\SkillAssessmentResult;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Admin Graduation page.
 *
 * Flow of one enrollment (the admin's part is the last two steps):
 *   Active Students      still learning, or the coach has not approved yet
 *   Ready for Graduation the coach marked the student eligible (all skills
 *                        passed, or the coach's "eligible" override). The admin
 *                        reviews it here, e.g. the balance.
 *   Graduating           the admin moved the student here (cleared_for_graduation_at is set;
 *                        the Back button clears it again).
 *                        Only allowed when the student is FULLY PAID.
 *                        The admin uploads the certificate PDF in this tab.
 *   Graduates            the admin confirmed it -> graduation_at is set
 *                        (needs the certificate and no balance)
 *
 * The certificate PDF is stored in the database, in its own table
 * (tbl_enrollment_certificates: enrollment_id, file_name, file_data, uploaded_at).
 * It is a separate table on purpose, so the PDF is not loaded every time an
 * enrollment is read.
 */
class AdminGraduationController extends Controller
{
    private const ADMIN_ROLE_ID = 1;

    private const CERTIFICATES_TABLE = 'tbl_enrollment_certificates';

    /**
     * Data for the page (HomeController passes it to graduation_blade as $graduation).
     *
     * - active / ready / graduating / graduated: lists of students, one tab each
     * - assessments: skills results + payment details keyed by enrollment id,
     *   for every student on the page (used by the popup)
     *
     * Sessions attended = attendance rows with a Time In (same rule as the
     * Attendance page).
     */
    public function pageData(): array
    {
        $columns = [
            'e.enrollment_id',
            'e.enrollment_at',
            'e.graduation_at',
            'e.cleared_for_graduation_at',
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
            ->whereNull('e.dropped_at')   // dropped students are not part of graduation
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

        // Total paid per enrollment. A payment belongs to its enrollment_id
        // (foreign key to tbl_enrollments), same rule as Sales & Invoice.
        $paidByEnrollment = $allIds->isEmpty()
            ? collect()
            : DB::table('tbl_course_payments')
                ->whereNull('archived_at')   // archived payments don't count toward what was paid
                ->whereIn('enrollment_id', $allIds)
                ->groupBy('enrollment_id')
                ->selectRaw('enrollment_id as eid, SUM(amount) as paid')
                ->pluck('paid', 'eid');

        // Archived payments per enrollment: not counted, but the popup says how many were set aside.
        $archivedByEnrollment = $allIds->isEmpty()
            ? collect()
            : DB::table('tbl_course_payments')
                ->whereNotNull('archived_at')
                ->whereIn('enrollment_id', $allIds)
                ->groupBy('enrollment_id')
                ->selectRaw('enrollment_id as eid, COUNT(*) as n, SUM(amount) as total')
                ->get()
                ->keyBy('eid');

        // Which enrollments already have a certificate (name only, the PDF itself is not loaded).
        $certificates = $allIds->isEmpty()
            ? collect()
            : DB::table(self::CERTIFICATES_TABLE)
                ->whereIn('enrollment_id', $allIds)
                ->select('enrollment_id', 'file_name')
                ->get()
                ->keyBy('enrollment_id');

        $toRow = function ($e) use ($sessions, $certificates) {
            $given = trim($e->first_name . ' ' . ($e->middle_name ?? ''));
            $name  = trim($e->last_name . ', ' . $given . ($e->name_suffix ? ' ' . $e->name_suffix : ''));
            $row   = $sessions->get($e->enrollment_id);

            return [
                'id'              => (int) $e->enrollment_id,
                'name'            => $name,
                'courseId'        => $e->course_id !== null ? (int) $e->course_id : null,
                'course'          => $e->course_name ?? 'No course',
                'schedule'        => $e->scheduled_time ?: null,
                'enrolledAt'      => $e->enrollment_at ? substr((string) $e->enrollment_at, 0, 10) : null,
                'graduatedAt'     => $e->graduation_at ? substr((string) $e->graduation_at, 0, 10) : null,
                'done'            => $row ? (int) $row->sessions : 0,
                'total'           => max(0, (int) $e->course_session_count),
                'lastSession'     => $row && $row->last_session ? substr((string) $row->last_session, 0, 10) : null,
                'graduating'      => ! empty($e->cleared_for_graduation_at),
                'hasCertificate'  => $certificates->has($e->enrollment_id),
                'certificateName' => $certificates->get($e->enrollment_id)->file_name ?? null,
            ];
        };

        $active    = $activeEnrollments->map($toRow)->values();
        $graduated = $graduatedEnrollments->map($toRow)->values();

        $assessments = $this->assessmentDetails($enrollmentById, $paidByEnrollment, $archivedByEnrollment);

        // Sort the active students into their tab.
        $stillActive = [];
        $ready       = [];
        $graduating  = [];

        foreach ($active as $row) {
            $detail   = $assessments[$row['id']] ?? [];
            $eligible = ($detail['status'] ?? null) === 'eligible';

            // The coach did not approve, but the student did every session and passed
            // enough skills. The admin can mark them ready (see markReady()).
            $row['canMarkReady'] = ! $eligible && $this->meetsReadyRule(
                $row['done'],
                $row['total'],
                (int) ($detail['passed'] ?? 0),
                (int) ($detail['total'] ?? 0)
            );

            if ($row['graduating']) {
                $graduating[] = $row;
            } elseif ($eligible) {
                $ready[] = $row;
            } else {
                $stillActive[] = $row;
            }
        }

        // The popup opens from every tab now (View Progress on the Active Students
        // tab too), so it gets the details of every student on the page.
        return [
            'active'      => $stillActive,
            'ready'       => $ready,
            'graduating'  => $graduating,
            'graduated'   => $graduated->all(),
            'assessments' => $assessments,
        ];
    }

    /**
     * GET /graduation/data
     * The same data the page is rendered with, as JSON. The page asks for it
     * every few seconds to stay current (see the auto-refresh in graduation_blade).
     */
    public function data(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        return response()->json(['success' => true] + $this->pageData());
    }

    /**
     * POST /graduation/{enrollment}/certificate   (field: certificate = PDF)
     * Upload (or replace) the certificate. Only for students in the Graduating tab.
     */
    public function uploadCertificate(Request $request, int $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $row = Enrollment::find($enrollment);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        if ($row->graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has already graduated.'], 422);
        }

        if ($row->dropped_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has been dropped.'], 422);
        }

        if ($row->cleared_for_graduation_at === null) {
            return response()->json(['success' => false, 'message' => 'Move the student to Graduating before uploading the certificate.'], 422);
        }

        // PHP can refuse a file before validation runs (e.g. bigger than upload_max_filesize).
        // Say why instead of a generic error.
        $incoming = $request->file('certificate');

        if ($incoming && ! $incoming->isValid()) {
            return response()->json(['success' => false, 'message' => $incoming->getErrorMessage()], 422);
        }

        $request->validate([
            'certificate' => ['required', 'file', 'mimes:pdf', 'max:92160'], // PDF, up to 90 MiB
        ]);

        $file = $incoming;

        // One certificate per enrollment: uploading again replaces the old one.
        DB::table(self::CERTIFICATES_TABLE)->updateOrInsert(
            ['enrollment_id' => $row->enrollment_id],
            [
                'file_name'   => mb_substr($file->getClientOriginalName(), 0, 255),
                'file_data'   => file_get_contents($file->getRealPath()),
                'uploaded_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Certificate uploaded.']);
    }

    /**
     * POST /graduation/{enrollment}/graduate
     * Final step: marks the student as graduated today. Needs the Graduating step,
     * the coach's approval, no balance and an uploaded certificate.
     */
    public function graduate(Request $request, int $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $row = Enrollment::find($enrollment);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        if ($row->graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has already graduated.'], 422);
        }

        if ($row->dropped_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has been dropped.'], 422);
        }

        if ($row->cleared_for_graduation_at === null) {
            return response()->json(['success' => false, 'message' => 'Move the student to Graduating first.'], 422);
        }

        if (! $this->isEligible($row)) {
            return response()->json(['success' => false, 'message' => 'The coach has not marked this student as eligible to graduate.'], 422);
        }

        if (($balance = $this->balanceFor($row)) > 0) {
            return response()->json(['success' => false, 'message' => 'This student still has a balance of ₱' . number_format($balance, 2) . '.'], 422);
        }

        if (! DB::table(self::CERTIFICATES_TABLE)->where('enrollment_id', $row->enrollment_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Upload the certificate PDF before marking the student as graduated.'], 422);
        }

        $row->forceFill(['graduation_at' => now()])->save();

        return response()->json(['success' => true, 'message' => 'Student marked as graduated.']);
    }

    /**
     * POST /graduation/{enrollment}/mark-ready
     * The admin marks a student as ready when the coach forgot. Allowed only when
     * the student attended at least all the course sessions AND passed at least
     * PASSING_PERCENT % of the course's active skills (same grade as the coach page).
     */
    public function markReady(Request $request, int $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $row = Enrollment::with('course')->find($enrollment);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        if ($row->graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has already graduated.'], 422);
        }

        if ($row->dropped_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has been dropped.'], 422);
        }

        $assessment = SkillAssessment::query()
            ->with('results')
            ->where('enrollment_id', $row->enrollment_id)
            ->first();

        if (! $assessment) {
            return response()->json(['success' => false, 'message' => 'This student has no skills assessment yet.'], 422);
        }

        if ($this->isEligible($row)) {
            return response()->json(['success' => false, 'message' => 'This student is already marked as ready.'], 422);
        }

        // Sessions: attendance rows with a Time In (same rule as the page).
        $done         = DB::table('tbl_attendances')
            ->where('enrollment_id', $row->enrollment_id)
            ->whereNotNull('time_in')
            ->count();
        $sessionTotal = (int) ($row->course->course_session_count ?? 0);

        // Skills: active skills of the course only.
        $course   = Course::query()
            ->where('course_id', $row->course_id)
            ->with('activeCategories.skills')
            ->first();
        $skillIds = collect($course?->activeCategories ?? [])
            ->flatMap(fn ($category) => $category->skills->pluck('course_skill_id'));
        $total    = $skillIds->count();
        $passed   = $assessment->results
            ->where('result', SkillAssessmentResult::PASS)
            ->pluck('course_skill_id')
            ->intersect($skillIds)
            ->count();

        if (! $this->meetsReadyRule($done, $sessionTotal, $passed, $total)) {
            $percent = $total > 0 ? round($passed / $total * 100, 1) : 0;

            return response()->json([
                'success' => false,
                'message' => "Not allowed yet. Sessions: {$done} of {$sessionTotal}. Skills passed: {$passed} of {$total} ({$percent}%). "
                    . 'The student needs all sessions and at least ' . CoachSkillsAssessmentController::PASSING_PERCENT . '% of the skills.',
            ], 422);
        }

        $assessment->forceFill(['ready_to_graduate' => true])->save();

        return response()->json(['success' => true, 'message' => 'Student marked as ready for graduation.']);
    }

    /** Sessions done >= course sessions AND skills passed >= the passing percent of all skills. */
    private function meetsReadyRule(int $done, int $sessionTotal, int $passed, int $skillTotal): bool
    {
        if ($sessionTotal <= 0 || $skillTotal <= 0) {
            return false;
        }

        return $done >= $sessionTotal
            && $passed * 100 >= CoachSkillsAssessmentController::PASSING_PERCENT * $skillTotal;
    }

    /**
     * POST /graduation/{enrollment}/graduating
     * Moves a student from Ready for Graduation to Graduating. Blocked while
     * the student still has a balance.
     */
    public function markGraduating(Request $request, int $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $row = Enrollment::find($enrollment);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        if ($row->graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has already graduated.'], 422);
        }

        if ($row->dropped_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has been dropped.'], 422);
        }

        if ($row->cleared_for_graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student is already in Graduating.'], 422);
        }

        if (! $this->isEligible($row)) {
            return response()->json(['success' => false, 'message' => 'The coach has not marked this student as eligible to graduate yet.'], 422);
        }

        $balance = $this->balanceFor($row);

        if ($balance > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This student still has a balance of ₱' . number_format($balance, 2) . ' and cannot be moved to Graduating yet.',
            ], 422);
        }

        $row->forceFill(['cleared_for_graduation_at' => now()])->save();

        return response()->json(['success' => true, 'message' => 'Student moved to Graduating. Upload the certificate to finish.']);
    }

    /**
     * POST /graduation/{enrollment}/ready
     * Back button: takes a student out of Graduating and returns them to Ready for
     * Graduation. An uploaded certificate is kept, so it is not lost.
     */
    public function moveBackToReady(Request $request, int $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin($request)) {
            return $denied;
        }

        $row = Enrollment::find($enrollment);

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Enrollment not found.'], 404);
        }

        if ($row->graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'This student has already graduated.'], 422);
        }

        if ($row->cleared_for_graduation_at === null) {
            return response()->json(['success' => false, 'message' => 'This student is not in Graduating.'], 422);
        }

        $row->forceFill(['cleared_for_graduation_at' => null])->save();

        return response()->json(['success' => true, 'message' => 'Student moved back to Ready for Graduation.']);
    }

    /** What the student still owes: price at enrollment minus every ACTIVE (not archived) payment of that enrollment. */
    private function balanceFor(Enrollment $enrollment): float
    {
        $price = (float) ($enrollment->course_price_at_enrollment ?? $enrollment->course->course_price ?? 0);

        $paid = (float) DB::table('tbl_course_payments')
            ->whereNull('archived_at')
            ->whereRaw('enrollment_id = ?', [$enrollment->enrollment_id])
            ->sum('amount');

        return max(round($price - $paid, 2), 0);
    }

    private function denyUnlessAdmin(Request $request): ?JsonResponse
    {
        if ((int) $request->user()->role_id !== self::ADMIN_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only the admin can do this.'], 403);
        }

        return null;
    }

    /**
     * Same rule as the coach page: eligible when the coach saved an assessment
     * AND either ticked "eligible to graduate" or every active skill is passed.
     */
    private function isEligible(Enrollment $enrollment): bool
    {
        $assessment = SkillAssessment::query()
            ->with('results')
            ->where('enrollment_id', $enrollment->enrollment_id)
            ->first();

        if (! $assessment) {
            return false;
        }

        if ($assessment->ready_to_graduate) {
            return true;
        }

        $skillIds = CourseSkill::query()
            ->whereIn(
                'course_category_id',
                CourseCategory::query()->where('course_id', $enrollment->course_id)->select('course_category_id')
            )
            ->pluck('course_skill_id');

        if ($skillIds->isEmpty()) {
            return false;
        }

        $passed = $assessment->results
            ->where('result', SkillAssessmentResult::PASS)
            ->pluck('course_skill_id');

        return $skillIds->diff($passed)->isEmpty();
    }

    /**
     * Skills results + payment details for every enrollment, keyed by enrollment id.
     *
     * @param  Collection $enrollmentById  rows from the page query, keyed by enrollment_id
     * @param  Collection $paidByEnrollment  enrollment id => total paid (active payments only)
     * @param  Collection $archivedByEnrollment  enrollment id => {n, total} of archived payments
     */
    private function assessmentDetails(Collection $enrollmentById, Collection $paidByEnrollment, ?Collection $archivedByEnrollment = null): array
    {
        $ids = $enrollmentById->keys();

        if ($ids->isEmpty()) {
            return [];
        }

        $saved = SkillAssessment::query()
            ->with(['results', 'assessedBy.profile'])
            ->whereIn('enrollment_id', $ids)
            ->get()
            ->keyBy('enrollment_id');

        $courseIds = $ids
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

        $assessments = [];

        foreach ($ids as $id) {
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
                'archived_count'    => (int) ($archivedByEnrollment?->get($id)->n ?? 0),
                'archived_total'    => (float) ($archivedByEnrollment?->get($id)->total ?? 0),
            ];
        }

        return $assessments;
    }

    /** "First Last" of the coach who saved the assessment; falls back to the username. */
    private function coachName(SkillAssessment $assessment): ?string
    {
        $coach   = $assessment->assessedBy;
        $profile = $coach?->profile;

        $name = trim((string) ($profile->first_name ?? '') . ' ' . (string) ($profile->last_name ?? ''));

        return $name !== '' ? $name : ($coach->username ?? null);
    }
}