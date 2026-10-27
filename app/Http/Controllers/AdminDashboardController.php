<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Data for the admin dashboard (admin_dashboard_blade).
 *
 * Same pattern as StudentDashboardController: the page is rendered by name from
 * role_menus.php (no route), so the blade calls pageData() itself.
 *
 * Everything uses the query builder on purpose, so this file does not depend on
 * any model scopes. Dates and money come back ready for display.
 *
 * What the page answers, in order of how an admin would ask it:
 *   1. How is the school doing right now?   -> kpis, attendance today
 *   2. Where is the money?                  -> revenue, trend, recent payments, owing
 *   3. What needs my attention?             -> attention, ready to graduate, low stock
 *   4. Which courses are busy?              -> courses
 */
class AdminDashboardController extends Controller
{
    /** tbl_roles.role_name / tbl_status.status_name values (case-insensitive). Edit here if yours differ. */
    private const ROLE_STUDENT  = 'Student';
    private const ROLE_COACH    = 'Coach';
    private const STATUS_ACTIVE = 'Active';

    /** An item counts as "low stock" at or below this quantity. */
    private const LOW_STOCK_AT = 5;

    /** An enrolled student with no attended session for this many days is flagged as inactive. */
    private const INACTIVE_DAYS = 14;

    /** Rows shown in each short list. */
    private const LIST_LIMIT = 5;

    /** Months shown in the revenue trend (including the current one). */
    private const TREND_MONTHS = 6;

    /** Revenue sources: key => [table, amount column, date column]. */
    private const SOURCES = [
        'course'  => ['tbl_course_payments',  'amount',      'paid_at'],
        'items'   => ['tbl_item_logs',        'amount_paid', 'sold_at'],
        'athlete' => ['tbl_athlete_payments', 'amount',      'paid_at'],
    ];

    /** Sources whose rows can be archived (archived_at set by the admin); those rows never count. */
    private const ARCHIVABLE = ['tbl_course_payments', 'tbl_athlete_payments'];

    public function pageData(): array
    {
        $now   = Carbon::now();
        $today = Carbon::today();

        $studentRole = $this->idByName('tbl_roles', 'role_id', 'role_name', self::ROLE_STUDENT);
        $coachRole   = $this->idByName('tbl_roles', 'role_id', 'role_name', self::ROLE_COACH);
        $activeState = $this->idByName('tbl_status', 'status_id', 'status_name', self::STATUS_ACTIVE);

        $enrollments = $this->enrollments();
        // Active = not graduated and not dropped (a dropped enrollment is not current).
        $active      = $enrollments->filter(fn ($e) => $e->graduation_at === null && $e->dropped_at === null)->values();

        $attendanceToday = $this->attendanceToday($today);
        $revenue         = $this->revenue($now);
        $lowStock        = $this->lowStock();

        // ---- People ------------------------------------------------------------------
        $activeCoaches = DB::table('tbl_users')
            ->where('role_id', $coachRole)->where('status_id', $activeState)->count();

        $applicants = DB::table('tbl_users as u')
            ->where('u.role_id', $studentRole)
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('tbl_enrollments as e')->whereColumn('e.user_id', 'u.user_id'))
            ->count();

        $newThisMonth = DB::table('tbl_users')
            ->where('role_id', $studentRole)
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->count();

        // ---- Money owed (every enrollment, graduated ones included) ------------------
        $owing = $enrollments->filter(fn ($e) => $e->balance > 0)->sortByDesc('balance')->values();

        // ---- Attention items ---------------------------------------------------------
        $needsAssessment = $active->filter(fn ($e) => $e->total > 0 && $e->done >= $e->total && ! $e->ready);
        $ready           = $active->filter(fn ($e) => $e->ready)->values();

        $cutoff   = $today->copy()->subDays(self::INACTIVE_DAYS);
        $inactive = $active->filter(function ($e) use ($cutoff) {
            if ($e->total > 0 && $e->done >= $e->total) {
                return false;   // finished the sessions; waiting on assessment, not absent
            }
            $reference = $e->last_session ?: $e->enrollment_at;
            return $reference && Carbon::parse($reference)->lt($cutoff);
        });

        $coveredCourses = DB::table('tbl_coach_courses')->pluck('course_id')->all();
        $uncovered      = $active->pluck('course_id')->filter()->unique()
            ->reject(fn ($id) => in_array((int) $id, array_map("intval", $coveredCourses), true));

