@extends('layouts.toast')

@php
    use App\Http\Controllers\CoachController as C;

    // Self-contained like courses.blade.php: loads its own data so it can be
    // rendered by name from role_menus.php without a dedicated controller.
    // Real data: ACTIVE enrollments (not graduated yet, account status Active).
    $enrollments = \DB::table('tbl_enrollments as e')
        ->join(C::USERS_TABLE . ' as u', 'u.' . C::USER_PK, '=', 'e.user_id')
        ->join('tbl_user_profile as p', 'p.user_id', '=', 'e.user_id')
        ->join(C::STATUS_TABLE . ' as st', 'st.' . C::STATUS_ID_COL, '=', 'u.' . C::STATUS_COL)
        ->leftJoin(C::COURSES_TABLE . ' as c', 'c.' . C::COURSE_ID_COL, '=', 'e.course_id')
        ->whereNull('e.graduation_at')
        ->where('st.' . C::STATUS_NAME_COL, 'Active')
        ->orderBy('p.last_name')
        ->orderBy('p.first_name')
        ->get([
            'e.enrollment_id',
            'e.enrollment_at',
            'e.scheduled_time',
            'c.' . C::COURSE_ID_COL . ' as course_id',
            'c.' . C::COURSE_NAME_COL . ' as course_name',
            'c.course_session_count',
            'p.first_name', 'p.middle_name', 'p.last_name', 'p.name_suffix',
        ]);

    // Sessions attended = attendance rows with a Time In, per enrollment
    // (same rule as the Attendance page).
    $attendance = $enrollments->isEmpty()
        ? collect()
        : \DB::table('tbl_attendances')
            ->whereIn('enrollment_id', $enrollments->pluck('enrollment_id'))
            ->whereNotNull('time_in')
            ->groupBy('enrollment_id')
            ->selectRaw('enrollment_id, COUNT(*) as sessions, MAX(attendance_at) as last_session')
            ->get()
            ->keyBy('enrollment_id');

    $graduationStudents = $enrollments->map(function ($e) use ($attendance) {
        $given = trim($e->first_name . ' ' . ($e->middle_name ?? ''));
        $name = trim($e->last_name . ', ' . $given . ($e->name_suffix ? ' ' . $e->name_suffix : ''));
        $row = $attendance->get($e->enrollment_id);

        return [
            'id'          => (int) $e->enrollment_id,
            'name'        => $name,
            'courseId'    => $e->course_id !== null ? (int) $e->course_id : null,
            'course'      => $e->course_name ?? 'No course',
            'schedule'    => $e->scheduled_time ?: null,
            'enrolledAt'  => $e->enrollment_at ? substr((string) $e->enrollment_at, 0, 10) : null,
            'done'        => $row ? (int) $row->sessions : 0,
            'total'       => max(0, (int) $e->course_session_count),
            'lastSession' => $row && $row->last_session ? substr((string) $row->last_session, 0, 10) : null,
        ];
    })->values();

    $graduationCourses = $graduationStudents
        ->filter(fn ($s) => $s['courseId'] !== null)
        ->unique('courseId')
        ->map(fn ($s) => ['id' => $s['courseId'], 'name' => $s['course']])
        ->sortBy('name')
        ->values();

    // SAMPLE DATA ONLY (placeholder display for the Ready / Graduates tabs
    // until they are connected to real records).
    $sampleReady = [
        ['name' => 'Santos, Maria P.',   'level' => 'Beginner',     'sessions' => '8 / 8 sessions',   'last_session' => 'Oct 09, 2026'],
        ['name' => 'Reyes, Carlo D.',    'level' => 'Intermediate', 'sessions' => '12 / 12 sessions', 'last_session' => 'Oct 10, 2026'],
    ];
    $sampleGraduated = [
        ['name' => 'Cruz, Angela M.',    'level' => 'Beginner',     'sessions' => '8 / 8 sessions',   'certificate' => 'CERT-2026-0001'],
        ['name' => 'Garcia, Paolo R.',   'level' => 'Advanced',     'sessions' => '16 / 16 sessions', 'certificate' => 'CERT-2026-0002'],
    ];
@endphp

