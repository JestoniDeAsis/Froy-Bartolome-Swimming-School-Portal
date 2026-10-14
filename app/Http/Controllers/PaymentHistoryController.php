<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Payment history for the logged-in swimmer (student, applicant or athlete).
 * View only: it lists the payments that belong to THIS user's enrollments.
 *
 * Same rules as the admin Sales & Invoice page (SalesInvoiceController):
 *  - a payment belongs to COALESCE(enrollment_id, student_id), because
 *    student_id has always held the enrollment id
 *  - the price is course_price_at_enrollment (falls back to the course price)
 *  - each payment's balance/status is the enrollment's balance right AFTER it
 *  - the receipt number is derived: OR-<year of paid_at>-<payment id, 4 digits>
 *
 * GET /payments/data
 */
class PaymentHistoryController extends Controller
{
    /** 3 = student, 4 = athlete, 5 = applicant (see config/role_menus.php). */
    private const ALLOWED_ROLE_IDS = [3, 4, 5];

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! in_array((int) $user->role_id, self::ALLOWED_ROLE_IDS, true)) {
            return response()->json(['success' => false, 'message' => 'Payments are only available to swimmers.'], 403);
        }

        $enrollments = Enrollment::query()
            ->with('course')
            ->where('user_id', $user->getKey())
            ->orderByDesc('enrollment_at')
            ->orderByDesc('enrollment_id')
            ->get();

        $enrollmentIds = $enrollments->pluck('enrollment_id');

        $prices = $enrollments->mapWithKeys(fn ($e) => [
            $e->enrollment_id => (float) ($e->course_price_at_enrollment ?? $e->course->course_price ?? 0),
        ]);

        $courseNames = $enrollments->mapWithKeys(fn ($e) => [
            $e->enrollment_id => $e->course->course_name ?? 'Course',
        ]);

        // Oldest first, so the running balance of each enrollment can be built up.
        $payments = $enrollmentIds->isEmpty()
            ? collect()
            : CoursePayment::query()
                ->with('receivedBy.profile')
                ->where(function ($query) use ($enrollmentIds) {
                    $query->whereIn('enrollment_id', $enrollmentIds)
                        ->orWhere(fn ($q) => $q->whereNull('enrollment_id')->whereIn('student_id', $enrollmentIds));
                })
                ->orderBy('course_payment_id')
                ->get();

        $running = [];   // enrollment id => paid so far

        $rows = $payments->map(function ($payment) use ($prices, $courseNames, &$running) {
            $enrollmentId = (int) ($payment->enrollment_id ?? $payment->student_id);
            $price        = (float) ($prices[$enrollmentId] ?? 0);

            $running[$enrollmentId] = round(($running[$enrollmentId] ?? 0) + (float) $payment->amount, 2);

            $balance = max(round($price - $running[$enrollmentId], 2), 0);
            $year    = $payment->paid_at?->format('Y') ?? now()->format('Y');

            return [
                'id'            => (int) $payment->course_payment_id,
                'enrollment_id' => $enrollmentId,
                'course'        => $courseNames[$enrollmentId] ?? 'Course',
                'amount'        => (float) $payment->amount,
                'method'        => $payment->payment_method ?? '',
                'reference'     => $payment->reference_number ?? '',
                'date'          => $payment->paid_at?->format('Y-m-d'),
                'receipt'       => 'OR-' . $year . '-' . str_pad((string) $payment->course_payment_id, 4, '0', STR_PAD_LEFT),
                'price'         => $price,
                'paid_to_date'  => $running[$enrollmentId],
                'balance'       => $balance,
                'status'        => $balance <= 0 ? 'paid' : 'partial',
                'received_by'   => $payment->receivedBy ? $this->fullName($payment->receivedBy) : '',
                'sort_at'       => (string) $payment->paid_at,
            ];
        });

        $summaries = $enrollments->map(function ($e) use ($prices, $running) {
            $price   = $prices[$e->enrollment_id];
            $paid    = (float) ($running[$e->enrollment_id] ?? 0);
            $balance = max(round($price - $paid, 2), 0);

            return [
                'id'       => (int) $e->enrollment_id,
                'label'    => ($e->course->course_name ?? 'Course')
                    . ($e->enrollment_at ? ' · Enrolled ' . $e->enrollment_at->format('M d, Y') : ''),
                'course'   => $e->course->course_name ?? 'Course',
                'price'    => $price,
                'paid'     => $paid,
                'balance'  => $balance,
                'status'   => ($price > 0 && $balance <= 0) ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
                'graduated' => $e->graduation_at !== null,
            ];
        })->values();

        // Newest payment first.
        $rows = $rows
            ->sort(fn ($a, $b) => [$b['sort_at'], $b['id']] <=> [$a['sort_at'], $a['id']])
            ->map(function ($row) {
                unset($row['sort_at']);

                return $row;
            })
            ->values();

        $default = $enrollments->first(fn ($e) => $e->graduation_at === null) ?? $enrollments->first();

        return response()->json([
            'success'               => true,
            'client'                => $this->fullName($user),
            'enrollments'           => $summaries,
            'payments'              => $rows,
            'default_enrollment_id' => $default?->enrollment_id,
        ]);
    }

    /** Full name from the profile; falls back to the username. */
    private function fullName(User $user): string
    {
        $profile = $user->profile;

        $name = trim(implode(' ', array_filter([
            trim((string) ($profile->first_name ?? '')),
            trim((string) ($profile->middle_name ?? '')),
            trim((string) ($profile->last_name ?? '')),
            trim((string) ($profile->name_suffix ?? '')),
        ])));

        return $name !== '' ? $name : (string) ($user->username ?? '');
    }
}