<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin Registers page.
 *
 * Four lists, all built from real data:
 *   Applicants  role 5 with no enrollment at all
 *   Students    enrollments without a graduation date
 *   Athletes    role 4
 *   Graduates   graduated at least once, NOT enrolled now, not an athlete
 *
 * Rules enforced here (not only in the page):
 *   - nobody can be enrolled in a course they already graduated from
 *   - nobody who is currently enrolled can be enrolled again
 *   - athletes (role 4) can never be enrolled
 *   - only applicants and graduates who are not enrolled now can become athletes
 */
class RegistersController extends Controller
{
    private const ADMIN_ROLE_ID     = 1;
    private const STUDENT_ROLE_ID   = 3;
    private const ATHLETE_ROLE_ID   = 4;
    private const APPLICANT_ROLE_ID = 5;

    /**
     * Called from HomeController's match($page) when $page === 'registers'.
     * Feeds the Registers blade every list plus the course options used by
     * the Enroll modal's dropdown.
     */
    public function data(): array
    {
        $courses = Course::orderBy('course_name')
            ->get(['course_id', 'course_name', 'course_price'])
            ->map(fn ($c) => [
                'id' => $c->course_id,
                'name' => $c->course_name,
                'price' => $c->course_price,
            ]);

        // user id => ids of the courses they already graduated from
        $graduatedCourseIds = $this->graduatedCourseIdsByUser();

        $enrollments = Enrollment::whereNull('graduation_at')
            ->with(['user.profile', 'course', 'payments'])
            ->get();

        return array_merge($this->lists(), [
            'students' => $enrollments->map(function ($e) use ($graduatedCourseIds) {
                $totalPaid = $e->payments->sum('amount');
                $balance = max($e->course_price_at_enrollment - $totalPaid, 0);

                return [
                    'id' => $e->enrollment_id,
                    'lastName' => $e->user->profile->last_name ?? '',
                    'firstName' => $e->user->profile->first_name ?? '',
                    'middleName' => $e->user->profile->middle_name ?? '',
                    'course' => $e->course->course_name ?? '',
                    'schedule' => $e->scheduled_time ?? '',
                    'paymentStatus' => $balance <= 0 ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Unpaid'),
                    'notes' => $e->notes ?? '',
                    'price' => $e->course_price_at_enrollment,
                    'balance' => $balance,
                    // courses this person can no longer take
                    'graduatedCourseIds' => $graduatedCourseIds->get($e->user_id, []),
                ];
            }),
            'courses' => $courses,
        ]);
    }