<style>
.graduationContainer {
    width: 100%;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.graduationHeader { flex: 0 0 auto; margin-bottom: 1rem; }

.graduationStats {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
    margin-bottom: 1rem;
}

.graduationStat {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .85rem 1.1rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

.graduationStatLabel { margin: 0 0 .2rem; font-size: .72rem; font-weight: 600; text-transform: uppercase; color: #667085; }
.graduationStatValue { margin: 0; font-size: 1.45rem; font-weight: 700; line-height: 1; }

.graduationStatIcon {
    width: 2rem;
    height: 2rem;
    flex: 0 0 2rem;
    display: grid;
    place-items: center;
    border-radius: 9px;
    font-size: 1rem;
}
.graduationStatIcon.blue  { background: #e7f1ff; color: #0d6efd; }
.graduationStatIcon.green { background: #e6f7ec; color: #1a7f3c; }
.graduationStatIcon.amber { background: #fff4e0; color: #b26a00; }
.graduationStatIcon.red   { background: #fdebec; color: #c22b3a; }

/* Real-data view (Active tab) and the sample panels share the space under the tabs. */
.graduationRealView, .graduationSample { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
.graduationRealView[hidden], .graduationSample[hidden] { display: none !important; }
.graduationSample { overflow-y: auto; }

.graduationTabs { flex: 0 0 auto; margin-bottom: 1rem; }
.graduationTabs .btn-tab { min-width: 150px; }

.graduationToolbar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

.graduationSearchContainer { position: relative; width: min(300px, 100%); flex-shrink: 0; }

.graduationSearchIcon {
    position: absolute;
    top: 50%;
    left: .75rem;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.graduationSearchInput { height: 40px; box-sizing: border-box; border-radius: 20px; padding-left: 2.25rem; }

/* Sizes come from the shared dropdown variables (our_theme.css). */
.graduationFilter { --dd-min-width: 190px; }

.graduationResultCount { margin-left: auto; font-size: .8125rem; color: #6c757d; white-space: nowrap; }

.graduationList {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    display: grid;
    gap: .65rem;
    align-content: start;
    padding-right: .25rem;
}

.graduationList::-webkit-scrollbar { width: 8px; }
.graduationList::-webkit-scrollbar-track { background: #f1f3f5; border-radius: 10px; }
.graduationList::-webkit-scrollbar-thumb { background: #c9ced3; border-radius: 10px; }

.graduationCard {
    display: grid;
    grid-template-columns: minmax(0, 1.3fr) minmax(0, 1.4fr) auto;
    align-items: center;
    gap: 1.25rem;
    padding: .85rem 1.1rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

.graduationCard.isReady { background: #fbfff6; border-color: #cfe9d3; }

.graduationName { font-size: .95rem; font-weight: 650; color: #1f2937; }

.graduationMeta { display: flex; flex-wrap: wrap; gap: .15rem .9rem; margin-top: .2rem; font-size: .78rem; color: #667085; }
.graduationMeta i { margin-right: .25rem; }
.graduationCourse { font-weight: 700; text-transform: uppercase; font-style: italic; }

.graduationProgressTop { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .3rem; font-size: .78rem; }
.graduationProgressTop strong { font-weight: 700; }
.graduationProgressTop span { color: #667085; }

.graduationBar { height: 7px; background: #e7ebf0; border-radius: 99px; overflow: hidden; }
.graduationBarFill { height: 100%; background: var(--fbss-blue, #0d6efd); border-radius: 99px; }
.graduationCard.isReady .graduationBarFill { background: #1a7f3c; }

.graduationLast { margin-top: .35rem; font-size: .75rem; color: #667085; }
.graduationLast.isStale { color: #b26a00; font-weight: 600; }

.graduationBadge { display: inline-block; padding: .2rem .65rem; border-radius: 12px; font-size: .75rem; font-weight: 600; white-space: nowrap; }
.graduationBadge.is-ready { background: #e6f7ec; color: #1a7f3c; }
.graduationBadge.is-almost { background: #fff4e0; color: #b26a00; }
.graduationBadge.is-inprogress { background: #e7f1ff; color: #0b5ed7; }
.graduationBadge.is-notstarted { background: #f1f3f5; color: #667085; }

.graduationEmpty {
    padding: 2rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    color: #6c757d;
    text-align: center;
}

@media (max-width: 991.98px) {
    .graduationStats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .graduationCard { grid-template-columns: 1fr; gap: .6rem; }
}

@media (max-width: 575.98px) {
    .graduationContainer { padding: 1rem; }
    .graduationFilter { --dd-min-width: 140px; flex: 1 1 140px; }
    .graduationResultCount { margin-left: 0; width: 100%; }
}

/* ---------- Sample panels (Ready / Graduates) — old design ---------- */


    .graduation-panel {
        max-width: none;
        margin: 0;
        padding: 1rem 1.2rem 1.25rem;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .05);
    }

    .graduation-panel[hidden] { display: none !important; }

    .graduation-panel-heading {
        display: flex;
        align-items: center;
        gap: .55rem;
        margin: 0 0 1rem;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .graduation-panel-heading i { color: var(--fbss-blue); font-size: 1.2rem; }
    .graduation-panel-heading .group-count { color: #667085; font-size: .9rem; font-weight: 500; }

    .graduation-student-list { display: grid; gap: .65rem; padding-left: 1.2rem; }
    .graduation-empty { color: #667085; text-align: center; }

    .graduation-student {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        min-height: 54px;
        padding: .55rem .8rem;
        background: #e1f6ff;
        border: 1px solid rgba(16, 24, 40, .06);
        border-radius: 8px;
        box-shadow: 0 1px 1px rgba(16, 24, 40, .12);
    }

    .graduation-student--ready { background: #fbffe5; }
    .graduation-student--graduated { background: #e3ffe6; }
    .graduation-student-info { min-width: 0; }
    .graduation-student-name { font-size: .92rem; font-weight: 650; }
    .graduation-student-meta { color: #667085; font-size: .8rem; }
    .graduation-student-meta strong { font-weight: 700; font-style: italic; text-transform: uppercase; }
    .graduation-student .btn { min-width: 76px; }

    .graduation-ready-actions {
        display: grid;
        grid-template-columns: minmax(150px, 1fr) 92px;
        align-items: center;
        gap: .2rem .8rem;
        flex-shrink: 0;
    }

    .graduation-ready-session {
        grid-column: 1;
        color: #667085;
        font-size: .75rem;
        text-align: right;
        white-space: nowrap;
    }

    .graduation-ready-upload {
        grid-column: 1;
        color: var(--fbss-blue);
        cursor: pointer;
        font-size: .78rem;
        text-align: right;
        text-decoration: none;
    }

    .graduation-ready-upload:hover { color: var(--fbss-blue-hover); text-decoration: underline; }
    .graduation-ready-upload input { position: absolute; width: 1px; height: 1px; opacity: 0; }
    .graduation-ready-file { grid-column: 1 / -1; color: #667085; font-size: .68rem; text-align: right; }
    .graduation-ready-graduate { grid-column: 2; grid-row: 1; }
    .graduation-ready-view { grid-column: 2; grid-row: 2; }

    .graduation-graduate-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 1rem;
        flex-shrink: 0;
    }

    .graduation-certificate-number { min-width: 110px; font-size: .75rem; font-weight: 600; text-align: right; white-space: nowrap; }

    .assessment-modal .modal-dialog { max-width: 410px; }
    .assessment-modal .modal-content { border: 0; border-radius: 12px; box-shadow: 0 18px 50px rgba(0, 0, 0, .18); }
    .assessment-modal .modal-header { padding: .9rem 1rem .5rem; border: 0; }
    .assessment-modal .modal-body { padding: 0 1rem .7rem; }
    .assessment-modal .modal-footer { padding: .6rem 1rem 1rem; border: 0; }
    .assessment-student-name { margin: 0; font-size: 1.05rem; font-weight: 700; }
    .assessment-title { margin: .15rem 0 .5rem; font-size: .8rem; font-weight: 600; }
    .assessment-payment {
        padding: .2rem .5rem;
        color: var(--fbss-danger);
        background: var(--fbss-danger-tint);
        border-radius: 999px;
        font-size: .65rem;
        font-weight: 700;
    }
    .assessment-payment--paid { color: var(--fbss-success); background: var(--fbss-success-tint); }
    .assessment-progress-summary { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .25rem; font-size: .7rem; }
    .assessment-progress-count { font-weight: 600; }
    .assessment-progress-percent { color: #667085; }
    .assessment-progress { height: 5px; background: #e7ebf0; border-radius: 99px; }
    .assessment-progress .progress-bar { background: var(--fbss-blue); border-radius: 99px; }
    .assessment-categories { display: grid; gap: .35rem; margin-top: 1rem; }
    .assessment-category { border-radius: 7px; overflow: hidden; }
    .assessment-category-toggle {
        display: flex;
        align-items: center;
        width: 100%;
        gap: .45rem;
        padding: .45rem .55rem;
        border: 0;
        background: #f4f7fa;
        color: #172033;
        text-align: left;
        font-size: .72rem;
        font-weight: 700;
    }
    .assessment-category-toggle:hover { background: #eaf1f7; }
    .assessment-category-toggle .bi-chevron-down { transition: transform .15s ease; }
    .assessment-category-toggle[aria-expanded="true"] .bi-chevron-down { transform: rotate(180deg); }
    .assessment-category-count { margin-left: auto; color: #667085; font-size: .65rem; font-weight: 500; white-space: nowrap; }
    .assessment-skills { padding: .25rem 0 .15rem; }
    .assessment-skill { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .28rem 0; font-size: .68rem; }
    .assessment-result { padding: .08rem .38rem; border-radius: 3px; font-size: .57rem; font-weight: 700; }
    .assessment-result--passed { color: #087443; background: #d8f6e6; }
    .assessment-result--failed { color: #b42318; background: #fde4e2; }
    .assessment-balance { color: var(--fbss-danger); font-size: .85rem; font-style: italic; }
    .assessment-back { min-width: 76px; }

    @media (max-width: 640px) {
        .graduation-panel { padding: .9rem .75rem 1rem; }
        .graduation-student-list { padding-left: .15rem; }
        .graduation-student { align-items: flex-start; flex-direction: column; }
        .graduation-ready-actions { grid-template-columns: minmax(0, 1fr) 76px; gap: .25rem .5rem; width: 100%; }
        .graduation-ready-session, .graduation-ready-upload, .graduation-ready-file { white-space: normal; }
        .graduation-ready-session, .graduation-ready-upload { text-align: left; }
        .graduation-graduate-actions { gap: .5rem; }
        .graduation-ready-actions, .graduation-graduate-actions { width: 100%; }
        .graduation-graduate-actions { justify-content: space-between; }
        .graduation-certificate-number { min-width: 0; white-space: normal; }
    }
</style>

<section class="graduationContainer" id="graduationContainer" aria-labelledby="graduationTitle">

    <header class="graduationHeader">
        <h2 class="mb-1" id="graduationTitle">Graduation</h2>
        <p class="text-muted mb-0">Active students and how far along they are, based on the sessions logged in attendance.</p>
    </header>

    <nav class="btn-tab-group graduationTabs" id="graduationTabs" role="tablist" aria-label="Graduation student groups">
        <button class="btn btn-outline-primary btn-tab active" type="button" role="tab" data-graduation-tab="active" aria-selected="true">Active Students <span data-tab-count="active"></span></button>
        <button class="btn btn-outline-primary btn-tab" type="button" role="tab" data-graduation-tab="ready" aria-selected="false">Ready for Assessment <span data-tab-count="ready"></span></button>
        <button class="btn btn-outline-primary btn-tab" type="button" role="tab" data-graduation-tab="graduated" aria-selected="false">Graduates <span data-tab-count="graduated"></span></button>
    </nav>

    <div class="graduationRealView" id="graduationRealView">

    <div class="graduationStats" aria-label="Active student summary">
        <article class="graduationStat">
            <div><p class="graduationStatLabel">Active studentss</p><p class="graduationStatValue" id="graduationStatActive">0</p></div>
            <span class="graduationStatIcon blue"><i class="bi bi-people"></i></span>
        </article>
        <article class="graduationStat">
            <div><p class="graduationStatLabel">Ready for assessment</p><p class="graduationStatValue" id="graduationStatReady">0</p></div>
            <span class="graduationStatIcon green"><i class="bi bi-award"></i></span>
        </article>
        <article class="graduationStat">
            <div><p class="graduationStatLabel">Almost done (2 or fewer left)</p><p class="graduationStatValue" id="graduationStatAlmost">0</p></div>
            <span class="graduationStatIcon amber"><i class="bi bi-hourglass-split"></i></span>
        </article>
        <article class="graduationStat">
            <div><p class="graduationStatLabel">No session in 14+ days</p><p class="graduationStatValue" id="graduationStatStale">0</p></div>
            <span class="graduationStatIcon red"><i class="bi bi-exclamation-circle"></i></span>
        </article>
    </div>

    <div class="graduationToolbar">
        <div class="graduationSearchContainer">
            <i class="bi bi-search graduationSearchIcon" aria-hidden="true"></i>
            <input class="form-control graduationSearchInput" id="graduationSearch" type="search"
                   placeholder="Search name, course or schedule" autocomplete="off" aria-label="Search students">
        </div>

        <div class="registerDropdown graduationFilter" id="graduationCourseDropdown" data-fbss-dropdown>
            <button class="registerDropdownToggle" id="graduationCourseToggle" type="button" aria-expanded="false" aria-controls="graduationCourseOptions" aria-label="Filter by course">
                <span>All Courses</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>
            <div class="registerDropdownOptions" id="graduationCourseOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="">All Courses</button>
                @foreach ($graduationCourses as $course)
                    <button class="registerDropdownOption" type="button" data-value="{{ $course['id'] }}">{{ $course['name'] }}</button>
                @endforeach
            </div>
            <input id="graduationCourseInput" type="hidden" value="">
        </div>

        <div class="registerDropdown graduationFilter" id="graduationProgressDropdown" data-fbss-dropdown>
            <button class="registerDropdownToggle" id="graduationProgressToggle" type="button" aria-expanded="false" aria-controls="graduationProgressOptions" aria-label="Filter by progress">
                <span>All Progress</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>
            <div class="registerDropdownOptions" id="graduationProgressOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="">All Progress</button>
                <button class="registerDropdownOption" type="button" data-value="ready">Ready for assessment</button>
                <button class="registerDropdownOption" type="button" data-value="almost">Almost done</button>
                <button class="registerDropdownOption" type="button" data-value="inprogress">In progress</button>
                <button class="registerDropdownOption" type="button" data-value="notstarted">Not started</button>
            </div>
            <input id="graduationProgressInput" type="hidden" value="">
        </div>

        <div class="registerDropdown graduationFilter" id="graduationActivityDropdown" data-fbss-dropdown>
            <button class="registerDropdownToggle" id="graduationActivityToggle" type="button" aria-expanded="false" aria-controls="graduationActivityOptions" aria-label="Filter by recent activity">
                <span>Any Activity</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>
            <div class="registerDropdownOptions" id="graduationActivityOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="">Any Activity</button>
                <button class="registerDropdownOption" type="button" data-value="recent">Attended in last 14 days</button>
                <button class="registerDropdownOption" type="button" data-value="stale">No session in 14+ days</button>
            </div>
            <input id="graduationActivityInput" type="hidden" value="">
        </div>

        <div class="registerDropdown graduationFilter" id="graduationSortDropdown" data-fbss-dropdown>
            <button class="registerDropdownToggle" id="graduationSortToggle" type="button" aria-expanded="false" aria-controls="graduationSortOptions" aria-label="Sort students">
                <span>Sort: Name (A-Z)</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>
            <div class="registerDropdownOptions" id="graduationSortOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="name">Sort: Name (A-Z)</button>
                <button class="registerDropdownOption" type="button" data-value="mostDone">Sort: Most sessions done</button>
                <button class="registerDropdownOption" type="button" data-value="fewestLeft">Sort: Fewest sessions left</button>
                <button class="registerDropdownOption" type="button" data-value="longestIdle">Sort: Longest since last session</button>
            </div>
            <input id="graduationSortInput" type="hidden" value="">
        </div>

        <span class="graduationResultCount" id="graduationResultCount"></span>
    </div>

    <div class="graduationList" id="graduationList"></div>

    </div>

    {{-- ===== SAMPLE DISPLAY (not real data yet) ===== --}}
    <div class="graduationSample" id="graduationSampleReady" hidden>
        <section class="graduation-panel">
            <h2 class="graduation-panel-heading">
                <i class="bi bi-patch-check" aria-hidden="true"></i>
                Ready for Assessment <span class="group-count">({{ count($sampleReady) }})</span>
                <span class="badge text-bg-secondary ms-auto">Sample data</span>
            </h2>
            <div class="graduation-student-list">
                @foreach ($sampleReady as $student)
                    <article class="graduation-student graduation-student--ready">
                        <div class="graduation-student-info">
                            <div class="graduation-student-name">{{ $student['name'] }}</div>
                            <div class="graduation-student-meta"><strong>{{ $student['level'] }}</strong><span class="mx-2">&middot;</span><strong>{{ $student['sessions'] }}</strong></div>
                        </div>
                        <div class="graduation-ready-actions">
                            <span class="graduation-ready-session">LAST SESSION: {{ $student['last_session'] }}</span>
                            <button type="button" class="btn btn-primary btn-sm graduation-ready-graduate" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-student="{{ $student['name'] }}" data-assessment-type="ready" data-assessment-mode="graduate">
                                <i class="bi bi-award" aria-hidden="true"></i> Graduate
                            </button>
                            <label class="graduation-ready-upload" for="certificate-{{ $loop->index }}">
                                <i class="bi bi-upload" aria-hidden="true"></i> Upload Certificate
                                <input type="file" id="certificate-{{ $loop->index }}" accept=".pdf,image/*" data-certificate-input>
                            </label>
                            <button type="button" class="btn btn-primary btn-sm graduation-ready-view" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-student="{{ $student['name'] }}" data-assessment-type="ready" data-assessment-mode="view">View</button>
                            <span class="graduation-ready-file" data-certificate-name></span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    <div class="graduationSample" id="graduationSampleGraduated" hidden>
        <section class="graduation-panel">
            <h2 class="graduation-panel-heading">
                <i class="bi bi-mortarboard" aria-hidden="true"></i>
                Graduates <span class="group-count">({{ count($sampleGraduated) }})</span>
                <span class="badge text-bg-secondary ms-auto">Sample data</span>
            </h2>
            <div class="graduation-student-list">
                @foreach ($sampleGraduated as $student)
                    <article class="graduation-student graduation-student--graduated">
                        <div class="graduation-student-info">
                            <div class="graduation-student-name">{{ $student['name'] }}</div>
                            <div class="graduation-student-meta"><strong>{{ $student['level'] }}</strong><span class="mx-2">&middot;</span><strong>{{ $student['sessions'] }}</strong></div>
                        </div>
                        <div class="graduation-graduate-actions">
                            <span class="graduation-certificate-number">{{ $student['certificate'] }}</span>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-student="{{ $student['name'] }}" data-assessment-type="graduated" data-assessment-mode="view">View Details</button>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

</section>

<div class="alert alert-info graduation-feedback d-none" id="graduationFeedback" role="status" style="position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:2000;"></div>

<div class="modal fade assessment-modal" id="assessmentModal" tabindex="-1" aria-labelledby="assessmentStudent" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="assessment-student-name" id="assessmentStudent">Maria P. Santos</h2>
                    <div class="text-muted small" id="assessmentLevel">Core Skills Assessment List</div>
                </div>
                <span class="assessment-payment ms-auto me-2">Unpaid</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="assessment-progress-summary">
                    <span class="assessment-progress-count"><span id="assessmentPassed">4</span> / <span id="assessmentTotal">18</span> passed</span>
                    <span class="assessment-progress-percent" id="assessmentPercent">22.7%</span>
                </div>
                <div class="progress assessment-progress" role="progressbar" aria-label="Skills passed" aria-valuenow="22.7" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: 22.7%"></div>
                </div>

                <div class="assessment-categories">
                    <div class="assessment-category">
                        <button type="button" class="assessment-category-toggle" data-bs-toggle="collapse" data-bs-target="#skills-water" aria-expanded="true" aria-controls="skills-water">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i><span>Water Comfort &amp; Safety</span><span class="assessment-category-count">2 / 4 passed</span>
                        </button>
                        <div class="collapse show" id="skills-water">
                            <div class="assessment-skills">
                                <div class="assessment-skill"><span>Entering and exiting the pool safely</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Submerging face underwater comfortably</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Floating on front (face in water)</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                                <div class="assessment-skill"><span>Floating on back (supine position)</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="assessment-category">
                        <button type="button" class="assessment-category-toggle" data-bs-toggle="collapse" data-bs-target="#skills-breathing" aria-expanded="true" aria-controls="skills-breathing">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i><span>Breathing Skills</span><span class="assessment-category-count">2 / 3 passed</span>
                        </button>
                        <div class="collapse show" id="skills-breathing">
                            <div class="assessment-skills">
                                <div class="assessment-skill"><span>Exhaling underwater (bubbles)</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Inhaling above water</span><span class="assessment-result assessment-result--passed">Passed</span></div>
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="assessment-category">
                        <button type="button" class="assessment-category-toggle" data-bs-toggle="collapse" data-bs-target="#skills-arms" aria-expanded="true" aria-controls="skills-arms">
                            <i class="bi bi-chevron-down" aria-hidden="true"></i><span>Arm Pulls &amp; Kicking Fundamentals</span><span class="assessment-category-count">0 / 3 passed</span>
                        </button>
                        <div class="collapse show" id="skills-arms">
                            <div class="assessment-skills">
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                                <div class="assessment-skill"><span>Rhythmic breathing while swimming</span><span class="assessment-result assessment-result--failed">Failed</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <strong class="assessment-balance">Balance: ₱3,000</strong>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm assessment-back" data-bs-dismiss="modal">Back</button>
                    <button type="button" class="btn btn-primary btn-sm d-none" id="confirmGraduationButton" data-bs-dismiss="modal">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    const STALE_DAYS = 14;
    const ALMOST_LEFT = 2;

    const students = @json($graduationStudents);
    const SAMPLE_COUNTS = { ready: {{ count($sampleReady) }}, graduated: {{ count($sampleGraduated) }} };
    let activeTab = 'active';

    const $ = id => document.getElementById(id);

    const el = {
        search: $('graduationSearch'),
        course: $('graduationCourseInput'),
        progress: $('graduationProgressInput'),
        activity: $('graduationActivityInput'),
        sort: $('graduationSortInput'),
        list: $('graduationList'),
        count: $('graduationResultCount'),
        tabs: $('graduationTabs')
    };

    const PROGRESS_LABELS = {
        ready: 'Ready for assessment',
        almost: 'Almost done',
        inprogress: 'In progress',
        notstarted: 'Not started'
    };

    /* =========================================================
       HELPERS
    ========================================================= */

    function make(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function icon(name) {
        const i = make('i', `bi ${name}`);
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    // 'YYYY-MM-DD' -> local-midnight Date (avoids the UTC off-by-one of new Date('YYYY-MM-DD')).
    function parseDay(iso) {
        if (!iso) return null;
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function daysSince(iso) {
        const day = parseDay(iso);
        if (!day) return null;
        const t = new Date();
        const today = new Date(t.getFullYear(), t.getMonth(), t.getDate());
        return Math.round((today - day) / 86400000);
    }

    function formatDay(iso) {
        const day = parseDay(iso);
        return day ? day.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '';
    }

    function progressKey(s) {
        if (s.total > 0 && s.done >= s.total) return 'ready';
        if (s.done === 0) return 'notstarted';
        if (s.total > 0 && s.total - s.done <= ALMOST_LEFT) return 'almost';
        return 'inprogress';
    }

    function isStale(s) {
        if (progressKey(s) === 'ready') return false;
        const idle = daysSince(s.lastSession);
        // Never attended: only counts as stale once enrolled for 14+ days.
        return idle === null ? (daysSince(s.enrolledAt) ?? 0) >= STALE_DAYS : idle >= STALE_DAYS;
    }

    function lastSessionText(s) {
        const idle = daysSince(s.lastSession);
        if (idle === null) return 'No sessions logged yet';
        const when = idle === 0 ? 'today' : idle === 1 ? 'yesterday' : `${idle} days ago`;
        return `Last session: ${when} (${formatDay(s.lastSession)})`;
    }

    /* =========================================================
       SUMMARY CARDS (always the whole active list, not the filter)
    ========================================================= */

    function renderStats() {
        $('graduationStatActive').textContent = students.length;
        $('graduationStatReady').textContent = students.filter(s => progressKey(s) === 'ready').length;
        $('graduationStatAlmost').textContent = students.filter(s => progressKey(s) === 'almost').length;
        $('graduationStatStale').textContent = students.filter(isStale).length;
    }

    /* =========================================================
       FILTER + SORT
    ========================================================= */

    // Real data: every active student (Ready / Graduates tabs are sample panels for now).
    function tabStudents() {
        return students;
    }

    function visibleStudents() {
        const term = el.search.value.trim().toLowerCase();
        const courseId = el.course.value;
        const progress = el.progress.value;
        const activity = el.activity.value;

        const rows = tabStudents().filter(s => {
            if (term && ![s.name, s.course, s.schedule || ''].join(' ').toLowerCase().includes(term)) return false;
            if (courseId && String(s.courseId) !== courseId) return false;
            if (progress && progressKey(s) !== progress) return false;
            if (activity === 'stale' && !isStale(s)) return false;
            if (activity === 'recent') {
                const idle = daysSince(s.lastSession);
                if (idle === null || idle >= STALE_DAYS) return false;
            }
            return true;
        });

        const left = s => (s.total > 0 ? s.total - s.done : Number.MAX_SAFE_INTEGER);
        // Never attended counts as the longest idle.
        const idle = s => daysSince(s.lastSession) ?? Number.MAX_SAFE_INTEGER;

        const sorters = {
            name: (a, b) => a.name.localeCompare(b.name),
            mostDone: (a, b) => b.done - a.done || a.name.localeCompare(b.name),
            fewestLeft: (a, b) => left(a) - left(b) || a.name.localeCompare(b.name),
            longestIdle: (a, b) => idle(b) - idle(a) || a.name.localeCompare(b.name)
        };

        return rows.sort(sorters[el.sort.value] || sorters.name);
    }

    /* =========================================================
       RENDER
    ========================================================= */

    function createCard(s) {
        const key = progressKey(s);
        const card = make('article', `graduationCard${key === 'ready' ? ' isReady' : ''}`);
        card.id = `graduationCard-${s.id}`;

        // Who + course info
        const info = make('div');
        info.append(make('div', 'graduationName', s.name));

        const meta = make('div', 'graduationMeta');
        const course = make('span');
        course.append(icon('bi-mortarboard'), make('span', 'graduationCourse', s.course));
        meta.append(course);

        if (s.schedule) {
            const schedule = make('span');
            schedule.append(icon('bi-clock'), document.createTextNode(s.schedule));
            meta.append(schedule);
        }

        if (s.enrolledAt) {
            const enrolled = make('span');
            enrolled.append(icon('bi-calendar-event'), document.createTextNode(`Enrolled ${formatDay(s.enrolledAt)}`));
            meta.append(enrolled);
        }

        info.append(meta);

        // Sessions from attendance
        const progress = make('div');
        const top = make('div', 'graduationProgressTop');
        top.append(make('strong', null, s.total > 0 ? `${s.done} / ${s.total} sessions` : `${s.done} sessions`));

        if (s.total > 0) {
            const remaining = Math.max(s.total - s.done, 0);
            top.append(make('span', null, remaining === 0 ? 'All sessions completed' : `${remaining} left`));
        }

        progress.append(top);

        const percent = s.total > 0 ? Math.min(100, Math.round((s.done / s.total) * 100)) : 0;
        const bar = make('div', 'graduationBar');
        bar.setAttribute('role', 'progressbar');
        bar.setAttribute('aria-valuemin', '0');
        bar.setAttribute('aria-valuemax', '100');
        bar.setAttribute('aria-valuenow', String(percent));
        const fill = make('div', 'graduationBarFill');
        fill.style.width = `${percent}%`;
        bar.append(fill);
        progress.append(bar);

        progress.append(make('div', `graduationLast${isStale(s) ? ' isStale' : ''}`, lastSessionText(s)));

        card.append(info, progress, make('span', `graduationBadge is-${key}`, PROGRESS_LABELS[key]));
        return card;
    }

    function render() {
        const rows = visibleStudents();

        el.list.replaceChildren();
        const tabTotal = tabStudents().length;
        el.count.textContent = `Showing ${rows.length} of ${tabTotal}`;

        if (!rows.length) {
            el.list.append(make('p', 'graduationEmpty',
                tabTotal ? 'No students match your search or filters.' : 'There are no active students yet.'));
            return;
        }

        rows.forEach(s => el.list.append(createCard(s)));
    }

    // Fired by the shared dropdown (assets/js/fbss-dropdown.js) when an option is picked.
    document.querySelector('.graduationToolbar').addEventListener('dropdown:change', render);
    el.search.addEventListener('input', render);

    function updateTabs() {
        el.tabs.querySelectorAll('[data-graduation-tab]').forEach(button => {
            const on = button.dataset.graduationTab === activeTab;
            button.classList.toggle('active', on);
            button.setAttribute('aria-selected', String(on));
        });

        const counts = { ready: SAMPLE_COUNTS.ready, active: students.length, graduated: SAMPLE_COUNTS.graduated };
        el.tabs.querySelectorAll('[data-tab-count]').forEach(span => {
            span.textContent = `(${counts[span.dataset.tabCount]})`;
        });

        $('graduationRealView').hidden = activeTab !== 'active';
        $('graduationSampleReady').hidden = activeTab !== 'ready';
        $('graduationSampleGraduated').hidden = activeTab !== 'graduated';
    }

    el.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-graduation-tab]');
        if (!button || button.dataset.graduationTab === activeTab) return;

        activeTab = button.dataset.graduationTab;
        updateTabs();
    });

    renderStats();
    updateTabs();
    render();

    /* =========================================================
       SAMPLE ASSESSMENT POPUP (placeholder data, Ready / Graduates tabs)
    ========================================================= */
    (function () {
        const assessmentModal = document.getElementById('assessmentModal');
        const paymentBadge = assessmentModal.querySelector('.assessment-payment');
        const passedCount = document.getElementById('assessmentPassed');
        const totalCount = document.getElementById('assessmentTotal');
        const percentage = document.getElementById('assessmentPercent');
        const progressBar = assessmentModal.querySelector('.assessment-progress .progress-bar');
        const progressTrack = assessmentModal.querySelector('.assessment-progress');
        const categoryCounts = assessmentModal.querySelectorAll('.assessment-category-count');
        const skillResults = assessmentModal.querySelectorAll('.assessment-result');
        const categoryToggles = assessmentModal.querySelectorAll('.assessment-category-toggle');
        const categoryPanels = assessmentModal.querySelectorAll('.assessment-category .collapse');
        const balance = assessmentModal.querySelector('.assessment-balance');
        const confirmGraduationButton = document.getElementById('confirmGraduationButton');
        const graduationFeedback = document.getElementById('graduationFeedback');

        const defaultAssessment = {
            payment: paymentBadge.textContent.trim(),
            passed: passedCount.textContent,
            total: totalCount.textContent,
            percentage: percentage.textContent,
            balance: balance.textContent,
            categoryCounts: Array.from(categoryCounts, (count) => count.textContent),
            skillResults: Array.from(skillResults, (result) => result.textContent),
        };

        assessmentModal.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget;
            const displayName = trigger.dataset.student || 'Student';
            const assessmentType = trigger.dataset.assessmentType || 'active';
            const assessmentMode = trigger.dataset.assessmentMode || 'view';
            const nameParts = displayName.split(', ');
            const modalName = nameParts.length === 2 ? `${nameParts[1]} ${nameParts[0]}` : displayName;

            document.getElementById('assessmentStudent').textContent = modalName;
            document.getElementById('assessmentLevel').textContent = 'Core Skills Assessment List';
            confirmGraduationButton.classList.toggle('d-none', !(assessmentType === 'ready' && assessmentMode === 'graduate'));

            if (assessmentType === 'ready' || assessmentType === 'graduated') {
                paymentBadge.textContent = 'Paid';
                paymentBadge.classList.add('assessment-payment--paid');
                passedCount.textContent = '10';
                totalCount.textContent = '10';
                percentage.textContent = '100%';
                progressBar.style.width = '100%';
                progressTrack.setAttribute('aria-valuenow', '100');
                balance.textContent = 'Balance: ₱0';
                ['4 / 4 passed', '3 / 3 passed', '3 / 3 passed'].forEach((count, index) => {
                    categoryCounts[index].textContent = count;
                });
                skillResults.forEach((result) => {
                    result.textContent = 'Passed';
                    result.classList.remove('assessment-result--failed');
                    result.classList.add('assessment-result--passed');
                });
                categoryPanels.forEach((panel, index) => {
                    const isExpanded = assessmentType === 'graduated' || assessmentMode === 'view';
                    panel.classList.toggle('show', isExpanded);
                    categoryToggles[index].setAttribute('aria-expanded', String(isExpanded));
                    categoryToggles[index].querySelector('i').className = `bi ${isExpanded ? 'bi-chevron-down' : 'bi-chevron-right'}`;
                });
                return;
            }

            paymentBadge.textContent = defaultAssessment.payment;
            paymentBadge.classList.remove('assessment-payment--paid');
            passedCount.textContent = defaultAssessment.passed;
            totalCount.textContent = defaultAssessment.total;
            percentage.textContent = defaultAssessment.percentage;
            progressBar.style.width = `${defaultAssessment.percentage}`;
            progressTrack.setAttribute('aria-valuenow', '22.7');
            balance.textContent = defaultAssessment.balance;
            categoryCounts.forEach((count, index) => {
                count.textContent = defaultAssessment.categoryCounts[index];
            });
            skillResults.forEach((result, index) => {
                const isPassed = defaultAssessment.skillResults[index] === 'Passed';
                result.textContent = defaultAssessment.skillResults[index];
                result.classList.toggle('assessment-result--passed', isPassed);
                result.classList.toggle('assessment-result--failed', !isPassed);
            });
            categoryPanels.forEach((panel, index) => {
                panel.classList.add('show');
                categoryToggles[index].setAttribute('aria-expanded', 'true');
                categoryToggles[index].querySelector('i').className = 'bi bi-chevron-down';
            });
        });

        document.querySelectorAll('[data-certificate-input]').forEach((input) => {
            input.addEventListener('change', () => {
                const fileName = input.files && input.files.length ? `Selected: ${input.files[0].name} (not uploaded)` : '';
                input.closest('.graduation-ready-actions').querySelector('[data-certificate-name]').textContent = fileName;
            });
        });

        confirmGraduationButton.addEventListener('click', () => {
            graduationFeedback.textContent = 'Graduation confirmed in this preview only. No student record was changed.';
            graduationFeedback.classList.remove('d-none');
        });
    })();
})();
</script>