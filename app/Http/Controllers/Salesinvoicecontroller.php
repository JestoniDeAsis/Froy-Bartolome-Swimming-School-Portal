<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Enrollment;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    private const ADMIN_ROLE_ID = 1;

    // role_id that athletes have in tbl_users.
    private const ATHLETE_ROLE_ID = 4;

    private const PERSON_NAME_SQL = "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.name_suffix)), ''), u.username, '')";

    /**
     * GET /sales/data
     * The Billing & Receipts page loads its three datasets from here with
     * fetch(), so the page works no matter how the blade gets included.
     */
    public function data(): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Same datasets as a plain array (reusable from HomeController if the
     * page is ever rendered server-side instead).
     */
    public function payload(): array
    {
        return [
            'coursePayments' => $this->courseLog(),
            'archivedCoursePayments' => $this->archivedCourseLog(),
            'itemCatalog' => $this->itemCatalog(),
            'itemLogs' => $this->itemLogs(),
            'athletePayments' => $this->athleteLog(),
            'archivedAthletePayments' => $this->archivedAthleteLog(),
            'athleteFees' => $this->athleteFees(),
        ];
    }

    /**
     * POST /sales/enrollments/{enrollment}/payments
     * "Record Course Payment": logs ONE new payment row for an enrollment.
     * It never edits a past payment, so balance/status are always
     * (price - sum of payments). Same rules the Registers page used to have:
     *   - admin only
     *   - amount > 0, method required, reference optional
     *   - a fully paid enrollment can't take another payment
     *   - a payment larger than the remaining balance is rejected (422)
     */
    public function recordPayment(Request $request, Enrollment $enrollment): JsonResponse
    {
        if ((int) Auth::user()->role_id !== self::ADMIN_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only the admin can do this.'], 403);
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

            // COALESCE keeps old rows (saved before enrollment_id existed) counted.
            $alreadyPaid = (float) CoursePayment::whereNull('archived_at')
                ->whereRaw('COALESCE(enrollment_id, student_id) = ?', [$locked->enrollment_id])
                ->sum('amount');

            $remaining = round((float) $locked->course_price_at_enrollment - $alreadyPaid, 2);

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

        $totalPaid = (float) CoursePayment::whereNull('archived_at')
            ->whereRaw('COALESCE(enrollment_id, student_id) = ?', [$enrollment->enrollment_id])
            ->sum('amount');
        $balance = max(round((float) $enrollment->course_price_at_enrollment - $totalPaid, 2), 0);

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'paymentStatus' => $balance <= 0 ? 'Paid' : ($totalPaid > 0 ? 'Partial' : 'Unpaid'),
        ]);
    }

    /**
     * PUT /sales/payments/{payment}
     * Edits ONE payment log row (amount / method / reference). The most this
     * payment may be is:
     *     course_price_at_enrollment  -  every OTHER payment of that enrollment
     * i.e. the payment being edited is excluded from the "already paid" total,
     * so lowering or re-raising it is checked against the right balance.
     */
    public function updatePayment(Request $request, CoursePayment $payment): JsonResponse
    {
        if ($payment->archived_at !== null) {
            return response()->json(['success' => false, 'message' => 'This payment is archived. Re-log it from the Archived filter instead.'], 422);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        $enrollmentId = $payment->enrollment_id ?? $payment->student_id;

        DB::transaction(function () use ($payment, $validated, $amount, $enrollmentId) {
            // Lock the enrollment so concurrent edits can't overpay together.
            $enrollment = Enrollment::whereKey($enrollmentId)->lockForUpdate()->firstOrFail();

            $others = (float) CoursePayment::whereNull('archived_at')
                ->whereRaw('COALESCE(enrollment_id, student_id) = ?', [$enrollmentId])
                ->where('course_payment_id', '!=', $payment->getKey())
                ->sum('amount');

            $maximum = round((float) $enrollment->course_price_at_enrollment - $others, 2);

            if ($amount > $maximum) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment of ₱' . number_format($amount, 2)
                        . ' exceeds the maximum of ₱' . number_format(max($maximum, 0), 2)
                        . ' (course price minus the other payments).'],
                ]);
            }

            $payment->update([
                'enrollment_id' => $enrollmentId,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
            ]);
        });

        return response()->json(['success' => true]);
    }

    /**
     * POST /sales/payments/{payment}/archive
     * Replaces the old "delete". The row stays in tbl_course_payments with
     * archived_at set, so nothing is lost, but it stops counting toward the
     * enrollment's paid total / balance / status and leaves the main log.
     * Admin only.
     */
    public function archivePayment(CoursePayment $payment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $enrollmentId = $payment->enrollment_id ?? $payment->student_id;

        $archived = DB::transaction(function () use ($payment, $enrollmentId) {
            // Same lock the other payment endpoints take, so a payment being
            // recorded at the same moment sees a consistent total.
            Enrollment::whereKey($enrollmentId)->lockForUpdate()->first();

            return DB::table('tbl_course_payments')
                ->where('course_payment_id', $payment->getKey())
                ->whereNull('archived_at')
                ->update([
                    'archived_at' => now(),
                    'archived_by' => Auth::id(),
                    'updated_at' => now(),
                ]) > 0;
        });

        if (! $archived) {
            return response()->json(['success' => false, 'message' => 'This payment is already archived.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /sales/payments/{payment}/restore
     * "Re-log" an archived payment. The admin may change amount / method /
     * reference while doing it, and it goes through the same checks as a new
     * payment, measured against ACTIVE payments only:
     *   - admin only, and the row must really be archived
     *   - amount > 0, method required, reference optional
     *   - the enrollment can't already be fully paid
     *   - amount can't exceed  course price - active payments of that enrollment
     * The original paid_at and receipt number are kept.
     */
    public function restorePayment(Request $request, CoursePayment $payment): JsonResponse
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
        $enrollmentId = $payment->enrollment_id ?? $payment->student_id;

        $balance = DB::transaction(function () use ($payment, $validated, $amount, $enrollmentId) {
            $enrollment = Enrollment::whereKey($enrollmentId)->lockForUpdate()->firstOrFail();

            // Re-read inside the lock: someone may have restored it already.
            $row = DB::table('tbl_course_payments')
                ->where('course_payment_id', $payment->getKey())
                ->lockForUpdate()
                ->first();

            if (! $row || $row->archived_at === null) {
                throw ValidationException::withMessages(['payment' => ['This payment is not archived.']]);
            }

            $alreadyPaid = (float) CoursePayment::whereNull('archived_at')
                ->whereRaw('COALESCE(enrollment_id, student_id) = ?', [$enrollmentId])
                ->sum('amount');

            $remaining = round((float) $enrollment->course_price_at_enrollment - $alreadyPaid, 2);

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['This enrollment is already fully paid, so this payment can\'t be re-logged.'],
                ]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment of ₱' . number_format($amount, 2)
                        . ' exceeds the remaining balance of ₱' . number_format($remaining, 2) . '.'],
                ]);
            }

            DB::table('tbl_course_payments')
                ->where('course_payment_id', $payment->getKey())
                ->update([
                    'enrollment_id' => $enrollmentId,
                    'amount' => $amount,
                    'payment_method' => $validated['payment_method'],
                    'reference_number' => $validated['reference_number'] ?? null,
                    'archived_at' => null,
                    'archived_by' => null,
                    'received_by' => Auth::id(),
                    'updated_at' => now(),
                ]);

            return max(round($remaining - $amount, 2), 0);
        });

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'paymentStatus' => $balance <= 0 ? 'Paid' : 'Partial',
        ]);
    }

    /**
     * Course payment log: ONE ROW PER PAYMENT in tbl_course_payments — rows
     * are never combined per student. Each row carries:
     *   paid            this payment's amount
     *   balance/status  the enrollment's balance and status right AFTER this
     *                   payment (running total of that enrollment's payments)
     *   paidToDate      the enrollment's total paid up to and including this payment
     *   enrollmentPaid  the enrollment's total paid so far (used by the page's
     *                   stats and the "record payment" dialog)
     * Enrollments with no payment yet get a single Unpaid row (negative id)
     * so they can still be seen and paid.
     *
     * A payment is matched to its enrollment with COALESCE(enrollment_id,
     * student_id): student_id has always held the enrollment id, so rows saved
     * before the enrollment_id column existed still show up.
     *
     * There's no stored receipt number: each payment's receipt is derived as
     * OR-<year>-<payment id>; unpaid enrollments show ENR-<enrollment id>.
     */
    private function courseLog(): Collection
    {
        $payments = $this->withEnrollmentDetails(
            DB::table('tbl_course_payments as cp')
                ->join('tbl_enrollments as e', DB::raw('e.enrollment_id'), '=', DB::raw('COALESCE(cp.enrollment_id, cp.student_id)'))
                ->whereNull('cp.archived_at')
                ->select('cp.course_payment_id as id', 'e.enrollment_id as enrollmentId', 'cp.amount', 'cp.payment_method as method', 'cp.reference_number as reference', 'cp.paid_at as sortAt')
        )
            ->selectRaw('(SELECT COALESCE(SUM(x.amount), 0) FROM tbl_course_payments x WHERE COALESCE(x.enrollment_id, x.student_id) = e.enrollment_id AND x.archived_at IS NULL AND x.course_payment_id <= cp.course_payment_id) as runningPaid')
            ->selectRaw('(SELECT COALESCE(SUM(x.amount), 0) FROM tbl_course_payments x WHERE COALESCE(x.enrollment_id, x.student_id) = e.enrollment_id AND x.archived_at IS NULL) as totalPaid')
            ->selectRaw("DATE_FORMAT(cp.paid_at, '%b-%d-%Y') as date")
            ->selectRaw("DATE_FORMAT(cp.updated_at, '%b-%d-%Y') as updatedAt")
            ->selectRaw("CONCAT('OR-', YEAR(cp.paid_at), '-', LPAD(cp.course_payment_id, 4, '0')) as receipt")
            ->get()
            ->map(function ($r) {
                $price = (float) $r->price;
                $balance = max(round($price - (float) $r->runningPaid, 2), 0);

                return [
                    'id' => (int) $r->id,
                    'enrollmentId' => (int) $r->enrollmentId,
                    'course' => $r->course,
                    'name' => $r->name,
                    'price' => $price,
                    'toPay' => $price,
                    'paid' => (float) $r->amount,
                    'balance' => $balance,
                    'status' => $balance <= 0 ? 'paid' : 'partial',
                    'paidToDate' => round((float) $r->runningPaid, 2),
                    'enrollmentPaid' => (float) $r->totalPaid,
                    'reference' => $r->reference ?? '',
                    'method' => $r->method ?? '',
                    'date' => $r->date ?? '',
                    'updatedAt' => $r->updatedAt ?? '',
                    'receipt' => $r->receipt,
                    'sortAt' => (string) $r->sortAt,
                ];
            });

        $unpaid = $this->withEnrollmentDetails(
            DB::table('tbl_enrollments as e')
                ->select('e.enrollment_id as enrollmentId', 'e.enrollment_at as sortAt')
        )
            ->selectRaw("DATE_FORMAT(e.enrollment_at, '%b-%d-%Y') as date")
            ->whereRaw('NOT EXISTS (SELECT 1 FROM tbl_course_payments x WHERE COALESCE(x.enrollment_id, x.student_id) = e.enrollment_id AND x.archived_at IS NULL)')
            ->get()
            ->map(function ($r) {
                $price = (float) $r->price;

                return [
                    'id' => -((int) $r->enrollmentId),
                    'enrollmentId' => (int) $r->enrollmentId,
                    'course' => $r->course,
                    'name' => $r->name,
                    'price' => $price,
                    'toPay' => $price,
                    'paid' => 0.0,
                    'balance' => $price,
                    'status' => 'unpaid',
                    'paidToDate' => 0.0,
                    'enrollmentPaid' => 0.0,
                    'reference' => '',
                    'method' => '',
                    'date' => $r->date ?? '',
                    'updatedAt' => '',
                    'receipt' => 'ENR-' . $r->enrollmentId,
                    'sortAt' => (string) $r->sortAt,
                ];
            });

        return $payments
            ->concat($unpaid)
            ->sort(fn ($a, $b) => [$b['sortAt'], $b['id']] <=> [$a['sortAt'], $a['id']])
            ->values();
    }

    /**
     * Archived course payments (the "Archived" filter). Kept apart from
     * courseLog() so the page's totals never include them. enrollmentPaid is
     * what the enrollment has paid in ACTIVE payments, which is what the
     * re-log dialog needs to work out the most this payment may be.
     */
    private function archivedCourseLog(): Collection
    {
        return $this->withEnrollmentDetails(
            DB::table('tbl_course_payments as cp')
                ->join('tbl_enrollments as e', DB::raw('e.enrollment_id'), '=', DB::raw('COALESCE(cp.enrollment_id, cp.student_id)'))
                ->whereNotNull('cp.archived_at')
                ->select('cp.course_payment_id as id', 'e.enrollment_id as enrollmentId', 'cp.amount', 'cp.payment_method as method', 'cp.reference_number as reference', 'cp.paid_at as sortAt')
        )
            ->selectRaw('(SELECT COALESCE(SUM(x.amount), 0) FROM tbl_course_payments x WHERE COALESCE(x.enrollment_id, x.student_id) = e.enrollment_id AND x.archived_at IS NULL) as activePaid')
            ->selectRaw("DATE_FORMAT(cp.paid_at, '%b-%d-%Y') as date")
            ->selectRaw("DATE_FORMAT(cp.archived_at, '%b-%d-%Y') as archivedAt")
            ->selectRaw("CONCAT('OR-', YEAR(cp.paid_at), '-', LPAD(cp.course_payment_id, 4, '0')) as receipt")
            ->orderByDesc('cp.archived_at')
            ->orderByDesc('cp.course_payment_id')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'enrollmentId' => (int) $r->enrollmentId,
                'course' => $r->course,
                'name' => $r->name,
                'price' => (float) $r->price,
                'toPay' => (float) $r->price,
                'paid' => (float) $r->amount,
                'enrollmentPaid' => round((float) $r->activePaid, 2),
                'reference' => $r->reference ?? '',
                'method' => $r->method ?? '',
                'date' => $r->date ?? '',
                'archivedAt' => $r->archivedAt ?? '',
                'receipt' => $r->receipt,
                'status' => 'archived',
                'sortAt' => (string) $r->sortAt,
            ]);
    }

    /**
     * Adds the student name, course name and course price to a query that
     * already exposes the enrollment as alias "e".
     */
    private function withEnrollmentDetails(Builder $query): Builder
    {
        return $query
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 'e.course_id')
            ->selectRaw("COALESCE(c.course_name, 'Course') as course")
            ->selectRaw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.name_suffix)), ''), u.username, '') as name")
            ->selectRaw('COALESCE(e.course_price_at_enrollment, c.course_price, 0) as price');
    }

    /**
     * Active items for the Items tab and the purchase dialog.
     */
    private function itemCatalog(): Collection
    {
        return DB::table('tbl_items')
            ->where('is_active', 1)
            ->orderBy('item_name')
            ->get(['item_id', 'item_name', 'description', 'unit_price', 'stock_quantity'])
            ->map(fn ($item) => [
                'id' => (int) $item->item_id,
                'name' => $item->item_name,
                'description' => $item->description ?? '',
                'price' => (float) $item->unit_price,
                'stock' => (int) $item->stock_quantity,
            ]);
    }

    /**
     * Item sales log (tbl_item_logs). Its receipt_number is separate from
     * course payments and is still stored on the log row.
     */
    private function itemLogs(): Collection
    {
        return DB::table('tbl_item_logs as l')
            ->join('tbl_items as i', 'i.item_id', '=', 'l.item_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'l.user_id')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
            ->select('l.item_log_id as id', 'l.item_id as itemId', 'i.item_name as item', 'l.quantity', 'l.unit_price as price', 'l.amount_paid as paid', 'l.payment_method as method', 'l.receipt_number as receipt', 'l.sold_at as sortAt')
            ->selectRaw("COALESCE(NULLIF(l.description, ''), i.description, '') as description")
            ->selectRaw("DATE_FORMAT(l.sold_at, '%b-%d-%Y') as date")
            ->selectRaw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.name_suffix)), ''), u.username, '') as client")
            ->orderByDesc('l.sold_at')
            ->orderByDesc('l.item_log_id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'itemId' => (int) $row->itemId,
                'item' => $row->item,
                'description' => $row->description ?? '',
                'quantity' => (int) $row->quantity,
                'price' => (float) $row->price,
                'paid' => (float) $row->paid,
                'method' => $row->method ?? '',
                'date' => $row->date ?? '',
                'client' => $row->client,
                'receipt' => $row->receipt,
                'sortAt' => (string) $row->sortAt,
            ]);
    }

    // =====================================================================
    // ATHLETE FEES (the "payment setting") + ATHLETE PAYMENTS
    //
    //   tbl_athlete_fees      what athletes need to pay: name, amount, and how
    //                         often (one_time / monthly / yearly)
    //   tbl_athlete_payments  ONE ROW PER PAYMENT, like tbl_course_payments.
    //                         A "due" = athlete + fee + billing period, e.g.
    //                         (Juan, Monthly fee, 2026-10). amount_due is the fee
    //                         amount frozen when the due got its first payment,
    //                         so changing a fee later never rewrites history.
    //                         period_key: '2026-10' monthly, '2026' yearly,
    //                         '' one-time.
    // =====================================================================

    private function denyUnlessAdmin(): ?JsonResponse
    {
        if ((int) Auth::user()->role_id !== self::ADMIN_ROLE_ID) {
            return response()->json(['success' => false, 'message' => 'Only the admin can do this.'], 403);
        }

        return null;
    }

    private function feeRules(): array
    {
        return [
            'fee_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'billing_cycle' => ['required', 'in:one_time,monthly,yearly'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * POST /sales/athlete-fees
     */
    public function storeAthleteFee(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate($this->feeRules());

        $id = DB::table('tbl_athlete_fees')->insertGetId([
            'fee_name' => trim($validated['fee_name']),
            'description' => $validated['description'] ?? null,
            'amount' => round((float) $validated['amount'], 2),
            'billing_cycle' => $validated['billing_cycle'],
            'is_active' => $request->boolean('is_active', true) ? 1 : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'athlete_fee_id');

        return response()->json(['success' => true, 'id' => $id]);
    }

    /**
     * PUT /sales/athlete-fees/{fee}
     * A new amount only affects dues that have no payment yet; dues that
     * already took a payment keep the amount_due they were frozen with.
     */
    public function updateAthleteFee(Request $request, int $fee): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate($this->feeRules());
        $row = DB::table('tbl_athlete_fees')->where('athlete_fee_id', $fee)->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Fee not found.'], 404);
        }

        // Period keys depend on the cycle, so it is locked once payments exist.
        $hasPayments = DB::table('tbl_athlete_payments')->where('athlete_fee_id', $fee)->exists();
        if ($hasPayments && $validated['billing_cycle'] !== $row->billing_cycle) {
            throw ValidationException::withMessages([
                'billing_cycle' => ['The billing cycle can\'t be changed once athletes have paid this fee.'],
            ]);
        }

        DB::table('tbl_athlete_fees')->where('athlete_fee_id', $fee)->update([
            'fee_name' => trim($validated['fee_name']),
            'description' => $validated['description'] ?? null,
            'amount' => round((float) $validated['amount'], 2),
            'billing_cycle' => $validated['billing_cycle'],
            'is_active' => $request->boolean('is_active', (bool) $row->is_active) ? 1 : 0,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /sales/athlete-fees/{fee}
     * A fee nobody has paid is deleted. A fee with payments is archived
     * (is_active = 0) so the payment history keeps its fee name.
     */
    public function deleteAthleteFee(int $fee): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        if (! DB::table('tbl_athlete_fees')->where('athlete_fee_id', $fee)->exists()) {
            return response()->json(['success' => false, 'message' => 'Fee not found.'], 404);
        }

        if (DB::table('tbl_athlete_payments')->where('athlete_fee_id', $fee)->exists()) {
            DB::table('tbl_athlete_fees')->where('athlete_fee_id', $fee)->update(['is_active' => 0, 'updated_at' => now()]);

            return response()->json(['success' => true, 'archived' => true]);
        }

        DB::table('tbl_athlete_fees')->where('athlete_fee_id', $fee)->delete();

        return response()->json(['success' => true, 'archived' => false]);
    }

    /**
     * POST /sales/athlete-payments
     * Logs ONE new payment for an athlete's due (athlete + fee + period).
     * Same rules as course payments: admin only, amount > 0, method required,
     * can't pay a fully paid due, can't pay more than the remaining balance.
     */
    public function recordAthletePayment(Request $request): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
            'athlete_fee_id' => ['required', 'integer', 'exists:tbl_athlete_fees,athlete_fee_id'],
            'period_key' => ['nullable', 'string', 'max:7'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $amount = round((float) $validated['amount'], 2);
        $userId = (int) $validated['user_id'];
        $feeId = (int) $validated['athlete_fee_id'];

        $balance = DB::transaction(function () use ($validated, $amount, $userId, $feeId) {
            // Lock the athlete row so two simultaneous payments can't overpay together.
            $athlete = DB::table('tbl_users')
                ->where('user_id', $userId)
                ->where('role_id', self::ATHLETE_ROLE_ID)
                ->lockForUpdate()
                ->first();

            if (! $athlete) {
                throw ValidationException::withMessages(['user_id' => ['This user is not an athlete.']]);
            }

            $fee = DB::table('tbl_athlete_fees')->where('athlete_fee_id', $feeId)->first();
            $periodKey = $this->resolvePeriodKey($fee->billing_cycle, $validated['period_key'] ?? null);

            $existing = DB::table('tbl_athlete_payments')
                ->whereNull('archived_at')
                ->where('user_id', $userId)
                ->where('athlete_fee_id', $feeId)
                ->where('period_key', $periodKey)
                ->orderBy('athlete_payment_id');

            $first = (clone $existing)->first();

            if (! $first && ! $fee->is_active) {
                throw ValidationException::withMessages(['athlete_fee_id' => ['This fee is archived.']]);
            }

            $due = $first ? (float) $first->amount_due : (float) $fee->amount;
            $alreadyPaid = (float) (clone $existing)->sum('amount');
            $remaining = round($due - $alreadyPaid, 2);

            if ($remaining <= 0) {
                throw ValidationException::withMessages(['amount' => ['This fee is already fully paid.']]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment of ₱' . number_format($amount, 2)
                        . ' exceeds the remaining balance of ₱' . number_format($remaining, 2) . '.'],
                ]);
            }

            DB::table('tbl_athlete_payments')->insert([
                'user_id' => $userId,
                'athlete_fee_id' => $feeId,
                'period_key' => $periodKey,
                'amount_due' => $due,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'paid_at' => now(),
                'received_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return round($remaining - $amount, 2);
        });

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'paymentStatus' => $balance <= 0 ? 'Paid' : 'Partial',
        ]);
    }

    /**
     * PUT /sales/athlete-payments/{payment}
     * Edits ONE payment row. Most it can be: amount_due - the OTHER payments
     * of the same due (the row being edited is left out of the total).
     */
    public function updateAthletePayment(Request $request, int $payment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $row = DB::table('tbl_athlete_payments')->where('athlete_payment_id', $payment)->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        if ($row->archived_at !== null) {
            return response()->json(['success' => false, 'message' => 'This payment is archived. Re-log it from the Archived filter instead.'], 422);
        }

        $amount = round((float) $validated['amount'], 2);

        DB::transaction(function () use ($row, $validated, $amount, $payment) {
            DB::table('tbl_users')->where('user_id', $row->user_id)->lockForUpdate()->first();

            $others = (float) DB::table('tbl_athlete_payments')
                ->whereNull('archived_at')
                ->where('user_id', $row->user_id)
                ->where('athlete_fee_id', $row->athlete_fee_id)
                ->where('period_key', $row->period_key)
                ->where('athlete_payment_id', '!=', $payment)
                ->sum('amount');

            $maximum = round((float) $row->amount_due - $others, 2);

            if ($amount > $maximum) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment of ₱' . number_format($amount, 2)
                        . ' exceeds the maximum of ₱' . number_format(max($maximum, 0), 2)
                        . ' (fee amount minus the other payments).'],
                ]);
            }

            DB::table('tbl_athlete_payments')->where('athlete_payment_id', $payment)->update([
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'updated_at' => now(),
            ]);
        });

        return response()->json(['success' => true]);
    }

    /**
     * POST /sales/athlete-payments/{payment}/archive
     * Same idea as archiving a course payment: the row stays in
     * tbl_athlete_payments with archived_at set, stops counting toward its
     * due (athlete + fee + period) and leaves the main log. Admin only.
     */
    public function archiveAthletePayment(int $payment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $row = DB::table('tbl_athlete_payments')->where('athlete_payment_id', $payment)->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        $archived = DB::transaction(function () use ($row, $payment) {
            // Same lock the other athlete payment endpoints take.
            DB::table('tbl_users')->where('user_id', $row->user_id)->lockForUpdate()->first();

            return DB::table('tbl_athlete_payments')
                ->where('athlete_payment_id', $payment)
                ->whereNull('archived_at')
                ->update([
                    'archived_at' => now(),
                    'archived_by' => Auth::id(),
                    'updated_at' => now(),
                ]) > 0;
        });

        if (! $archived) {
            return response()->json(['success' => false, 'message' => 'This payment is already archived.'], 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * POST /sales/athlete-payments/{payment}/restore
     * "Re-log" an archived athlete payment (amount / method / reference may be
     * changed). Same checks as a new payment, against ACTIVE payments only:
     *   - admin only, row must really be archived, user must still be an athlete
     *   - amount > 0, method required, reference optional
     *   - the due can't already be fully paid
     *   - amount can't exceed  amount_due - active payments of that due
     * amount_due is the one the due's active payments are frozen with, or this
     * row's own frozen amount_due when the due has no active payment left.
     * The original paid_at and receipt number are kept.
     */
    public function restoreAthletePayment(Request $request, int $payment): JsonResponse
    {
        if ($denied = $this->denyUnlessAdmin()) {
            return $denied;
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $row = DB::table('tbl_athlete_payments')->where('athlete_payment_id', $payment)->first();

        if (! $row) {
            return response()->json(['success' => false, 'message' => 'Payment not found.'], 404);
        }

        $amount = round((float) $validated['amount'], 2);

        $balance = DB::transaction(function () use ($row, $validated, $amount, $payment) {
            $athlete = DB::table('tbl_users')
                ->where('user_id', $row->user_id)
                ->where('role_id', self::ATHLETE_ROLE_ID)
                ->lockForUpdate()
                ->first();

            if (! $athlete) {
                throw ValidationException::withMessages(['user_id' => ['This user is not an athlete.']]);
            }

            // Re-read inside the lock: someone may have restored it already.
            $current = DB::table('tbl_athlete_payments')->where('athlete_payment_id', $payment)->lockForUpdate()->first();

            if (! $current || $current->archived_at === null) {
                throw ValidationException::withMessages(['payment' => ['This payment is not archived.']]);
            }

            $active = DB::table('tbl_athlete_payments')
                ->whereNull('archived_at')
                ->where('user_id', $current->user_id)
                ->where('athlete_fee_id', $current->athlete_fee_id)
                ->where('period_key', $current->period_key)
                ->orderBy('athlete_payment_id');

            $first = (clone $active)->first();
            $due = $first ? (float) $first->amount_due : (float) $current->amount_due;
            $alreadyPaid = (float) (clone $active)->sum('amount');
            $remaining = round($due - $alreadyPaid, 2);

            if ($remaining <= 0) {
                throw ValidationException::withMessages(['amount' => ['This fee is already fully paid, so this payment can\'t be re-logged.']]);
            }

            if ($amount > $remaining) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment of ₱' . number_format($amount, 2)
                        . ' exceeds the remaining balance of ₱' . number_format($remaining, 2) . '.'],
                ]);
            }

            DB::table('tbl_athlete_payments')->where('athlete_payment_id', $payment)->update([
                'amount_due' => $due,
                'amount' => $amount,
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'archived_at' => null,
                'archived_by' => null,
                'received_by' => Auth::id(),
                'updated_at' => now(),
            ]);

            return max(round($remaining - $amount, 2), 0);
        });

        return response()->json([
            'success' => true,
            'balance' => $balance,
            'paymentStatus' => $balance <= 0 ? 'Paid' : 'Partial',
        ]);
    }

    /**
     * Billing period a payment belongs to. No key sent = the current period.
     */
    private function resolvePeriodKey(string $cycle, ?string $key): string
    {
        $key = $key ?? '';

        if ($cycle === 'one_time') {
            return '';
        }

        if ($cycle === 'monthly') {
            if ($key === '') {
                return now()->format('Y-m');
            }
            if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $key)) {
                return $key;
            }
        }

        if ($cycle === 'yearly') {
            if ($key === '') {
                return now()->format('Y');
            }
            if (preg_match('/^\d{4}$/', $key)) {
                return $key;
            }
        }

        throw ValidationException::withMessages(['period_key' => ['Invalid billing period.']]);
    }

    private function periodLabel(string $cycle, string $key): string
    {
        if ($cycle === 'monthly' && $key !== '') {
            return Carbon::createFromFormat('!Y-m', $key)->format('M Y');
        }

        if ($cycle === 'yearly' && $key !== '') {
            return $key;
        }

        return 'One-time';
    }

    /**
     * Athlete payment log: ONE ROW PER PAYMENT (same shape as courseLog()).
     * paid = this payment, balance/status = the due right AFTER this payment,
     * duePaid = everything paid on that due so far.
     * Every athlete x every ACTIVE fee that has no payment yet for the
     * current period also gets one Unpaid row (negative id), so the admin sees
     * who still owes and can pay it.
     * Receipt is derived: AR-<year>-<payment id>.
     */
    private function athleteLog(): Collection
    {
        $month = now()->format('Y-m');
        $year = now()->format('Y');
        $sumOfDue = 'SELECT COALESCE(SUM(x.amount), 0) FROM tbl_athlete_payments x'
            . ' WHERE x.archived_at IS NULL AND x.user_id = ap.user_id AND x.athlete_fee_id = ap.athlete_fee_id AND x.period_key = ap.period_key';

        $payments = DB::table('tbl_athlete_payments as ap')
            ->join('tbl_athlete_fees as f', 'f.athlete_fee_id', '=', 'ap.athlete_fee_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'ap.user_id')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
            ->whereNull('ap.archived_at')
            ->select('ap.athlete_payment_id as id', 'ap.user_id as userId', 'ap.athlete_fee_id as feeId', 'f.fee_name as fee', 'f.billing_cycle as cycle', 'ap.period_key as periodKey', 'ap.amount_due as due', 'ap.amount', 'ap.payment_method as method', 'ap.reference_number as reference', 'ap.paid_at as sortAt')
            ->selectRaw(self::PERSON_NAME_SQL . ' as name')
            ->selectRaw('(' . $sumOfDue . ' AND x.athlete_payment_id <= ap.athlete_payment_id) as runningPaid')
            ->selectRaw('(' . $sumOfDue . ') as totalPaid')
            ->selectRaw("DATE_FORMAT(ap.paid_at, '%b-%d-%Y') as date")
            ->selectRaw("DATE_FORMAT(ap.updated_at, '%b-%d-%Y') as updatedAt")
            ->selectRaw("CONCAT('AR-', YEAR(ap.paid_at), '-', LPAD(ap.athlete_payment_id, 4, '0')) as receipt")
            ->get()
            ->map(function ($r) {
                $due = (float) $r->due;
                $balance = max(round($due - (float) $r->runningPaid, 2), 0);

                return [
                    'id' => (int) $r->id,
                    'userId' => (int) $r->userId,
                    'feeId' => (int) $r->feeId,
                    'periodKey' => (string) $r->periodKey,
                    'dueKey' => $r->userId . '-' . $r->feeId . '-' . $r->periodKey,
                    'fee' => $r->fee,
                    'cycle' => $r->cycle,
                    'period' => $this->periodLabel($r->cycle, (string) $r->periodKey),
                    'name' => $r->name,
                    'toPay' => $due,
                    'paid' => (float) $r->amount,
                    'balance' => $balance,
                    'status' => $balance <= 0 ? 'paid' : 'partial',
                    'paidToDate' => round((float) $r->runningPaid, 2),
                    'duePaid' => (float) $r->totalPaid,
                    'reference' => $r->reference ?? '',
                    'method' => $r->method ?? '',
                    'date' => $r->date ?? '',
                    'updatedAt' => $r->updatedAt ?? '',
                    'receipt' => $r->receipt,
                    'sortAt' => (string) $r->sortAt,
                ];
            });

        $periodCase = "CASE f.billing_cycle WHEN 'monthly' THEN ? WHEN 'yearly' THEN ? ELSE '' END";

        $unpaid = DB::table('tbl_users as u')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
            ->crossJoin('tbl_athlete_fees as f')
            ->where('u.role_id', self::ATHLETE_ROLE_ID)
            ->where('f.is_active', 1)
            ->whereRaw('NOT EXISTS (SELECT 1 FROM tbl_athlete_payments x WHERE x.archived_at IS NULL AND x.user_id = u.user_id AND x.athlete_fee_id = f.athlete_fee_id AND x.period_key = ' . $periodCase . ')', [$month, $year])
            ->select('u.user_id as userId', 'f.athlete_fee_id as feeId', 'f.fee_name as fee', 'f.billing_cycle as cycle', 'f.amount as due')
            ->selectRaw(self::PERSON_NAME_SQL . ' as name')
            ->selectRaw($periodCase . ' as periodKey', [$month, $year])
            ->get()
            ->values()
            ->map(function ($r, $i) {
                $due = (float) $r->due;

                return [
                    'id' => -($i + 1),
                    'userId' => (int) $r->userId,
                    'feeId' => (int) $r->feeId,
                    'periodKey' => (string) $r->periodKey,
                    'dueKey' => $r->userId . '-' . $r->feeId . '-' . $r->periodKey,
                    'fee' => $r->fee,
                    'cycle' => $r->cycle,
                    'period' => $this->periodLabel($r->cycle, (string) $r->periodKey),
                    'name' => $r->name,
                    'toPay' => $due,
                    'paid' => 0.0,
                    'balance' => $due,
                    'status' => 'unpaid',
                    'paidToDate' => 0.0,
                    'duePaid' => 0.0,
                    'reference' => '',
                    'method' => '',
                    'date' => '',
                    'updatedAt' => '',
                    'receipt' => 'DUE-' . $r->userId . '-' . $r->feeId,
                    'sortAt' => now()->toDateTimeString(),
                ];
            });

        return $payments
            ->concat($unpaid)
            ->sort(fn ($a, $b) => [$b['sortAt'], $b['id']] <=> [$a['sortAt'], $a['id']])
            ->values();
    }

    /**
     * Archived athlete payments (the "Archived" filter), kept apart from
     * athleteLog() so the page's totals never include them. toPay / duePaid
     * are what the re-log dialog needs: the due as it stands among ACTIVE
     * payments (or this row's own frozen amount_due when none are left).
     */
    private function archivedAthleteLog(): Collection
    {
        $activeOfDue = ' FROM tbl_athlete_payments x WHERE x.archived_at IS NULL'
            . ' AND x.user_id = ap.user_id AND x.athlete_fee_id = ap.athlete_fee_id AND x.period_key = ap.period_key';

        return DB::table('tbl_athlete_payments as ap')
            ->join('tbl_athlete_fees as f', 'f.athlete_fee_id', '=', 'ap.athlete_fee_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'ap.user_id')
            ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
            ->whereNotNull('ap.archived_at')
            ->select('ap.athlete_payment_id as id', 'ap.user_id as userId', 'ap.athlete_fee_id as feeId', 'f.fee_name as fee', 'f.billing_cycle as cycle', 'ap.period_key as periodKey', 'ap.amount_due as ownDue', 'ap.amount', 'ap.payment_method as method', 'ap.reference_number as reference', 'ap.paid_at as sortAt')
            ->selectRaw(self::PERSON_NAME_SQL . ' as name')
            ->selectRaw('(SELECT x.amount_due' . $activeOfDue . ' ORDER BY x.athlete_payment_id LIMIT 1) as activeDue')
            ->selectRaw('(SELECT COALESCE(SUM(x.amount), 0)' . $activeOfDue . ') as duePaid')
            ->selectRaw("DATE_FORMAT(ap.paid_at, '%b-%d-%Y') as date")
            ->selectRaw("DATE_FORMAT(ap.archived_at, '%b-%d-%Y') as archivedAt")
            ->selectRaw("CONCAT('AR-', YEAR(ap.paid_at), '-', LPAD(ap.athlete_payment_id, 4, '0')) as receipt")
            ->orderByDesc('ap.archived_at')
            ->orderByDesc('ap.athlete_payment_id')
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'userId' => (int) $r->userId,
                'feeId' => (int) $r->feeId,
                'periodKey' => (string) $r->periodKey,
                'fee' => $r->fee,
                'cycle' => $r->cycle,
                'period' => $this->periodLabel($r->cycle, (string) $r->periodKey),
                'name' => $r->name,
                'toPay' => (float) ($r->activeDue ?? $r->ownDue),
                'paid' => (float) $r->amount,
                'duePaid' => round((float) $r->duePaid, 2),
                'reference' => $r->reference ?? '',
                'method' => $r->method ?? '',
                'date' => $r->date ?? '',
                'archivedAt' => $r->archivedAt ?? '',
                'receipt' => $r->receipt,
                'status' => 'archived',
                'sortAt' => (string) $r->sortAt,
            ]);
    }

    /**
     * Fee settings for the "Athlete fees" tab (archived ones included).
     */
    private function athleteFees(): Collection
    {
        return DB::table('tbl_athlete_fees')
            ->orderByDesc('is_active')
            ->orderBy('fee_name')
            ->get(['athlete_fee_id', 'fee_name', 'description', 'amount', 'billing_cycle', 'is_active'])
            ->map(fn ($fee) => [
                'id' => (int) $fee->athlete_fee_id,
                'name' => $fee->fee_name,
                'description' => $fee->description ?? '',
                'amount' => (float) $fee->amount,
                'cycle' => $fee->billing_cycle,
                'active' => (bool) $fee->is_active,
            ]);
    }
}