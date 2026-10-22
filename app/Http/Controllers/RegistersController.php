<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Admin Registers page.
 *
 * Three lists, all built from real data (Athletes has its own page now):
 *   Applicants  role 5 with no enrollment at all
 *   Students    enrollments without a graduation date
 *   Graduates   graduated at least once, NOT enrolled now, not an athlete
 *
 * Rules enforced here (not only in the page):
 *   - nobody can be enrolled in a course they already graduated from
 *   - nobody who is currently enrolled can be enrolled again
 *   - athletes (role 4) can never be enrolled
 *   - only applicants and graduates who are not enrolled now can become athletes
 *   - an enrollment can only be cancelled (deleted) when it has no payments
 *     and no attendance records
 */
class RegistersController extends Controller
{
    private const ADMIN_ROLE_ID     = 1;
    private const STUDENT_ROLE_ID   = 3;
    private const ATHLETE_ROLE_ID   = 4;
    private const APPLICANT_ROLE_ID = 5;

    // Table that holds student attendance rows (must have an enrollment_id column).
    // CHANGE this to your real attendance table name.
    private const ATTENDANCE_TABLE = 'tbl_attendances';

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
            ->orderByDesc('enrollment_at')
            ->get();

        // enrollment id => number of attendance rows logged for it
        $attendanceCounts = $this->attendanceCountsByEnrollment($enrollments->pluck('enrollment_id')->all());

