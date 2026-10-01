<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Enrollment;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesInvoiceController extends Controller
{
    /**
     * GET /sales/data
     * The Sales & Invoice page loads its three datasets from here with
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
            'itemCatalog' => $this->itemCatalog(),
            'itemLogs' => $this->itemLogs(),
        ];
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

            $others = (float) CoursePayment::whereRaw('COALESCE(enrollment_id, student_id) = ?', [$enrollmentId])
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
                ->select('cp.course_payment_id as id', 'e.enrollment_id as enrollmentId', 'cp.amount', 'cp.payment_method as method', 'cp.reference_number as reference', 'cp.paid_at as sortAt')
        )
            ->selectRaw('(SELECT COALESCE(SUM(x.amount), 0) FROM tbl_course_payments x WHERE COALESCE(x.enrollment_id, x.student_id) = e.enrollment_id AND x.course_payment_id <= cp.course_payment_id) as runningPaid')
            ->selectRaw('(SELECT COALESCE(SUM(x.amount), 0) FROM tbl_course_payments x WHERE COALESCE(x.enrollment_id, x.student_id) = e.enrollment_id) as totalPaid')
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
            ->whereRaw('NOT EXISTS (SELECT 1 FROM tbl_course_payments x WHERE COALESCE(x.enrollment_id, x.student_id) = e.enrollment_id)')
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
            ->map(function ($row) {
                unset($row['sortAt']);

                return $row;
            })
            ->values();
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
            ->select('l.item_log_id as id', 'l.item_id as itemId', 'i.item_name as item', 'l.quantity', 'l.unit_price as price', 'l.amount_paid as paid', 'l.payment_method as method', 'l.receipt_number as receipt')
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
            ]);
    }
}