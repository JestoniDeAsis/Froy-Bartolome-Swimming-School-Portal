@extends('layouts.toast')

@php
    // Everything comes from AdminDashboardController::pageData(). The page is
    // rendered by name from role_menus.php (no route), so it is called here.
    $d = app(\App\Http\Controllers\AdminDashboardController::class)->pageData();

    $kpis       = $d['kpis'];
    $revenue    = $d['revenue'];
    $attendance = $d['attendance'];

    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);

    // Short form for the trend bars: 12500 -> 12.5k
    $pesoShort = function ($amount) {
        $amount = (float) $amount;
        if ($amount >= 1000000) return '₱' . round($amount / 1000000, 1) . 'M';
        if ($amount >= 1000)    return '₱' . round($amount / 1000, 1) . 'k';
        return '₱' . round($amount);
    };

    // Pill colour for an attendance status (Present / Late / Absent).
    $attendancePill = function (string $state): string {
        if (stripos($state, 'absent') !== false) return 'is-failed';
        if (stripos($state, 'late') !== false) return 'is-pending';
        return 'is-pass';
    };

    $change = $kpis['collectedChange'];
@endphp

<style>
/* Page-specific layout only. Typography, buttons and the base look come from
   default.css / our_theme.css. Mirrors the student dashboard's look. */

.adminDash { width: 100%; padding: 1.25rem; box-sizing: border-box; }
.adminDashHeader { margin-bottom: 1rem; }

.adminDashKpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.adminDashGrid {
    display: grid;
    grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
    gap: 1rem;
    align-items: start;
}

.adminDashCol { display: grid; gap: 1rem; align-content: start; }