        $attention = [
            $this->alert('Ready to graduate',          $ready->count(),           'bi-mortarboard-fill',        'success', 'Passed assessment, not yet graduated'),
            $this->alert('Sessions done, not assessed', $needsAssessment->count(), 'bi-clipboard2-check-fill',   'warning', 'Finished all sessions; coach has not marked them ready'),
            $this->alert('Students with a balance',    $owing->count(),           'bi-cash-coin',               'danger',  'Paid less than the course price'),
            $this->alert('Inactive students',          $inactive->count(),        'bi-person-dash-fill',        'warning', 'No session in ' . self::INACTIVE_DAYS . '+ days'),
            $this->alert('Courses without a coach',    $uncovered->count(),       'bi-person-exclamation',      'danger',  'Has active students, no coach assigned'),
            $this->alert('Low-stock items',            $lowStock['count'],        'bi-box-seam-fill',           'warning', 'At or below ' . self::LOW_STOCK_AT . ' in stock'),
        ];

        return [
            'dateLabel' => $now->format('l, F j, Y'),

            'kpis' => [
                'activeStudents'  => $active->pluck('user_id')->unique()->count(),
                'activeEnrolled'  => $active->count(),
                'applicants'      => $applicants,
                'newThisMonth'    => $newThisMonth,
                'studentsIn'      => $attendanceToday['studentsIn'],
                'coachesIn'       => $attendanceToday['coachesIn'],
                'activeCoaches'   => $activeCoaches,
                'collected'       => $revenue['thisMonth']['total'],
                'collectedChange' => $revenue['change'],
                'outstanding'     => (float) $owing->sum('balance'),
                'owingCount'      => $owing->count(),
            ],

            'attention'  => $attention,
            'revenue'    => $revenue,
            'attendance' => $attendanceToday,

            'ready' => $ready->take(self::LIST_LIMIT)->map(fn ($e) => [
                'name'    => $e->student_name,
                'course'  => $e->course_name,
                'cleared' => $e->cleared_for_graduation_at !== null,
            ])->all(),
            'readyMore' => max(0, $ready->count() - self::LIST_LIMIT),

            'owing' => $owing->take(self::LIST_LIMIT)->map(fn ($e) => [
                'name'      => $e->student_name,
                'course'    => $e->course_name,
                'balance'   => $e->balance,
                'graduated' => $e->graduation_at !== null,
            ])->all(),
            'owingMore' => max(0, $owing->count() - self::LIST_LIMIT),

            'courses'        => $this->courseBreakdown($active),
            'recentPayments' => $this->recentPayments(),
            'lowStock'       => $lowStock['rows'],
        ];
    }

    /* =====================================================================
     |  Enrollments (one query, reused by every enrollment-based widget)
     | ===================================================================== */

    /**
     * Every enrollment with course, student name, paid-to-date, sessions attended
     * and assessment flag. Payments are matched on tbl_course_payments.enrollment_id
     * (a foreign key to tbl_enrollments.enrollment_id).
     */
    private function enrollments(): Collection
    {
        $paid = DB::table('tbl_course_payments')
            ->whereNull('archived_at')
            ->selectRaw('enrollment_id, SUM(amount) AS paid')
            ->groupBy('enrollment_id');

        $sessions = DB::table('tbl_attendances')
            ->selectRaw('enrollment_id, COUNT(*) AS sessions_done, MAX(attendance_at) AS last_session')
            ->whereNotNull('enrollment_id')
            ->whereNotNull('time_in')
            ->groupBy('enrollment_id');

        return DB::table('tbl_enrollments as e')
            ->leftJoin('tbl_courses as c', 'c.course_id', '=', 'e.course_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_skill_assessments as sa', 'sa.enrollment_id', '=', 'e.enrollment_id')
            ->leftJoinSub($paid, 'pay', 'pay.enrollment_id', '=', 'e.enrollment_id')
            ->leftJoinSub($sessions, 'att', 'att.enrollment_id', '=', 'e.enrollment_id')
            ->select([
                'e.enrollment_id', 'e.user_id', 'e.course_id', 'e.enrollment_at',
                'e.graduation_at', 'e.dropped_at', 'e.cleared_for_graduation_at',
                'c.course_name', 'c.course_session_count', 'c.course_price',
                'e.course_price_at_enrollment',
                'sa.ready_to_graduate',
                'pay.paid', 'att.sessions_done', 'att.last_session',
            ])
            ->selectRaw($this->nameSql() . ' AS student_name')
            ->get()
            ->map(function ($e) {
                $price = (float) ($e->course_price_at_enrollment ?? $e->course_price ?? 0);
                $paid  = (float) ($e->paid ?? 0);

                $e->course_name  = $e->course_name ?: 'Course not assigned';
                $e->student_name = $e->student_name ?: 'Unknown';
                $e->total        = max(0, (int) ($e->course_session_count ?? 0));
                $e->done         = (int) ($e->sessions_done ?? 0);
                $e->ready        = (bool) $e->ready_to_graduate;
                $e->balance      = max(0, $price - $paid);

                return $e;
            });
    }

    private function courseBreakdown(Collection $active): array
    {
        return $active->groupBy(fn ($e) => $e->course_name)
            ->map(function (Collection $group, string $name) {
                $rated = $group->filter(fn ($e) => $e->total > 0);

                return [
                    'name'     => $name,
                    'students' => $group->count(),
                    'progress' => $rated->isEmpty()
                        ? null
                        : (int) round($rated->avg(fn ($e) => min(1, $e->done / $e->total)) * 100),
                ];
            })
            ->sortByDesc('students')
            ->values()
            ->all();
    }

    /* =====================================================================
     |  Attendance
     | ===================================================================== */

    private function attendanceToday(Carbon $today): array
    {
        $rows = DB::table('tbl_attendances as a')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'a.user_id')
            ->leftJoin('tbl_roles as r', 'r.role_id', '=', DB::raw('COALESCE(a.role_id, u.role_id)'))
            ->leftJoin('tbl_enrollments as e', 'e.enrollment_id', '=', 'a.enrollment_id')
            ->whereDate('a.attendance_at', $today->toDateString())
            ->whereNull('e.dropped_at')   // coach rows have no enrollment, so they always pass
            ->orderByDesc('a.time_in')
            ->select(['a.user_id', 'a.time_in', 'a.time_out', 'a.attendance_state', 'r.role_name'])
            ->selectRaw($this->nameSql() . ' AS person_name')
            ->get();

        $in = $rows->filter(fn ($r) => $r->time_in !== null);

        $isRole = fn (string $role) => fn ($r) => strcasecmp((string) $r->role_name, $role) === 0;

        return [
            'studentsIn' => $in->filter($isRole(self::ROLE_STUDENT))->pluck('user_id')->unique()->count(),
            'coachesIn'  => $in->filter($isRole(self::ROLE_COACH))->pluck('user_id')->unique()->count(),
            'total'      => $rows->count(),
            'rows'       => $rows->take(8)->map(fn ($r) => [
                'name'  => $r->person_name ?: 'Unknown',
                'role'  => $r->role_name ?: '-',
                'in'    => $r->time_in ? Carbon::parse($r->time_in)->format('g:i A') : null,
                'out'   => $r->time_out ? Carbon::parse($r->time_out)->format('g:i A') : null,
                'state' => $r->attendance_state ?: ($r->time_in ? 'Present' : 'Absent'),
            ])->all(),
            'more' => max(0, $rows->count() - 8),
        ];
    }

    /* =====================================================================
     |  Money
     | ===================================================================== */

    /**
     * This month's collections by source, last month's total for comparison, and a
     * small month-by-month trend. One grouped query per source.
     */
    private function revenue(Carbon $now): array
    {
        $first = $now->copy()->startOfMonth()->subMonths(self::TREND_MONTHS - 1);

        // Month keys, oldest first.
        $months = [];
        for ($i = 0; $i < self::TREND_MONTHS; $i++) {
            $month = $first->copy()->addMonths($i);
            $months[$month->format('Y-m')] = ['label' => $month->format('M'), 'total' => 0.0];
        }

        $thisKey = $now->format('Y-m');
        $lastKey = $now->copy()->subMonthNoOverflow()->format('Y-m');
        $bySource = array_fill_keys(array_keys(self::SOURCES), 0.0);

        foreach (self::SOURCES as $source => [$table, $amount, $date]) {
            $sums = DB::table($table)
                ->when(in_array($table, self::ARCHIVABLE, true), fn ($q) => $q->whereNull('archived_at'))
                ->selectRaw("DATE_FORMAT($date, '%Y-%m') AS ym, SUM($amount) AS total")
                ->where($date, '>=', $first)
                ->groupBy('ym')
                ->pluck('total', 'ym');

            foreach ($sums as $key => $total) {
                if (isset($months[$key])) {
                    $months[$key]['total'] += (float) $total;
                }
                if ($key === $thisKey) {
                    $bySource[$source] = (float) $total;
                }
            }
        }

        $thisTotal = $months[$thisKey]['total'] ?? 0.0;
        $lastTotal = $months[$lastKey]['total'] ?? 0.0;
        $max       = max(1.0, (float) collect($months)->max('total'));

        return [
            'thisMonth' => $bySource + ['total' => $thisTotal],
            'lastMonth' => $lastTotal,
            // null when there is nothing to compare against
            'change'    => $lastTotal > 0 ? (int) round((($thisTotal - $lastTotal) / $lastTotal) * 100) : null,
            'trend'     => collect($months)->map(fn ($m, $key) => [
                'label'   => $m['label'],
                'total'   => $m['total'],
                'percent' => (int) round(($m['total'] / $max) * 100),
                'current' => $key === $thisKey,
            ])->values()->all(),
        ];
    }

    /** Latest payments across course payments, item sales and athlete fees. */
    private function recentPayments(): array
    {
        $limit = 6;

        $course = DB::table('tbl_course_payments as p')
            ->leftJoin('tbl_enrollments as e', 'e.enrollment_id', '=', 'p.enrollment_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'e.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'e.user_id')
            ->whereNull('p.archived_at')
            ->orderByDesc('p.paid_at')->limit($limit)
            ->select(['p.amount', 'p.paid_at AS at', 'p.payment_method'])
            ->selectRaw($this->nameSql() . ' AS who')
            ->selectRaw("'Course' AS type")
            ->get();

        $items = DB::table('tbl_item_logs as l')
            ->leftJoin('tbl_items as i', 'i.item_id', '=', 'l.item_id')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'l.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'l.user_id')
            ->orderByDesc('l.sold_at')->limit($limit)
            ->select(['l.amount_paid AS amount', 'l.sold_at AS at', 'l.payment_method', 'i.item_name'])
            ->selectRaw($this->nameSql() . ' AS who')
            ->selectRaw("'Item' AS type")
            ->get();

        $athlete = DB::table('tbl_athlete_payments as a')
            ->leftJoin('tbl_users as u', 'u.user_id', '=', 'a.user_id')
            ->leftJoin('tbl_user_profile as pr', 'pr.user_id', '=', 'a.user_id')
            ->whereNull('a.archived_at')
            ->orderByDesc('a.paid_at')->limit($limit)
            ->select(['a.amount', 'a.paid_at AS at', 'a.payment_method'])
            ->selectRaw($this->nameSql() . ' AS who')
            ->selectRaw("'Athlete fee' AS type")
            ->get();

        return $course->concat($items)->concat($athlete)
            ->sortByDesc('at')
            ->take($limit)
            ->map(fn ($p) => [
                'who'    => $p->who ?: 'Walk-in',
                'type'   => $p->type,
                'detail' => $p->item_name ?? null,
                'method' => $p->payment_method,
                'amount' => (float) $p->amount,
                'at'     => Carbon::parse($p->at)->format('M d, g:i A'),
            ])
            ->values()
            ->all();
    }

    /* =====================================================================
     |  Inventory
     | ===================================================================== */

    private function lowStock(): array
    {
        $query = DB::table('tbl_items')
            ->where('is_active', 1)
            ->where('stock_quantity', '<=', self::LOW_STOCK_AT);

        return [
            'count' => (clone $query)->count(),
            'rows'  => $query->orderBy('stock_quantity')->orderBy('item_name')
                ->limit(self::LIST_LIMIT)
                ->get(['item_name', 'stock_quantity'])
                ->map(fn ($i) => ['name' => $i->item_name, 'stock' => (int) $i->stock_quantity])
                ->all(),
        ];
    }

    /* =====================================================================
     |  Helpers
     | ===================================================================== */

    /** Case-insensitive lookup of a role/status id by name. 0 when missing, so counts are 0 rather than matching NULL. */
    private function idByName(string $table, string $idColumn, string $nameColumn, string $name): int
    {
        return (int) DB::table($table)
            ->whereRaw("LOWER($nameColumn) = ?", [strtolower($name)])
            ->value($idColumn);
    }

    /** "First Last", falling back to the username. Expects joins aliased `pr` (profile) and `u` (users). */
    private function nameSql(): string
    {
        return "COALESCE(NULLIF(TRIM(CONCAT_WS(' ', pr.first_name, pr.last_name)), ''), u.username)";
    }

    /** One row of the "needs attention" card. A zero count is shown as all clear. */
    private function alert(string $label, int $count, string $icon, string $tone, string $hint): array
    {
        return [
            'label' => $label,
            'count' => $count,
            'icon'  => $icon,
            'tone'  => $count > 0 ? $tone : 'ok',
            'hint'  => $hint,
        ];
    }
}