    /**
     * POST /registers/enroll
     * Called via fetch() from the Registers blade's Enroll modal, for an
     * applicant or a graduate.
     */
    public function enroll(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:tbl_users,user_id'],
            'course_id' => ['required', 'integer', 'exists:tbl_courses,course_id'],
            'scheduled_time' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $course = Course::findOrFail($validated['course_id']);

        if ($reason = $this->enrollBlockReason((int) $validated['user_id'], (int) $course->course_id)) {
            return response()->json(['success' => false, 'message' => $reason], 422);
        }

        $enrollment = Enrollment::create([
            'user_id' => $validated['user_id'],
            'course_id' => $course->course_id,
            'course_price_at_enrollment' => $course->course_price,
            'enrollment_at' => now(),
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $enrollment->enrollment_id,
                'course' => $course->course_name,
                'schedule' => $enrollment->scheduled_time ?? '',
                'paymentStatus' => 'Unpaid',
                'notes' => $enrollment->notes ?? '',
                'price' => $enrollment->course_price_at_enrollment,
                'balance' => $enrollment->course_price_at_enrollment,
            ],
        ]);
    }

    /**
     * PUT /registers/students/{enrollment}
     * Called via fetch() from the Students tab's "Course update" button.
     * When the course actually changes, course_price_at_enrollment is
     * re-stamped to the new course's current price, so the balance reflects
     * the new price (payments already made are kept). If the course is
     * unchanged, the existing price snapshot is left alone.
     */
    public function updateCourse(Request $request, Enrollment $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:tbl_courses,course_id'],
            'scheduled_time' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $newCourse = Course::findOrFail($validated['course_id']);

        $changes = [
            'course_id' => $newCourse->course_id,
            'scheduled_time' => $validated['scheduled_time'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        if ((int) $enrollment->course_id !== (int) $newCourse->course_id) {
            if ($this->hasGraduatedFrom((int) $enrollment->user_id, (int) $newCourse->course_id)) {
                return response()->json(['success' => false, 'message' => 'This person has already graduated from that course.'], 422);
            }

            $changes['course_price_at_enrollment'] = $newCourse->course_price;
        }

        $enrollment->update($changes);

        $totalPaid = $enrollment->payments()->sum('amount');
        $balance = max($enrollment->course_price_at_enrollment - $totalPaid, 0);

        return response()->json([
            'success' => true,
            'price' => $enrollment->course_price_at_enrollment,
            'balance' => $balance,
            'paymentStatus' => $balance <= 0 ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Unpaid'),
        ]);
    }

    /**
     * POST /registers/students/{enrollment}/payments
     * Called via fetch() from the Students tab's "Payment update" button.
     * Each call records one payment row — it never edits a past payment,
     * so balance/status are always recomputed as (price - sum of payments).
     * A payment larger than the remaining balance is rejected (422).
     */
    public function addPayment(Request $request, Enrollment $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $amount = round((float) $validated['amount'], 2);

        // Lock the enrollment row so two simultaneous payments can't both
        // pass the balance check and together overpay.
        DB::transaction(function () use ($enrollment, $validated, $amount) {
            $locked = Enrollment::whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

            $remaining = round(
                (float) $locked->course_price_at_enrollment - (float) $locked->payments()->sum('amount'),
                2
            );

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['This enrollment is already fully paid.'],
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment of ₱' . number_format($amount, 2)
                        . ' exceeds the remaining balance of ₱' . number_format($remaining, 2) . '.'],
                ]);
            }

            CoursePayment::create([
                'student_id' => $locked->enrollment_id,
                'enrollment_id' => $locked->enrollment_id,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'paid_at' => now(),
                'received_by' => Auth::id(),
            ]);
        });

        $totalPaid = $enrollment->payments()->sum('amount');
        $balance = max($enrollment->course_price_at_enrollment - $totalPaid, 0);

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'paymentStatus' => $balance <= 0 ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Unpaid'),
        ]);
    }

    /**
     * POST /registers/athletes     body: { user_ids: [1, 2, ...] }
     * Turns applicants and graduates into athletes (role 4). Everyone in the
     * request must be eligible, otherwise nobody is changed:
     *   - not already an athlete
     *   - NOT enrolled in a course right now
     *   - an applicant, or someone who graduated before
     * Replies with the refreshed Applicants / Graduates / Athletes lists.
     */
    public function addAthletes(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:tbl_users,user_id'],
        ]);

        $ids = array_map('intval', $validated['user_ids']);

        $users = User::with('profile')->whereIn('user_id', $ids)->get();

        $enrolledNow = Enrollment::whereIn('user_id', $ids)
            ->whereNull('graduation_at')
            ->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        $enrolledBefore = Enrollment::whereIn('user_id', $ids)
            ->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->all();

        foreach ($users as $user) {
            $id = (int) $user->user_id;
            $name = $this->personName($user);

            if ((int) $user->role_id === self::ATHLETE_ROLE_ID) {
                return $this->fail("{$name} is already an athlete.");
            }

            if (in_array($id, $enrolledNow, true)) {
                return $this->fail("{$name} is enrolled in a course right now. Only people who are not enrolled can become athletes.");
            }

            if (! in_array($id, $enrolledBefore, true) && (int) $user->role_id !== self::APPLICANT_ROLE_ID) {
                return $this->fail("{$name} is not an applicant or a graduate.");
            }
        }

        DB::table('tbl_users')->whereIn('user_id', $ids)->update(['role_id' => self::ATHLETE_ROLE_ID]);

        return response()->json(array_merge(['success' => true], $this->lists()));
    }

    /**
     * POST /registers/athletes/{user}/revoke
     * Takes athlete status away. The person goes back to Graduates if they
     * were ever enrolled (role 3), otherwise back to Applicants (role 5).
     * Replies with the refreshed lists.
     */
    public function revokeAthlete(Request $request, int $user): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $roleId = DB::table('tbl_users')->where('user_id', $user)->value('role_id');

        if ($roleId === null) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        if ((int) $roleId !== self::ATHLETE_ROLE_ID) {
            return $this->fail('This person is not an athlete.');
        }

        $enrolledBefore = Enrollment::where('user_id', $user)->exists();

        DB::table('tbl_users')->where('user_id', $user)->update([
            'role_id' => $enrolledBefore ? self::STUDENT_ROLE_ID : self::APPLICANT_ROLE_ID,
        ]);

        return response()->json(array_merge(['success' => true], $this->lists()));
    }

    /* ---------------------------------------------------------
     * Lists
     * --------------------------------------------------------- */

    /** The three lists that change when someone becomes / stops being an athlete. */
    private function lists(): array
    {
        return [
            'registrations' => $this->applicantRows(),
            'graduates' => $this->graduateRows(),
            'athletes' => $this->athleteRows(),
        ];
    }

    private function applicantRows(): Collection
    {
        $profiles = UserProfile::whereHas('user', function ($q) {
            $q->where('role_id', self::APPLICANT_ROLE_ID)->whereDoesntHave('enrollments');
        })->with('user')->get();

        return $profiles->map(fn ($p) => array_merge([
            'id' => $p->user_id,
            'lastName' => $p->last_name ?? '',
            'firstName' => $p->first_name ?? '',
            'middleName' => $p->middle_name ?? '',
            'email' => $p->user->email_address ?? '',
        ], $this->birthFields($p)))->values();
    }

    /**
     * Graduates: graduated at least once, not enrolled right now, not an
     * athlete. One row per person, even if they graduated from several courses.
     * graduatedCourseIds is what the page uses to hide courses they already finished.
     */
    private function graduateRows(): Collection
    {
        $graduated = Enrollment::with('course')
            ->whereNotNull('graduation_at')
            ->orderByDesc('graduation_at')
            ->get()
            ->groupBy('user_id');

        $enrolledNow = Enrollment::whereNull('graduation_at')->pluck('user_id')->unique()->all();

        $users = User::with('profile')
            ->whereIn('user_id', $graduated->keys()->all())
            ->whereNotIn('user_id', $enrolledNow)
            ->where('role_id', '!=', self::ATHLETE_ROLE_ID)
            ->get();

        return $users->map(function ($user) use ($graduated) {
            $mine = $graduated->get($user->user_id);   // newest first
            $last = $mine->first();
            $profile = $user->profile;

            return array_merge([
                'id' => $user->user_id,
                'lastName' => $profile->last_name ?? '',
                'firstName' => $profile->first_name ?? '',
                'middleName' => $profile->middle_name ?? '',
                'email' => $user->email_address ?? '',
                'lastCourse' => $last->course->course_name ?? '',
                'graduatedAt' => Carbon::parse($last->graduation_at)->format('M-d-Y'),
                'graduatedCourseIds' => $mine->pluck('course_id')->unique()->values()->all(),
                'graduatedCourses' => $mine->map(fn ($e) => $e->course->course_name ?? '')->filter()->unique()->values()->all(),
            ], $this->birthFields($profile));
        })->sortBy('lastName', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    private function athleteRows(): Collection
    {
        return User::with('profile')
            ->where('role_id', self::ATHLETE_ROLE_ID)
            ->get()
            ->map(fn ($user) => array_merge([
                'id' => $user->user_id,
                'lastName' => $user->profile->last_name ?? '',
                'firstName' => $user->profile->first_name ?? '',
                'middleName' => $user->profile->middle_name ?? '',
                'email' => $user->email_address ?? '',
            ], $this->birthFields($user->profile)))
            ->sortBy('lastName', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /** user id => ids of the courses that person already graduated from. */
    private function graduatedCourseIdsByUser(): Collection
    {
        return Enrollment::whereNotNull('graduation_at')
            ->get(['user_id', 'course_id'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('course_id')->unique()->values()->all());
    }

    private function birthFields(?UserProfile $profile): array
    {
        $birthdate = $profile->birthdate ?? null;

        return [
            'birthdate' => $birthdate ? Carbon::parse($birthdate)->format('M-d-Y') : '',
            'age' => $birthdate ? Carbon::parse($birthdate)->age : '',
        ];
    }

    /* ---------------------------------------------------------
     * Rules / helpers
     * --------------------------------------------------------- */

    private function hasGraduatedFrom(int $userId, int $courseId): bool
    {
        return Enrollment::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereNotNull('graduation_at')
            ->exists();
    }

    /** Why this person cannot be enrolled in this course, or null when it is fine. */
    private function enrollBlockReason(int $userId, int $courseId): ?string
    {
        $roleId = (int) DB::table('tbl_users')->where('user_id', $userId)->value('role_id');

        if ($roleId === self::ATHLETE_ROLE_ID) {
            return 'Athletes cannot be enrolled in a course.';
        }

        if (Enrollment::where('user_id', $userId)->whereNull('graduation_at')->exists()) {
            return 'This person is already enrolled in a course. Use Course update to change it.';
        }

        if ($this->hasGraduatedFrom($userId, $courseId)) {
            return 'This person has already graduated from this course.';
        }

        return null;
    }

    private function denyUnlessAdmin(): ?JsonResponse
    {
        if ((int) Auth::user()->role_id !== self::ADMIN_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only the admin can do this.'], 403);
        }

        return null;
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], 422);
    }

    /** "Last, First" from the profile; falls back to the username. */
    private function personName(User $user): string
    {
        $profile = $user->profile;
        $last = trim((string) ($profile->last_name ?? ''));
        $first = trim((string) ($profile->first_name ?? ''));

        return $last !== '' ? "{$last}, {$first}" : (string) ($user->username ?? 'This person');
    }
}