.adminDashCard {
    padding: 1.1rem 1.25rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

.adminDashCardTitle {
    margin: 0 0 .85rem;
    color: #667085;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.adminDashCardHead {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: .5rem;
    margin-bottom: .85rem;
}
.adminDashCardHead .adminDashCardTitle { margin: 0; }

.adminDashNote { margin: .6rem 0 0; color: #667085; font-size: .8125rem; }
.adminDashNote.mt-0 { margin-top: 0; }

/* ---- KPI cards ---- */

.adminDashKpiLabel { margin: 0 0 .4rem; color: #667085; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.adminDashKpiValue { margin: 0; color: #1f2937; font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
.adminDashKpiValue span { color: #667085; font-size: .9rem; font-weight: 500; }
.adminDashKpiValue.isDanger  { color: var(--fbss-danger, #c22b3a); }
.adminDashKpiValue.isSuccess { color: var(--fbss-success, #1a7f3c); }
.adminDashKpiSub { margin: .45rem 0 0; color: #667085; font-size: .8125rem; }
.adminDashUp   { color: var(--fbss-success, #1a7f3c); font-weight: 600; }
.adminDashDown { color: var(--fbss-danger, #c22b3a); font-weight: 600; }

/* ---- Pills ---- */

.adminDashPill {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}
.adminDashPill.is-pass    { background: var(--fbss-success-tint, #e6f7ec); color: var(--fbss-success, #1a7f3c); }
.adminDashPill.is-failed  { background: var(--fbss-danger-tint, #fdebec);  color: var(--fbss-danger, #c22b3a); }
.adminDashPill.is-pending { background: #fff4e0; color: #b26a00; }
.adminDashPill.is-muted   { background: #eef1f4; color: #667085; }

/* ---- Needs attention ---- */

.adminDashAlerts { display: grid; gap: .5rem; }

.adminDashAlert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .6rem .75rem;
    background: #f8fafb;
    border-radius: 8px;
}

.adminDashAlertText { display: flex; align-items: center; gap: .6rem; min-width: 0; }
.adminDashAlertText i { font-size: 1.05rem; }
.adminDashAlertLabel { display: block; color: #344054; font-size: .875rem; font-weight: 600; }
.adminDashAlertHint  { display: block; color: #667085; font-size: .75rem; }

.adminDashAlert.is-danger  i { color: var(--fbss-danger, #c22b3a); }
.adminDashAlert.is-warning i { color: #b26a00; }
.adminDashAlert.is-success i { color: var(--fbss-success, #1a7f3c); }
.adminDashAlert.is-ok      i { color: #adb5bd; }

/* ---- Revenue ---- */

.adminDashTrend {
    display: grid;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    gap: .6rem;
    align-items: end;
    height: 150px;
    margin-bottom: 1rem;
}

.adminDashTrendCol { display: flex; flex-direction: column; justify-content: flex-end; align-items: center; height: 100%; gap: .3rem; }
.adminDashTrendValue { color: #667085; font-size: .6875rem; font-weight: 600; }
.adminDashTrendBarWrap { display: flex; align-items: flex-end; width: 100%; flex: 1 1 auto; min-height: 0; }
.adminDashTrendBar { width: 100%; min-height: 3px; background: #cfe0fb; border-radius: 6px 6px 0 0; }
.adminDashTrendCol.isCurrent .adminDashTrendBar { background: var(--fbss-blue, #0d6efd); }
.adminDashTrendCol.isCurrent .adminDashTrendValue { color: #1f2937; }
.adminDashTrendLabel { color: #667085; font-size: .75rem; font-weight: 600; }

/* ---- Rows (details, lists) ---- */

.adminDashDetails { margin: 0; display: grid; gap: .7rem; }

.adminDashDetails > div {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    padding-bottom: .7rem;
    border-bottom: 1px solid #f1f3f5;
}
.adminDashDetails > div:last-child { padding-bottom: 0; border-bottom: 0; }
.adminDashDetails dt { color: #667085; font-size: .85rem; font-weight: 500; }
.adminDashDetails dd { margin: 0; color: #1f2937; font-size: .9rem; font-weight: 600; text-align: right; }

.adminDashList { display: grid; gap: .5rem; }

.adminDashRow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .55rem .75rem;
    background: #f8fafb;
    border-radius: 8px;
}

.adminDashRowMain { min-width: 0; }
.adminDashRowTitle { color: #1f2937; font-size: .85rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.adminDashRowSub   { color: #667085; font-size: .75rem; }
.adminDashRowAmount { color: #1f2937; font-size: .875rem; font-weight: 700; white-space: nowrap; }
.adminDashRowAmount.isDanger { color: var(--fbss-danger, #c22b3a); }
.adminDashRowAmount.isSuccess { color: var(--fbss-success, #1a7f3c); }

/* ---- Courses ---- */

.adminDashCourse + .adminDashCourse { margin-top: .9rem; }
.adminDashCourseTop { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; margin-bottom: .35rem; }
.adminDashCourseName { color: #344054; font-size: .875rem; font-weight: 600; }
.adminDashCourseMeta { color: #667085; font-size: .75rem; }

.adminDash .progress { height: 8px; background: #e7ebf0; border-radius: 99px; }
.adminDash .progress-bar { background: var(--fbss-blue, #0d6efd); border-radius: 99px; }

@media (max-width: 1199.98px) {
    .adminDashKpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 991.98px) {
    .adminDashGrid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 575.98px) {
    .adminDash { padding: 1rem; }
    .adminDashKpis { grid-template-columns: minmax(0, 1fr); }
}
</style>

<section class="adminDash" id="adminDash" aria-labelledby="adminDashTitle">

    <header class="adminDashHeader">
        <h2 class="mb-1" id="adminDashTitle">Dashboard</h2>
        <p class="text-muted mb-0">Overview &mdash; {{ $d['dateLabel'] }}</p>
    </header>

    {{-- ===== KPI row ===== --}}
    <div class="adminDashKpis" id="adminDashKpis">

        <article class="adminDashCard">
            <h3 class="adminDashKpiLabel">Active Students</h3>
            <p class="adminDashKpiValue">{{ $kpis['activeStudents'] }}</p>
            <p class="adminDashKpiSub">
                {{ $kpis['newThisMonth'] }} new this month &middot; {{ $kpis['applicants'] }} not yet enrolled
            </p>
        </article>

        <article class="adminDashCard">
            <h3 class="adminDashKpiLabel">Checked In Today</h3>
            <p class="adminDashKpiValue">{{ $kpis['studentsIn'] }}<span> {{ $kpis['studentsIn'] === 1 ? 'student' : 'students' }}</span></p>
            <p class="adminDashKpiSub">Coaches in: {{ $kpis['coachesIn'] }} of {{ $kpis['activeCoaches'] }}</p>
        </article>

        <article class="adminDashCard">
            <h3 class="adminDashKpiLabel">Collected This Month</h3>
            <p class="adminDashKpiValue isSuccess">{{ $peso($kpis['collected']) }}</p>
            <p class="adminDashKpiSub">
                @if ($change === null)
                    No collections last month to compare.
                @else
                    <span class="{{ $change >= 0 ? 'adminDashUp' : 'adminDashDown' }}">
                        <i class="bi {{ $change >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>{{ abs($change) }}%
                    </span>
                    vs last month
                @endif
            </p>
        </article>

        <article class="adminDashCard">
            <h3 class="adminDashKpiLabel">Outstanding Balance</h3>
            <p class="adminDashKpiValue {{ $kpis['outstanding'] > 0 ? 'isDanger' : '' }}">{{ $peso($kpis['outstanding']) }}</p>
            <p class="adminDashKpiSub">
                {{ $kpis['owingCount'] }} {{ $kpis['owingCount'] === 1 ? 'enrollment' : 'enrollments' }} with a balance
            </p>
        </article>

    </div>

    <div class="adminDashGrid">

        {{-- ===== Left column: money, attendance, courses ===== --}}
        <div class="adminDashCol">

            {{-- Revenue --}}
            <article class="adminDashCard" id="adminDashRevenue">
                <div class="adminDashCardHead">
                    <h3 class="adminDashCardTitle">Collections, Last {{ count($revenue['trend']) }} Months</h3>
                </div>

                <div class="adminDashTrend" role="img" aria-label="Monthly collections">
                    @foreach ($revenue['trend'] as $month)
                        <div class="adminDashTrendCol {{ $month['current'] ? 'isCurrent' : '' }}" title="{{ $peso($month['total']) }}">
                            <span class="adminDashTrendValue">{{ $pesoShort($month['total']) }}</span>
                            <div class="adminDashTrendBarWrap">
                                <div class="adminDashTrendBar" style="height: {{ $month['percent'] }}%"></div>
                            </div>
                            <span class="adminDashTrendLabel">{{ $month['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                <dl class="adminDashDetails">
                    <div><dt>Course payments (this month)</dt><dd>{{ $peso($revenue['thisMonth']['course']) }}</dd></div>
                    <div><dt>Item purchases (this month)</dt><dd>{{ $peso($revenue['thisMonth']['items']) }}</dd></div>
                    <div><dt>Athlete fees (this month)</dt><dd>{{ $peso($revenue['thisMonth']['athlete']) }}</dd></div>
                    <div><dt>Total this month</dt><dd>{{ $peso($revenue['thisMonth']['total']) }}</dd></div>
                </dl>
            </article>

            {{-- Today's attendance --}}
            <article class="adminDashCard" id="adminDashAttendance">
                <div class="adminDashCardHead">
                    <h3 class="adminDashCardTitle">Today&rsquo;s Attendance</h3>
                    <span class="adminDashPill is-muted">{{ $attendance['total'] }} logged</span>
                </div>

                @if (empty($attendance['rows']))
                    <p class="adminDashNote mt-0">No attendance recorded today.</p>
                @else
                    <div class="adminDashList">
                        @foreach ($attendance['rows'] as $row)
                            <div class="adminDashRow">
                                <div class="adminDashRowMain">
                                    <div class="adminDashRowTitle">{{ $row['name'] }}</div>
                                    <div class="adminDashRowSub">{{ $row['role'] }} &middot; {{ $row['in'] ?? '-' }} &ndash; {{ $row['out'] ?? '-' }}</div>
                                </div>
                                <span class="adminDashPill {{ $attendancePill($row['state']) }}">{{ $row['state'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($attendance['more'] > 0)
                        <p class="adminDashNote">+ {{ $attendance['more'] }} more. See the Attendance page for the full list.</p>
                    @endif
                @endif
            </article>

            {{-- Recent payments --}}
            <article class="adminDashCard" id="adminDashPayments">
                <h3 class="adminDashCardTitle">Recent Payments</h3>

                @if (empty($d['recentPayments']))
                    <p class="adminDashNote mt-0">No payments recorded yet.</p>
                @else
                    <div class="adminDashList">
                        @foreach ($d['recentPayments'] as $p)
                            <div class="adminDashRow">
                                <div class="adminDashRowMain">
                                    <div class="adminDashRowTitle">{{ $p['who'] }}</div>
                                    <div class="adminDashRowSub">
                                        {{ $p['type'] }}@if ($p['detail']) &middot; {{ $p['detail'] }}@endif
                                        &middot; {{ $p['method'] }} &middot; {{ $p['at'] }}
                                    </div>
                                </div>
                                <span class="adminDashRowAmount isSuccess">{{ $peso($p['amount']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

        </div>

        {{-- ===== Right column: what needs attention ===== --}}
        <div class="adminDashCol">

            {{-- Needs attention --}}
            <article class="adminDashCard" id="adminDashAttention">
                <h3 class="adminDashCardTitle">Needs Attention</h3>
                <div class="adminDashAlerts">
                    @foreach ($d['attention'] as $alert)
                        <div class="adminDashAlert is-{{ $alert['tone'] }}">
                            <div class="adminDashAlertText">
                                <i class="bi {{ $alert['icon'] }}" aria-hidden="true"></i>
                                <div>
                                    <span class="adminDashAlertLabel">{{ $alert['label'] }}</span>
                                    <span class="adminDashAlertHint">{{ $alert['hint'] }}</span>
                                </div>
                            </div>
                            @php
                                $pill = ['danger' => 'is-failed', 'warning' => 'is-pending', 'success' => 'is-pass', 'ok' => 'is-muted'][$alert['tone']];
                            @endphp
                            <span class="adminDashPill {{ $pill }}">{{ $alert['count'] }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            {{-- Ready to graduate --}}
            <article class="adminDashCard" id="adminDashReady">
                <h3 class="adminDashCardTitle">Ready to Graduate</h3>

                @if (empty($d['ready']))
                    <p class="adminDashNote mt-0">No one is waiting to graduate.</p>
                @else
                    <div class="adminDashList">
                        @foreach ($d['ready'] as $row)
                            <div class="adminDashRow">
                                <div class="adminDashRowMain">
                                    <div class="adminDashRowTitle">{{ $row['name'] }}</div>
                                    <div class="adminDashRowSub">{{ $row['course'] }}</div>
                                </div>
                                <span class="adminDashPill {{ $row['cleared'] ? 'is-pass' : 'is-pending' }}">{{ $row['cleared'] ? 'Cleared' : 'Awaiting clearance' }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($d['readyMore'] > 0)
                        <p class="adminDashNote">+ {{ $d['readyMore'] }} more on the Graduation page.</p>
                    @endif
                @endif
            </article>

            {{-- Outstanding balances --}}
            <article class="adminDashCard" id="adminDashOwing">
                <h3 class="adminDashCardTitle">Largest Balances</h3>

                @if (empty($d['owing']))
                    <p class="adminDashNote mt-0">Everyone is fully paid.</p>
                @else
                    <div class="adminDashList">
                        @foreach ($d['owing'] as $row)
                            <div class="adminDashRow">
                                <div class="adminDashRowMain">
                                    <div class="adminDashRowTitle">{{ $row['name'] }}</div>
                                    <div class="adminDashRowSub">{{ $row['course'] }}@if ($row['graduated']) &middot; Graduated @endif</div>
                                </div>
                                <span class="adminDashRowAmount isDanger">{{ $peso($row['balance']) }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if ($d['owingMore'] > 0)
                        <p class="adminDashNote">+ {{ $d['owingMore'] }} more with a balance.</p>
                    @endif
                @endif
            </article>

            {{-- Courses --}}
            <article class="adminDashCard" id="adminDashCourses">
                <h3 class="adminDashCardTitle">Active Courses</h3>

                @if (empty($d['courses']))
                    <p class="adminDashNote mt-0">No active enrollments.</p>
                @else
                    @foreach ($d['courses'] as $course)
                        <div class="adminDashCourse">
                            <div class="adminDashCourseTop">
                                <span class="adminDashCourseName">{{ $course['name'] }}</span>
                                <span class="adminDashCourseMeta">
                                    {{ $course['students'] }} {{ $course['students'] === 1 ? 'student' : 'students' }}
                                    @if ($course['progress'] !== null) &middot; {{ $course['progress'] }}% avg progress @endif
                                </span>
                            </div>
                            @if ($course['progress'] !== null)
                                <div class="progress" role="progressbar" aria-label="Average session progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $course['progress'] }}">
                                    <div class="progress-bar" style="width: {{ $course['progress'] }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif
            </article>

            {{-- Low stock --}}
            <article class="adminDashCard" id="adminDashStock">
                <h3 class="adminDashCardTitle">Low Stock</h3>

                @if (empty($d['lowStock']))
                    <p class="adminDashNote mt-0">All items are stocked.</p>
                @else
                    <div class="adminDashList">
                        @foreach ($d['lowStock'] as $item)
                            <div class="adminDashRow">
                                <div class="adminDashRowTitle">{{ $item['name'] }}</div>
                                <span class="adminDashPill {{ $item['stock'] === 0 ? 'is-failed' : 'is-pending' }}">{{ $item['stock'] === 0 ? 'Out of stock' : $item['stock'] . ' left' }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

        </div>

    </div>

</section>