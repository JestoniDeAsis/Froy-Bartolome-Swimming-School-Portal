<?php

namespace App\Http\Controllers;

use App\Models\CoursePayment;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Payment history for the logged-in swimmer (student, applicant or athlete).
 * View only: it lists the payments that belong to THIS user's enrollments.
 * Archived payments (archived_at set by the admin, e.g. after an edit) are never
 * counted in balances, but they are listed separately as "history" so every
 * change stays visible to the payer.
 *
 * Same rules as the admin Sales & Invoice page (SalesInvoiceController):
 *  - a payment belongs to its enrollment_id (foreign key to tbl_enrollments)
 *  - the price is course_price_at_enrollment (falls back to the course price)
 *  - each payment's balance/status is the enrollment's balance right AFTER it
 *  - the invoice number is derived: INV-C-<year of paid_at>-<payment id, 4 digits>
 *
 * GET /payments/data
 */
class PaymentHistoryController extends Controller
{
    /** 3 = student, 4 = athlete, 5 = applicant (see config/role_menus.php). */
    private const ALLOWED_ROLE_IDS = [3, 4, 5, 6];

    private const ATHLETE_ROLE_ID = 4;

    private const FORMER_ATHLETE_ROLE_ID = 6;

    public function data(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! in_array((int) $user->role_id, self::ALLOWED_ROLE_IDS, true)) {
            return response()->json(['success' => false, 'message' => 'Payments are only available to swimmers.'], 403);
        }

