@extends('layouts.toast')

@php
    // Everything comes from CoachDashboardController::pageData(). The page is
    // rendered by name from role_menus.php (no route), so it is called here.
    $d = app(\App\Http\Controllers\CoachDashboardController::class)->pageData(auth()->user());

    $kpis = $d['kpis'];
    $mine = $d['myAttendance'];
@endphp

<style>
/* Page-specific layout only. Typography, buttons and the base look come from
   default.css / our_theme.css. Mirrors the student and admin dashboards. */

.coachDash { width: 100%; padding: 1.25rem; box-sizing: border-box; }
.coachDashHeader {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.coachDashKpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.coachDashSectionTitle { margin: 0 0 .15rem; color: #1f2937; font-size: 1rem; font-weight: 700; text-transform: uppercase; }
.coachDashSectionSub   { margin: 0 0 .85rem; color: #667085; font-size: .8125rem; }

.coachDashCard {
    padding: 1.1rem 1.25rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

.coachDashCardTitle {
    margin: 0 0 .85rem;
    color: #667085;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.coachDashCardHead { display: flex; align-items: baseline; justify-content: space-between; gap: .5rem; margin-bottom: .85rem; }
.coachDashCardHead .coachDashCardTitle { margin: 0; }

.coachDashNote { margin: .6rem 0 0; color: #667085; font-size: .8125rem; }
.coachDashNote.mt-0 { margin-top: 0; }

/* ---- KPI cards ---- */

.coachDashKpiLabel { margin: 0 0 .4rem; color: #667085; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
.coachDashKpiValue { margin: 0; color: #1f2937; font-size: 1.75rem; font-weight: 700; line-height: 1.1; }
.coachDashKpiValue span { color: #667085; font-size: .9rem; font-weight: 500; }
.coachDashKpiValue.isWarning { color: #b26a00; }
.coachDashKpiValue.isSuccess { color: var(--fbss-success, #1a7f3c); }
.coachDashKpiSub { margin: .45rem 0 0; color: #667085; font-size: .8125rem; }

/* ---- Pills ---- */

.coachDashPill {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}
.coachDashPill.is-pass    { background: var(--fbss-success-tint, #e6f7ec); color: var(--fbss-success, #1a7f3c); }
.coachDashPill.is-failed  { background: var(--fbss-danger-tint, #fdebec);  color: var(--fbss-danger, #c22b3a); }
.coachDashPill.is-pending { background: #fff4e0; color: #b26a00; }
.coachDashPill.is-info    { background: #e7f1ff; color: var(--fbss-blue, #0d6efd); }
.coachDashPill.is-muted   { background: #eef1f4; color: #667085; }

/* ---- Class roster cards ---- */

.coachDashClasses {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.coachDashClassTop {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .5rem;
    padding-bottom: .75rem;
    border-bottom: 1px solid #f1f3f5;
}
.coachDashClassName { margin: 0; color: #1f2937; font-size: 1rem; font-weight: 700; }

.coachDashRosterLabel { margin: .75rem 0 .5rem; color: #98a2b3; font-size: .6875rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }

.coachDashRoster { display: grid; gap: .55rem; }

.coachDashStudent {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    min-width: 0;
}
.coachDashStudentMain {
    display: flex;
    align-items: flex-start;
    gap: .5rem;
    flex: 1 1 auto;
    min-width: 0;
}
.coachDashStudentMain .coachDashDot { margin-top: .35rem; }
.coachDashStudentName {
    color: #1f2937;
    font-size: .85rem;
    font-weight: 600;
    min-width: 0;
    overflow-wrap: anywhere;
    line-height: 1.35;
}
.coachDashStudentMeta {
    color: #667085;
    font-size: .75rem;
    white-space: normal;
    text-align: right;
    flex: 0 0 auto;
    max-width: 48%;
}

.coachDashDot { flex: 0 0 auto; width: 8px; height: 8px; border-radius: 50%; background: #c4cad4; }
.coachDashDot.isIn { background: var(--fbss-success, #1a7f3c); }

.coachDashLegend { display: flex; align-items: center; gap: .4rem; margin-bottom: .75rem; color: #667085; font-size: .75rem; }

/* ---- Lower grid ---- */

.coachDashGrid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; align-items: start; }
.coachDashCol  { display: grid; gap: 1rem; align-content: start; }

.coachDashList { display: grid; gap: .5rem; }

.coachDashRow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .55rem .75rem;
    background: #f8fafb;
    border-radius: 8px;
}
.coachDashRowMain  { min-width: 0; }
.coachDashRowTitle { color: #1f2937; font-size: .85rem; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.coachDashRowSub   { color: #667085; font-size: .75rem; }
.coachDashRowAmount { color: #1f2937; font-size: .875rem; font-weight: 700; white-space: nowrap; }

/* ---- Empty state ---- */

.coachDashEmpty { max-width: 520px; margin: 1.5rem auto 0; padding: 2.25rem 1.5rem; text-align: center; }
.coachDashEmptyIcon {
    display: grid;
    place-items: center;
    width: 4.5rem;
    height: 4.5rem;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background: #e7f1ff;
    color: var(--fbss-blue, #0d6efd);
    font-size: 2rem;
}
.coachDashEmpty h3 { margin: 0 0 .4rem; color: #1f2937; font-size: 1.2rem; font-weight: 700; }
.coachDashEmpty p  { margin: 0; color: #667085; font-size: .9rem; }

@media (max-width: 1199.98px) {
    .coachDashKpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 991.98px) {
    .coachDashGrid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 575.98px) {
    .coachDash { padding: 1rem; }
    .coachDashKpis { grid-template-columns: minmax(0, 1fr); }
}
</style>

<section class="coachDash" id="coachDash" aria-labelledby="coachDashTitle">

    <header class="coachDashHeader">
        <div>
            <h2 class="mb-1" id="coachDashTitle">Dashboard</h2>
            <p class="text-muted mb-0">Welcome, {{ $d['firstName'] }}. &mdash; {{ $d['dateLabel'] }}</p>
        </div>

        {{-- The coach's own attendance for today --}}
        @if ($mine)
            <span class="coachDashPill is-pass">
                <i class="bi bi-check-circle-fill me-1"></i>You checked in at {{ $mine['in'] }}@if ($mine['out']) &middot; out {{ $mine['out'] }}@endif
            </span>
        @else
            <span class="coachDashPill is-muted"><i class="bi bi-clock me-1"></i>You haven&rsquo;t checked in today</span>
        @endif
    </header>

    @if (! $d['hasCourses'])

        <div class="coachDashCard coachDashEmpty" id="coachDashEmpty">
            <div class="coachDashEmptyIcon"><i class="bi bi-water" aria-hidden="true"></i></div>
            <h3>No courses assigned yet.</h3>
            <p>Once an admin assigns you to a course, your class rosters and assessments will show up here.</p>
        </div>

    @else

        {{-- ===== KPI row ===== --}}
        <div class="coachDashKpis" id="coachDashKpis">

            <article class="coachDashCard">
                <h3 class="coachDashKpiLabel">My Students</h3>
                <p class="coachDashKpiValue">{{ $kpis['students'] }}</p>
                <p class="coachDashKpiSub">Currently enrolled in your courses</p>
            </article>

            <article class="coachDashCard">
                <h3 class="coachDashKpiLabel">Checked In Today</h3>
                <p class="coachDashKpiValue">{{ $kpis['checkedIn'] }}<span> / {{ $kpis['students'] }}</span></p>
                <p class="coachDashKpiSub">Students with a Time In today</p>
            </article>

            <article class="coachDashCard">
                <h3 class="coachDashKpiLabel">Pending Assessments</h3>
                <p class="coachDashKpiValue {{ $kpis['pending'] > 0 ? 'isWarning' : '' }}">{{ $kpis['pending'] }}</p>
                <p class="coachDashKpiSub">Finished all sessions, not yet marked ready</p>
            </article>

            <article class="coachDashCard">
                <h3 class="coachDashKpiLabel">Ready to Graduate</h3>
                <p class="coachDashKpiValue {{ $kpis['ready'] > 0 ? 'isSuccess' : '' }}">{{ $kpis['ready'] }}</p>
                <p class="coachDashKpiSub">Assessed and waiting on graduation</p>
            </article>

        </div>

        {{-- ===== Class rosters ===== --}}
        <h3 class="coachDashSectionTitle">Student Roster</h3>
        <p class="coachDashSectionSub">Your assigned courses and who is enrolled.</p>

        <div class="coachDashLegend">
            <span class="coachDashDot isIn"></span> Checked in today
            <span class="coachDashDot ms-2"></span> Not yet today
        </div>

        <div class="coachDashClasses" id="coachDashClasses">
            @foreach ($d['classes'] as $class)
                <article class="coachDashCard">
                    <div class="coachDashClassTop">
                        <h4 class="coachDashClassName">{{ $class['name'] }}</h4>
                        <span class="coachDashPill is-info">{{ $class['enrolled'] }} Enrolled</span>
                    </div>

                    <p class="coachDashRosterLabel">Roster preview</p>

                    @if (empty($class['preview']))
                        <p class="coachDashNote mt-0">No students enrolled yet.</p>
                    @else
                        <div class="coachDashRoster">
                            @foreach ($class['preview'] as $student)
                                <div class="coachDashStudent">
                                    <div class="coachDashStudentMain">
                                        <span class="coachDashDot {{ $student['in'] ? 'isIn' : '' }}"></span>
                                        <span class="coachDashStudentName">{{ $student['name'] }}</span>
                                    </div>
                                    <div class="coachDashStudentMeta">
                                        @if ($student['schedule']){{ $student['schedule'] }}<br>@endif
                                        {{ $student['done'] }}{{ $student['total'] > 0 ? ' / ' . $student['total'] : '' }} sessions
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($class['more'] > 0)
                            <p class="coachDashNote">+ {{ $class['more'] }} more. {{ $class['checkedIn'] }} checked in today.</p>
                        @else
                            <p class="coachDashNote">{{ $class['checkedIn'] }} checked in today.</p>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>

        {{-- ===== Work queue ===== --}}
        <div class="coachDashGrid">

            <div class="coachDashCol">

                {{-- Pending assessments --}}
                <article class="coachDashCard" id="coachDashPending">
                    <div class="coachDashCardHead">
                        <h3 class="coachDashCardTitle">Needs Assessment</h3>
                        <span class="coachDashPill {{ $kpis['pending'] > 0 ? 'is-pending' : 'is-muted' }}">{{ $kpis['pending'] }}</span>
                    </div>

                    @if (empty($d['pending']))
                        <p class="coachDashNote mt-0">Nobody is waiting on an assessment.</p>
                    @else
                        <div class="coachDashList">
                            @foreach ($d['pending'] as $row)
                                <div class="coachDashRow">
                                    <div class="coachDashRowMain">
                                        <div class="coachDashRowTitle">{{ $row['name'] }}</div>
                                        <div class="coachDashRowSub">{{ $row['course'] }}</div>
                                    </div>
                                    <span class="coachDashPill is-pending">{{ $row['done'] }} / {{ $row['total'] }} sessions</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($d['pendingMore'] > 0)
                            <p class="coachDashNote">+ {{ $d['pendingMore'] }} more on the Assessment page.</p>
                        @endif
                    @endif
                </article>

                {{-- Ready to graduate --}}
                <article class="coachDashCard" id="coachDashReady">
                    <div class="coachDashCardHead">
                        <h3 class="coachDashCardTitle">Ready to Graduate</h3>
                        <span class="coachDashPill {{ $kpis['ready'] > 0 ? 'is-pass' : 'is-muted' }}">{{ $kpis['ready'] }}</span>
                    </div>

                    @if (empty($d['ready']))
                        <p class="coachDashNote mt-0">No one is waiting to graduate.</p>
                    @else
                        <div class="coachDashList">
                            @foreach ($d['ready'] as $row)
                                <div class="coachDashRow">
                                    <div class="coachDashRowMain">
                                        <div class="coachDashRowTitle">{{ $row['name'] }}</div>
                                        <div class="coachDashRowSub">{{ $row['course'] }}</div>
                                    </div>
                                    <span class="coachDashPill {{ $row['cleared'] ? 'is-pass' : 'is-pending' }}">{{ $row['cleared'] ? 'Cleared' : 'Awaiting clearance' }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($d['readyMore'] > 0)
                            <p class="coachDashNote">+ {{ $d['readyMore'] }} more.</p>
                        @endif
                    @endif
                </article>

                {{-- Inactive students --}}
                <article class="coachDashCard" id="coachDashInactive">
                    <h3 class="coachDashCardTitle">Haven&rsquo;t Attended Lately</h3>

                    @if (empty($d['inactive']))
                        <p class="coachDashNote mt-0">Everyone has attended recently.</p>
                    @else
                        <div class="coachDashList">
                            @foreach ($d['inactive'] as $row)
                                <div class="coachDashRow">
                                    <div class="coachDashRowMain">
                                        <div class="coachDashRowTitle">{{ $row['name'] }}</div>
                                        <div class="coachDashRowSub">{{ $row['course'] }}</div>
                                    </div>
                                    <span class="coachDashPill is-failed">{{ $row['last'] ? 'Last ' . $row['last'] : 'No session yet' }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($d['inactiveMore'] > 0)
                            <p class="coachDashNote">+ {{ $d['inactiveMore'] }} more.</p>
                        @endif
                    @endif
                </article>

            </div>

            <div class="coachDashCol">

                {{-- Today's check-ins --}}
                <article class="coachDashCard" id="coachDashToday">
                    <div class="coachDashCardHead">
                        <h3 class="coachDashCardTitle">Today&rsquo;s Check-ins</h3>
                        <span class="coachDashPill is-muted">{{ $d['today']['total'] }} in</span>
                    </div>

                    @if (empty($d['today']['rows']))
                        <p class="coachDashNote mt-0">No one has checked in today.</p>
                    @else
                        <div class="coachDashList">
                            @foreach ($d['today']['rows'] as $row)
                                <div class="coachDashRow">
                                    <div class="coachDashRowMain">
                                        <div class="coachDashRowTitle">{{ $row['name'] }}</div>
                                        <div class="coachDashRowSub">{{ $row['course'] }}</div>
                                    </div>
                                    <span class="coachDashPill {{ $row['out'] ? 'is-muted' : 'is-pass' }}">{{ $row['in'] }} &ndash; {{ $row['out'] ?? 'in session' }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($d['today']['more'] > 0)
                            <p class="coachDashNote">+ {{ $d['today']['more'] }} more.</p>
                        @endif
                    @endif
                </article>

                {{-- Recent assessments --}}
                <article class="coachDashCard" id="coachDashAssessed">
                    <h3 class="coachDashCardTitle">Recently Assessed By You</h3>

                    @if (empty($d['recentAssessments']))
                        <p class="coachDashNote mt-0">You haven&rsquo;t saved any assessments yet.</p>
                    @else
                        <div class="coachDashList">
                            @foreach ($d['recentAssessments'] as $row)
                                <div class="coachDashRow">
                                    <div class="coachDashRowMain">
                                        <div class="coachDashRowTitle">{{ $row['name'] }}</div>
                                        <div class="coachDashRowSub">{{ $row['course'] }} &middot; {{ $row['passed'] }} / {{ $row['marked'] }} passed &middot; {{ $row['at'] }}</div>
                                    </div>
                                    <span class="coachDashPill {{ $row['ready'] ? 'is-pass' : 'is-muted' }}">{{ $row['ready'] ? 'Ready' : 'In progress' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>

                {{-- Athlete records (only for coaches who handle athletes) --}}
                @if ($d['athletes'] !== null)
                    <article class="coachDashCard" id="coachDashAthletes">
                        <div class="coachDashCardHead">
                            <h3 class="coachDashCardTitle">Athlete Times You Recorded</h3>
                            <span class="coachDashPill is-info">{{ $d['athletes']['last30'] }} in 30 days</span>
                        </div>

                        @if (empty($d['athletes']['rows']))
                            <p class="coachDashNote mt-0">No times recorded yet.</p>
                        @else
                            <div class="coachDashList">
                                @foreach ($d['athletes']['rows'] as $row)
                                    <div class="coachDashRow">
                                        <div class="coachDashRowMain">
                                            <div class="coachDashRowTitle">{{ $row['name'] }}</div>
                                            <div class="coachDashRowSub">{{ $row['event'] }} &middot; {{ $row['on'] }}</div>
                                        </div>
                                        <span class="coachDashRowAmount">{{ $row['time'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @endif

            </div>

        </div>

    @endif

</section>