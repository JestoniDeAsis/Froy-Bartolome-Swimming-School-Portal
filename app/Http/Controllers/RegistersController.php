<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\UserProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistersController extends Controller
{
    /**
     * Called from HomeController's match($page) when $page === 'registers'.
     * Feeds the Registers blade both the New Registers list and the
     * course options used by the Enroll modal's dropdown.
     */
    public function data(): array
    {
        $profiles = UserProfile::whereHas('user', function ($q) {
            $q->where('role_id', 3)->whereDoesntHave('enrollments');
        })->with('user')->get();

        $courses = Course::orderBy('course_name')
            ->get(['course_id', 'course_name', 'course_price'])
            ->map(fn ($c) => [
                'id' => $c->course_id,
                'name' => $c->course_name,
                'price' => $c->course_price,
            ]);

        $enrollments = Enrollment::whereNull('graduation_at')
            ->with(['user.profile', 'course', 'payments'])
            ->get();

        return [
            'registrations' => $profiles->map(fn ($p) => [
                'id' => $p->user_id,
                'lastName' => $p->last_name ?? '',
                'firstName' => $p->first_name ?? '',
                'middleName' => $p->middle_name ?? '',
                'email' => $p->user->email_address ?? '',
                'birthdate' => $p->birthdate
                    ? Carbon::parse($p->birthdate)->format('M-d-Y')
                    : '',
                'age' => $p->birthdate
                    ? Carbon::parse($p->birthdate)->age
                    : '',
            ]),
            'students' => $enrollments->map(function ($e) {
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
                ];
            }),
            'courses' => $courses,
        ];
    }

    /**
     * POST /registers/enroll
     * Called via fetch() from the Registers blade's Enroll modal,
     * when enrolling someone currently on the New Registers tab.
     */
    public function enroll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:tbl_users,user_id'],
            'course_id' => ['required', 'integer', 'exists:tbl_courses,course_id'],
            'scheduled_time' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $course = Course::findOrFail($validated['course_id']);

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
}