        // Athletes pay athlete fees (tbl_athlete_payments), not course enrollments.
        if (in_array((int) $user->role_id, [self::ATHLETE_ROLE_ID, self::FORMER_ATHLETE_ROLE_ID], true)) {
            return $this->athleteData($user);
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
                ->whereNull('archived_at')   // archived payments are hidden from the student
                ->whereIn('enrollment_id', $enrollmentIds)
                ->orderBy('course_payment_id')
                ->get();

        $running = [];   // enrollment id => paid so far

        $rows = $payments->map(function ($payment) use ($prices, $courseNames, &$running) {
            $enrollmentId = (int) $payment->enrollment_id;
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
                'receipt'       => 'INV-C-' . $year . '-' . str_pad((string) $payment->course_payment_id, 4, '0', STR_PAD_LEFT),
                'price'         => $price,
                'paid_to_date'  => $running[$enrollmentId],
                'balance'       => $balance,
                'status'        => $balance <= 0 ? 'paid' : 'partial',
                'received_by'   => $payment->receivedBy ? $this->fullName($payment->receivedBy) : '',
                'corrects'      => $payment->replaces_payment_id
                    ? 'INV-C-' . $year . '-' . str_pad((string) $payment->replaces_payment_id, 4, '0', STR_PAD_LEFT)
                    : '',
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

        // Archived / edited entries: shown for transparency, never counted.
        $history = $enrollmentIds->isEmpty()
            ? collect()
            : DB::table('tbl_course_payments as cp')
                ->leftJoin('tbl_user_profile as ab', 'ab.user_id', '=', 'cp.archived_by')
                ->whereNotNull('cp.archived_at')
                ->whereIn('cp.enrollment_id', $enrollmentIds)
                ->select('cp.course_payment_id as id', 'cp.enrollment_id', 'cp.amount', 'cp.payment_method as method', 'cp.reference_number as reference', 'cp.paid_at', 'cp.archived_at', 'cp.archive_reason')
                ->selectRaw("TRIM(CONCAT_WS(' ', ab.first_name, ab.last_name)) as archivedBy")
                ->selectRaw('(SELECT x.course_payment_id FROM tbl_course_payments x WHERE x.replaces_payment_id = cp.course_payment_id LIMIT 1) as replacedById')
                ->orderByDesc('cp.archived_at')
                ->get()
                ->map(function ($r) use ($courseNames) {
                    $paidAt = Carbon::parse($r->paid_at);

                    return [
                        'id'              => (int) $r->id,
                        'enrollment_id'   => (int) $r->enrollment_id,
                        'course'          => $courseNames[(int) $r->enrollment_id] ?? 'Course',
                        'amount'          => (float) $r->amount,
                        'method'          => $r->method ?? '',
                        'reference'       => $r->reference ?? '',
                        'date'            => $paidAt->format('Y-m-d'),
                        'receipt'         => 'INV-C-' . $paidAt->format('Y') . '-' . str_pad((string) $r->id, 4, '0', STR_PAD_LEFT),
                        'archived_at'     => Carbon::parse($r->archived_at)->format('M d, Y h:i A'),
                        'archived_by'     => $r->archivedBy ?: '',
                        'reason'          => $r->archive_reason ?? '',
                        'replaced_by'     => $r->replacedById
                            ? 'INV-C-' . $paidAt->format('Y') . '-' . str_pad((string) $r->replacedById, 4, '0', STR_PAD_LEFT)
                            : '',
                    ];
                })
                ->values();

        return response()->json([
            'success'               => true,
            'client'                => $this->fullName($user),
            'enrollments'           => $summaries,
            'payments'              => $rows,
            'history'               => $history,
            'default_enrollment_id' => $default?->enrollment_id,
        ]);
    }

    /**
     * Payment history of a logged-in athlete: one row per payment in
     * tbl_athlete_payments (same row shape as the course rows, so the page
     * renders them the same way). "course" holds the fee name + billing period.
     * balance/status = the due's balance right AFTER that payment; the receipt
     * number matches the admin page: INV-A-<year of paid_at>-<payment id, 4 digits>.
     */
    private function athleteData(User $user): JsonResponse
    {
        $sumOfDue = 'SELECT COALESCE(SUM(x.amount), 0) FROM tbl_athlete_payments x'
            . ' WHERE x.archived_at IS NULL AND x.user_id = ap.user_id AND x.athlete_fee_id = ap.athlete_fee_id AND x.period_key = ap.period_key';

        $rows = DB::table('tbl_athlete_payments as ap')
            ->join('tbl_athlete_fees as f', 'f.athlete_fee_id', '=', 'ap.athlete_fee_id')
            ->leftJoin('tbl_users as rb', 'rb.user_id', '=', 'ap.received_by')
            ->leftJoin('tbl_user_profile as rp', 'rp.user_id', '=', 'rb.user_id')
            ->where('ap.user_id', $user->getKey())
            ->whereNull('ap.archived_at')   // archived payments are hidden from the athlete
            ->select('ap.athlete_payment_id as id', 'f.fee_name as fee', 'f.billing_cycle as cycle', 'ap.period_key as periodKey', 'ap.amount_due as due', 'ap.amount', 'ap.payment_method as method', 'ap.reference_number as reference', 'ap.paid_at as paidAt', 'ap.replaces_payment_id as replacesId')
            ->selectRaw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', rp.first_name, rp.middle_name, rp.last_name, rp.name_suffix)), ''), rb.username, '') as receivedBy")
            ->selectRaw('(' . $sumOfDue . ' AND x.athlete_payment_id <= ap.athlete_payment_id) as runningPaid')
            ->orderByDesc('ap.paid_at')
            ->orderByDesc('ap.athlete_payment_id')
            ->get()
            ->map(function ($r) {
                $due     = (float) $r->due;
                $paidAt  = Carbon::parse($r->paidAt);
                $balance = max(round($due - (float) $r->runningPaid, 2), 0);
                $period  = $this->periodLabel($r->cycle, (string) $r->periodKey);

                return [
                    'id'            => (int) $r->id,
                    'enrollment_id' => null,
                    'course'        => $period === 'One-time' ? $r->fee : $r->fee . ' (' . $period . ')',
                    'amount'        => (float) $r->amount,
                    'method'        => $r->method ?? '',
                    'reference'     => $r->reference ?? '',
                    'date'          => $paidAt->format('Y-m-d'),
                    'receipt'       => 'INV-A-' . $paidAt->format('Y') . '-' . str_pad((string) $r->id, 4, '0', STR_PAD_LEFT),
                    'price'         => $due,
                    'paid_to_date'  => round((float) $r->runningPaid, 2),
                    'balance'       => $balance,
                    'status'        => $balance <= 0 ? 'paid' : 'partial',
                    'received_by'   => $r->receivedBy ?? '',
                    'corrects'      => $r->replacesId
                        ? 'INV-A-' . $paidAt->format('Y') . '-' . str_pad((string) $r->replacesId, 4, '0', STR_PAD_LEFT)
                        : '',
                ];
            })
            ->values();

        $history = DB::table('tbl_athlete_payments as ap')
            ->join('tbl_athlete_fees as f', 'f.athlete_fee_id', '=', 'ap.athlete_fee_id')
            ->leftJoin('tbl_user_profile as ab', 'ab.user_id', '=', 'ap.archived_by')
            ->where('ap.user_id', $user->getKey())
            ->whereNotNull('ap.archived_at')
            ->select('ap.athlete_payment_id as id', 'f.fee_name as fee', 'f.billing_cycle as cycle', 'ap.period_key as periodKey', 'ap.amount', 'ap.payment_method as method', 'ap.reference_number as reference', 'ap.paid_at', 'ap.archived_at', 'ap.archive_reason')
            ->selectRaw("TRIM(CONCAT_WS(' ', ab.first_name, ab.last_name)) as archivedBy")
            ->selectRaw('(SELECT x.athlete_payment_id FROM tbl_athlete_payments x WHERE x.replaces_payment_id = ap.athlete_payment_id LIMIT 1) as replacedById')
            ->orderByDesc('ap.archived_at')
            ->get()
            ->map(function ($r) {
                $paidAt = Carbon::parse($r->paid_at);
                $period = $this->periodLabel($r->cycle, (string) $r->periodKey);

                return [
                    'id'            => (int) $r->id,
                    'enrollment_id' => null,
                    'course'        => $period === 'One-time' ? $r->fee : $r->fee . ' (' . $period . ')',
                    'amount'        => (float) $r->amount,
                    'method'        => $r->method ?? '',
                    'reference'     => $r->reference ?? '',
                    'date'          => $paidAt->format('Y-m-d'),
                    'receipt'       => 'INV-A-' . $paidAt->format('Y') . '-' . str_pad((string) $r->id, 4, '0', STR_PAD_LEFT),
                    'archived_at'   => Carbon::parse($r->archived_at)->format('M d, Y h:i A'),
                    'archived_by'   => $r->archivedBy ?: '',
                    'reason'        => $r->archive_reason ?? '',
                    'replaced_by'   => $r->replacedById
                        ? 'INV-A-' . $paidAt->format('Y') . '-' . str_pad((string) $r->replacedById, 4, '0', STR_PAD_LEFT)
                        : '',
                ];
            })
            ->values();

        return response()->json([
            'success'               => true,
            'client'                => $this->fullName($user),
            'enrollments'           => [],
            'payments'              => $rows,
            'history'               => $history,
            'summary'               => $this->athleteSummary($user),
            'default_enrollment_id' => null,
        ]);
    }

    /**
     * Totals for the athlete's summary cards (fee / paid / balance / status).
     * Counts every due (fee + billing period) that has an active payment, plus,
     * for a current athlete, every ACTIVE fee that has no payment yet for the
     * current period. That second part matches the "Unpaid" rows the admin
     * Sales & Invoice page shows, so an athlete who hasn't paid anything yet
     * still sees what they owe. Archived payments never count.
     */
    private function athleteSummary(User $user): array
    {
        $dues = DB::table('tbl_athlete_payments')
            ->where('user_id', $user->getKey())
            ->whereNull('archived_at')
            ->selectRaw('MIN(amount_due) as due, SUM(amount) as paid')
            ->groupBy('athlete_fee_id', 'period_key')
            ->get();

        $fee  = (float) $dues->sum('due');
        $paid = (float) $dues->sum('paid');

        if ((int) $user->role_id === self::ATHLETE_ROLE_ID) {
            $periodCase = "CASE f.billing_cycle WHEN 'monthly' THEN ? WHEN 'yearly' THEN ? ELSE '' END";

            $fee += (float) DB::table('tbl_athlete_fees as f')
                ->where('f.is_active', 1)
                ->whereRaw(
                    'NOT EXISTS (SELECT 1 FROM tbl_athlete_payments x WHERE x.archived_at IS NULL'
                    . ' AND x.user_id = ? AND x.athlete_fee_id = f.athlete_fee_id AND x.period_key = ' . $periodCase . ')',
                    [$user->getKey(), now()->format('Y-m'), now()->format('Y')]
                )
                ->sum('f.amount');
        }

        $fee     = round($fee, 2);
        $paid    = round($paid, 2);
        $balance = max(round($fee - $paid, 2), 0);

        return [
            'fee'     => $fee,
            'paid'    => $paid,
            'balance' => $balance,
            'status'  => $fee <= 0 ? '' : ($balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')),
        ];
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