        return array_merge($this->lists(), [
            'students' => $enrollments
                ->map(fn ($e) => $this->studentRow($e, $graduatedCourseIds->get($e->user_id, []), $attendanceCounts))
                ->values(),
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

        // Create the enrollment and promote an applicant (role 5) to student
        // (role 3) together, so one can't succeed without the other.
        $enrollment = DB::transaction(function () use ($validated, $course) {
            $enrollment = Enrollment::create([
                'user_id' => $validated['user_id'],
                'course_id' => $course->course_id,
                'course_price_at_enrollment' => $course->course_price,
                'enrollment_at' => now(),
                'scheduled_time' => $validated['scheduled_time'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Only applicants are promoted. Graduates are already role 3,
            // and athletes (role 4) were rejected by enrollBlockReason().
            DB::table('tbl_users')
                ->where('user_id', $validated['user_id'])
                ->where('role_id', self::APPLICANT_ROLE_ID)
                ->update(['role_id' => self::STUDENT_ROLE_ID]);

            return $enrollment;
        });

        // Send the full row (same shape as data()) so the page can show the
        // enrolled date/time, names, balance, etc. right away without a refresh.
        $enrollment->load(['user.profile', 'course', 'payments']);

        return response()->json([
            'success' => true,
            'student' => $this->studentRow(
                $enrollment,
                $this->graduatedCourseIdsByUser((int) $validated['user_id'])->get((int) $validated['user_id'], []),
                []
            ),
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
     * DELETE /registers/students/{enrollment}
     * Called via fetch() from the Students tab's "Cancel Enrollment" button.
     * Cancels an enrollment by deleting it, but only when it has no payments
     * and no attendance. If the person has no enrollment left at all, they go
     * back to applicant (role 5). The response says where the person landed
     * (applicant list, graduate list, or neither) so the page can update itself.
     */
    public function destroy(Enrollment $enrollment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        if ($enrollment->graduation_at !== null) {
            return response()->json(['success' => false, 'message' => 'A graduated enrollment cannot be cancelled.'], 422);
        }

        if ($enrollment->payments()->exists()) {
            return response()->json(['success' => false, 'message' => 'This enrollment already has payment records, so it cannot be cancelled.'], 422);
        }

        if (($this->attendanceCountsByEnrollment([$enrollment->enrollment_id])[$enrollment->enrollment_id] ?? 0) > 0) {
            return response()->json(['success' => false, 'message' => 'This enrollment already has attendance records, so it cannot be cancelled.'], 422);
        }

        $userId = (int) $enrollment->user_id;

        try {
            DB::transaction(function () use ($enrollment, $userId) {
                $enrollment->delete();

                // Only go back to applicant when no enrollment is left at all
                // (a graduate keeps role 3 and returns to the Graduates list).
                if (! Enrollment::where('user_id', $userId)->exists()) {
                    DB::table('tbl_users')
                        ->where('user_id', $userId)
                        ->where('role_id', self::STUDENT_ROLE_ID)
                        ->update(['role_id' => self::APPLICANT_ROLE_ID]);
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // another table still points at this enrollment
            return response()->json(['success' => false, 'message' => 'This enrollment is linked to other records, so it cannot be cancelled.'], 422);
        }

        return response()->json([
            'success' => true,
            'applicant' => $this->applicantRows($userId)->first(),
            'graduate' => $this->graduateRows($userId)->first(),
        ]);
    }

    /**
     * DELETE /registers/applicants/{user}
     * Called via fetch() from the Applicants tab's "Delete" button.
     * PERMANENT (hard) delete of an applicant's account: the user row and the
     * rows that only exist for that account. Nothing is soft-deleted. Only an
     * applicant (role 5) with no enrollment can be deleted, and the action is
     * written to tbl_system_log so there is still a record of who removed what.
     */
    public function destroyApplicant(User $user): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        if ((int) $user->role_id !== self::APPLICANT_ROLE_ID || $user->enrollments()->exists()) {
            return response()->json(['success' => false, 'message' => 'Only applicants with no enrollment can be deleted.'], 422);
        }

        $userId = (int) $user->user_id;
        $email = $user->email_address;
        $label = $user->username . ' <' . $email . '>';

        try {
            DB::transaction(function () use ($userId, $email, $label) {
                DB::table('tbl_emergency_contacts')->where('user_id', $userId)->delete();
                DB::table('tbl_user_profile')->where('user_id', $userId)->delete();
                DB::table('tbl_verification_codes')->where('email_address', $email)->delete();
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                DB::table('sessions')->where('user_id', $userId)->delete();

                // query builder on purpose: always a real DELETE, never a soft delete
                DB::table('tbl_users')->where('user_id', $userId)->delete();

                DB::table('tbl_system_log')->insert([
                    'user_id' => Auth::id(),
                    'role_id' => Auth::user()->role_id,
                    'log_info' => 'Permanently deleted applicant account ' . $label,
                    'log_timestamp' => now(),
                ]);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // some other table still points at this user
            return response()->json(['success' => false, 'message' => 'This account is linked to other records, so it cannot be deleted.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /* ---------------------------------------------------------
     * Lists
     * --------------------------------------------------------- */

    /** The Applicants and Graduates lists. (Athletes live on the Athletes page.) */
    private function lists(): array
    {
        return [
            'registrations' => $this->applicantRows(),
            'graduates' => $this->graduateRows(),
        ];
    }

    private function applicantRows(?int $onlyUserId = null): Collection
    {
        $profiles = UserProfile::whereHas('user', function ($q) use ($onlyUserId) {
            $q->where('role_id', self::APPLICANT_ROLE_ID)->whereDoesntHave('enrollments');

            if ($onlyUserId) {
                $q->where('user_id', $onlyUserId);
            }
        })->with('user')->get()->sortByDesc(fn ($p) => $p->user->created_at);

        return $profiles->map(fn ($p) => array_merge([
            'id' => $p->user_id,
            'lastName' => $p->last_name ?? '',
            'firstName' => $p->first_name ?? '',
            'middleName' => $p->middle_name ?? '',
            'email' => $p->user->email_address ?? '',
            'createdAt' => $p->user->created_at?->toIso8601String(),
        ], $this->birthFields($p)))->values();
    }

    /**
     * Graduates: graduated at least once, not enrolled right now, not an
     * athlete. One row per person, even if they graduated from several courses.
     * graduatedCourseIds is what the page uses to hide courses they already finished.
     */
    private function graduateRows(?int $onlyUserId = null): Collection
    {
        $graduated = Enrollment::with('course')
            ->whereNotNull('graduation_at')
            ->when($onlyUserId, fn ($q) => $q->where('user_id', $onlyUserId))
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

            // Enrollment that was graduated most recently
            $enrolledAt = $last->enrollment_at ? Carbon::parse($last->enrollment_at) : null;
            $graduatedAt = Carbon::parse($last->graduation_at);

            return array_merge([
                'id' => $user->user_id,
                'lastName' => $profile->last_name ?? '',
                'firstName' => $profile->first_name ?? '',
                'middleName' => $profile->middle_name ?? '',
                'email' => $user->email_address ?? '',
                'lastCourse' => $last->course->course_name ?? '',
                'enrolledAt' => $enrolledAt?->toIso8601String(),
                'graduatedAt' => $graduatedAt->toIso8601String(),
                // how long that last course took, e.g. "3 months"
                'duration' => $enrolledAt ? $enrolledAt->diffForHumans($graduatedAt, true) : '',
                'coursesFinished' => $mine->pluck('course_id')->unique()->count(),
                'graduatedCourseIds' => $mine->pluck('course_id')->unique()->values()->all(),
                'graduatedCourses' => $mine->map(fn ($e) => $e->course->course_name ?? '')->filter()->unique()->values()->all(),
            ], $this->birthFields($profile));
        })->sortByDesc('graduatedAt')->values();
    }

    /** user id => ids of the courses that person already graduated from. */
    private function graduatedCourseIdsByUser(?int $onlyUserId = null): Collection
    {
        return Enrollment::whereNotNull('graduation_at')
            ->when($onlyUserId, fn ($q) => $q->where('user_id', $onlyUserId))
            ->get(['user_id', 'course_id'])
            ->groupBy('user_id')
            ->map(fn ($rows) => $rows->pluck('course_id')->unique()->values()->all());
    }

    /**
     * One Students-tab row. Used by data() AND enroll(), so a newly enrolled
     * student has exactly the same fields as one loaded with the page.
     * Needs the enrollment's user.profile, course and payments loaded.
     */
    private function studentRow(Enrollment $e, array $graduatedCourseIds, array $attendanceCounts): array
    {
        $totalPaid = $e->payments->sum('amount');
        $balance = max($e->course_price_at_enrollment - $totalPaid, 0);

        $paymentCount = $e->payments->count();
        $attendanceCount = (int) ($attendanceCounts[$e->enrollment_id] ?? 0);

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
            'graduatedCourseIds' => $graduatedCourseIds,
            // when the enrollment happened (date AND time)
            'enrolledAt' => $e->enrollment_at ? Carbon::parse($e->enrollment_at)->toIso8601String() : null,
            // sessions logged so far vs. the course's session count
            'attendanceCount' => $attendanceCount,
            'sessionTotal' => (int) ($e->course->course_session_count ?? 0),
            // set once the person has been cleared for graduation
            'clearedAt' => $e->cleared_for_graduation_at
                ? Carbon::parse($e->cleared_for_graduation_at)->toIso8601String()
                : null,
            // Cancel button: enabled flag + the reason shown on the page when it is off
            'canCancel' => $paymentCount === 0 && $attendanceCount === 0,
            'cancelBlockReason' => $this->cancelBlockReason($paymentCount, $attendanceCount),
        ];
    }

    /** Why an enrollment can't be cancelled, or null when it can. */
    private function cancelBlockReason(int $paymentCount, int $attendanceCount): ?string
    {
        $parts = [];

        if ($paymentCount > 0) {
            $parts[] = $paymentCount . ' payment ' . Str::plural('record', $paymentCount);
        }

        if ($attendanceCount > 0) {
            $parts[] = $attendanceCount . ' attendance ' . Str::plural('record', $attendanceCount);
        }

        if (! $parts) {
            return null;
        }

        return 'Cannot cancel: this enrollment already has ' . implode(' and ', $parts) . '.';
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

    /** enrollment id => number of attendance rows, for the given enrollments (ids with none are left out). */
    private function attendanceCountsByEnrollment(array $enrollmentIds): array
    {
        if (! $enrollmentIds) {
            return [];
        }

        return DB::table(self::ATTENDANCE_TABLE)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->selectRaw('enrollment_id, COUNT(*) as total')
            ->groupBy('enrollment_id')
            ->pluck('total', 'enrollment_id')
            ->mapWithKeys(fn ($total, $id) => [(int) $id => (int) $total])
            ->all();
    }

    private function denyUnlessAdmin(): ?JsonResponse
    {
        if ((int) Auth::user()->role_id !== self::ADMIN_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only the admin can do this.'], 403);
        }

        return null;
    }
}