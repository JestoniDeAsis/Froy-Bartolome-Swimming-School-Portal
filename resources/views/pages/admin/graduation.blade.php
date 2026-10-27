@extends('layouts.toast')

@php
    // $graduation is passed by HomeController (AdminGraduationController::pageData()).
    // The fallback keeps the page working if it is ever rendered another way.
    $graduation = $graduation ?? app(\App\Http\Controllers\AdminGraduationController::class)->pageData();

    $graduationStudents = collect($graduation['active']);       // In Training tab
    $readyStudents      = collect($graduation['ready']);        // coach approved; the admin reviews (e.g. balance)
    $graduatingStudents = collect($graduation['graduating']);   // moved by the admin; certificate upload + final confirm
    $graduatedStudents  = collect($graduation['graduated']);
    $assessments        = $graduation['assessments'];           // keyed by enrollment id

    $graduationCourses = $graduationStudents
        ->filter(fn ($s) => $s['courseId'] !== null)
        ->unique('courseId')
        ->map(fn ($s) => ['id' => $s['courseId'], 'name' => $s['course']])
        ->sortBy('name')
        ->values();

    // Badge class + text for the coach's skills assessment status.
    $assessmentBadges = [
        'eligible'     => ['is-ready',      'Eligible to graduate'],
        'assessed'     => ['is-almost',     'Assessed'],
        'not_assessed' => ['is-notstarted', 'Not yet assessed'],
    ];

    $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);

    // ---- search / filter toolbars for the Ready, Graduating and Graduates tabs ----
    $gradSearchText = fn ($s) => \Illuminate\Support\Str::lower(trim($s['name'] . ' ' . $s['course'] . ' ' . ($s['schedule'] ?? '')));

    $gradCourseOptions = fn ($students) => ['' => 'All Courses'] + $students
        ->filter(fn ($s) => $s['courseId'] !== null)
        ->unique('courseId')
        ->sortBy('course')
        ->mapWithKeys(fn ($s) => [(string) $s['courseId'] => $s['course']])
        ->all();

    // $options = value => label; the first one is the default.
    $gradDropdown = function (string $id, string $key, string $aria, array $options) {
        $firstValue = (string) array_key_first($options);
        $html  = '<div class="registerDropdown graduationFilter" id="' . e($id) . 'Dropdown" data-fbss-dropdown>';
        $html .= '<button class="registerDropdownToggle" id="' . e($id) . 'Toggle" type="button" aria-expanded="false" aria-controls="' . e($id) . 'Options" aria-label="' . e($aria) . '"><span>' . e(reset($options)) . '</span><i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i></button>';
        $html .= '<div class="registerDropdownOptions" id="' . e($id) . 'Options" hidden>';
        $i = 0;
        foreach ($options as $value => $label) {
            $html .= '<button class="registerDropdownOption' . ($i++ === 0 ? ' active' : '') . '" type="button" data-value="' . e((string) $value) . '">' . e($label) . '</button>';
        }
        $html .= '</div><input type="hidden" data-pf="' . e($key) . '" value="' . e($firstValue) . '"></div>';

        return $html;
    };

    // $dropdowns = list of [key, aria label, options]
    $gradToolbar = function (string $id, string $searchLabel, array $dropdowns) use ($gradDropdown) {
        $html  = '<div class="graduationToolbar">';
        $html .= '<div class="graduationSearchContainer"><i class="bi bi-search graduationSearchIcon" aria-hidden="true"></i>';
        $html .= '<input class="form-control graduationSearchInput" type="search" data-pf="search" placeholder="' . e($searchLabel) . '" autocomplete="off" aria-label="' . e($searchLabel) . '"></div>';
        foreach ($dropdowns as [$key, $aria, $options]) {
            $html .= $gradDropdown($id . ucfirst($key), $key, $aria, $options);
        }
        $html .= '<span class="graduationResultCount" data-pf-count></span></div>';

        return new \Illuminate\Support\HtmlString($html);
    };

    $readyToolbar = $gradToolbar('gradReady', 'Search name, course or schedule', [
        ['course', 'Filter by course', $gradCourseOptions($readyStudents)],
        ['payment', 'Filter by payment', ['' => 'Any Payment', 'paid' => 'Fully paid', 'balance' => 'Has a balance']],
        ['sort', 'Sort students', ['name' => 'Sort: Name (A-Z)', 'balance' => 'Sort: Highest balance', 'done' => 'Sort: Most sessions done']],
    ]);

    $graduatingToolbar = $gradToolbar('gradGraduating', 'Search name, course or schedule', [
        ['course', 'Filter by course', $gradCourseOptions($graduatingStudents)],
        ['cert', 'Filter by certificate', ['' => 'Any Certificate', 'yes' => 'Certificate uploaded', 'no' => 'Certificate needed']],
        ['sort', 'Sort students', ['name' => 'Sort: Name (A-Z)', 'done' => 'Sort: Most sessions done']],
    ]);

    $graduateYears = $graduatedStudents
        ->map(fn ($s) => $s['graduatedAt'] ? substr($s['graduatedAt'], 0, 4) : null)
        ->filter()->unique()->sortDesc()
        ->mapWithKeys(fn ($y) => [(string) $y => (string) $y])->all();

    $graduatedToolbar = $gradToolbar('gradGraduated', 'Search name, course or schedule', [
        ['course', 'Filter by course', $gradCourseOptions($graduatedStudents)],
        ['year', 'Filter by year graduated', ['' => 'Any Year'] + $graduateYears],
        ['cert', 'Filter by certificate', ['' => 'Any Certificate', 'yes' => 'Has certificate', 'no' => 'Certificate not recorded']],
        ['sort', 'Sort students', ['newest' => 'Sort: Newest graduates', 'oldest' => 'Sort: Oldest graduates', 'name' => 'Sort: Name (A-Z)']],
    ]);

    $dayLabel = fn ($iso) => $iso ? \Illuminate\Support\Carbon::parse($iso)->format('M d, Y') : null;
@endphp

<style>
.graduationContainer {
    width: 100%;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
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

/* Real-data view (Active tab) and the Ready / Graduates panels share the space under the tabs. */
.graduationRealView, .graduationSample { flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column; }
.graduationRealView[hidden], .graduationSample[hidden] { display: none !important; }
.graduationSample { overflow-y: auto; }

/* The Ready / Graduating / Graduates lists are filtered in the browser by toggling [hidden]. */
.graduation-student[hidden], .graduation-empty[hidden] { display: none !important; }
.graduation-panel .graduationToolbar { margin-top: .5rem; }

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

/* Right side of an In Training card: status badge + View Progress button */
.graduationCardSide { display: flex; align-items: center; justify-content: flex-end; gap: .6rem; flex-wrap: wrap; }

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

@media (max-width: 767.98px) {
    .graduationContainer {
        height: 100vh !important;
        padding: 1rem !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
    }

    .graduationTabs {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: .5rem !important;
        width: 100% !important;
        margin-bottom: 1rem !important;
    }

    .graduationTabs .btn-tab,
    .graduationTab {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        padding: .5rem .3rem !important;
        font-size: .8rem !important;
        line-height: 1.2 !important;
        white-space: normal !important;
        word-break: normal !important;
        text-align: center !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        --bs-btn-padding-x: .3rem !important;
        --bs-btn-padding-y: .5rem !important;
        --bs-btn-font-size: .8rem !important;
    }

    .graduationStats {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: .5rem !important;
    }

    .graduationStat {
        padding: .7rem !important;
    }

    .graduationToolbar {
        gap: .5rem !important;
        display: flex !important;
        flex-wrap: wrap !important;
        width: 100% !important;
    }

    .graduationSearchContainer {
        width: 100% !important;
        flex: 1 1 100% !important;
    }

    .graduationSearchInput {
        width: 100% !important;
    }

    .graduationFilter {
        display: block !important;
        flex: 1 1 calc(50% - .25rem) !important;
        max-width: calc(50% - .25rem) !important;
        min-width: 0 !important;
        --dd-width: 100% !important;
        --dd-min-width: 0 !important;
    }

    .graduationFilter .registerDropdownToggle {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
    }

    .graduationResultCount {
        margin-left: 0 !important;
        width: 100% !important;
        margin-top: .25rem !important;
    }

    .graduationRealView,
    .graduationSample {
        flex: 0 0 auto !important;
        width: 100% !important;
        min-height: 260px !important;
        display: flex !important;
        flex-direction: column !important;
    }

    .graduationList {
        flex: 0 0 auto !important;
        width: 100% !important;
        min-height: 200px !important;
        overflow-y: visible !important;
        display: grid !important;
        gap: .65rem !important;
    }

    .graduationCard {
        grid-template-columns: 1fr !important;
        gap: .6rem !important;
        width: 100% !important;
    }
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
    .graduation-card-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: .45rem; max-width: 560px; }
    .graduation-card-actions .graduation-ready-file { flex-basis: 100%; }
    .graduation-upload-label { position: relative; margin: 0; }
    .graduation-panel-hint { margin: -.35rem 0 .75rem; padding-left: 1.2rem; color: #667085; font-size: .8rem; }
    .graduation-file-input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
    .assessment-result--none { color: #667085; background: #f1f3f5; }
    .assessment-note { margin: 0 0 .6rem; font-size: .72rem; color: #667085; }
    .assessment-balance { color: var(--fbss-danger); font-size: .85rem; font-style: italic; }
    .assessment-back { min-width: 76px; }

    @media (max-width: 640px) {
        .graduation-panel { padding: .9rem .75rem 1rem; }
        .graduation-student-list { padding-left: .15rem; }
        .graduation-student { align-items: flex-start; flex-direction: column; }
        .graduation-ready-actions { grid-template-columns: minmax(0, 1fr) 76px; gap: .25rem .5rem; width: 100%; }
        .graduation-ready-session, .graduation-ready-upload, .graduation-ready-file { white-space: normal; }
        .graduation-ready-session, .graduation-ready-upload { text-align: left; }
        .graduation-card-actions { justify-content: flex-start; max-width: none; width: 100%; }
        .graduation-graduate-actions { gap: .5rem; }
        .graduation-ready-actions, .graduation-graduate-actions { width: 100%; }
        .graduation-graduate-actions { justify-content: space-between; }
        .graduation-certificate-number { min-width: 0; white-space: normal; }
    }
</style>

<section class="graduationContainer" id="graduationContainer" aria-labelledby="graduationTitle">

    <header class="graduationHeader">
        <h2 class="mb-1" id="graduationTitle">Graduation Progress</h2>
        <p class="text-muted mb-0">Follow students from active learning to graduation. Sessions come from attendance; the coach decides who is ready to graduate.</p>
    </header>

    <nav class="btn-tab-group graduationTabs" id="graduationTabs" role="tablist" aria-label="Graduation student groups">
        <button class="btn btn-outline-primary btn-tab graduationTab active" type="button" role="tab" data-graduation-tab="active" aria-selected="true">In Training <span data-tab-count="active"></span></button>
        <button class="btn btn-outline-primary btn-tab graduationTab" type="button" role="tab" data-graduation-tab="ready" aria-selected="false">Ready for Graduation <span data-tab-count="ready"></span></button>
        <button class="btn btn-outline-primary btn-tab graduationTab" type="button" role="tab" data-graduation-tab="graduating" aria-selected="false">Graduating <span data-tab-count="graduating"></span></button>
        <button class="btn btn-outline-primary btn-tab graduationTab" type="button" role="tab" data-graduation-tab="graduated" aria-selected="false">Graduates <span data-tab-count="graduated"></span></button>
    </nav>

    <div class="graduationRealView" id="graduationRealView">

    <div class="graduationStats" aria-label="Active student summary">
        <article class="graduationStat">
            <div><p class="graduationStatLabel">Active students</p><p class="graduationStatValue" id="graduationStatActive">0</p></div>
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

    {{-- ===== READY FOR GRADUATION (the coach approved; the admin reviews, e.g. the balance) ===== --}}
    <div class="graduationSample" id="graduationPanelReady" hidden>
        <section class="graduation-panel" data-panel-filter>
            <h2 class="graduation-panel-heading">
                <i class="bi bi-patch-check" aria-hidden="true"></i>
                Ready for Graduation <span class="group-count">({{ $readyStudents->count() }})</span>
            </h2>
            <p class="graduation-panel-hint">The coach approved these students. Check their skills and balance first. A student with a balance cannot be moved to Graduating.</p>
            @if ($readyStudents->isNotEmpty()) {{ $readyToolbar }} @endif
            <div class="graduation-student-list">
                @forelse ($readyStudents as $student)
                    @php
                        $detail  = $assessments[$student['id']] ?? null;
                        $balance = (float) ($detail['balance'] ?? 0);
                    @endphp
                    <article class="graduation-student graduation-student--ready" data-grad-item data-name="{{ \Illuminate\Support\Str::lower($student['name']) }}" data-search="{{ $gradSearchText($student) }}" data-course-id="{{ $student['courseId'] }}" data-balance="{{ $balance }}" data-done="{{ $student['done'] }}">
                        <div class="graduation-student-info">
                            <div class="graduation-student-name">{{ $student['name'] }}</div>
                            <div class="graduation-student-meta">
                                <strong>{{ $student['course'] }}</strong><span class="mx-2">&middot;</span><strong>{{ $student['done'] }}{{ $student['total'] > 0 ? ' / ' . $student['total'] : '' }} sessions</strong><span class="mx-2">&middot;</span><span class="graduationBadge is-ready">Eligible to graduate</span>
                                <span class="graduationBadge {{ $balance > 0 ? 'is-almost' : 'is-ready' }}">{{ $balance > 0 ? 'Balance ' . $peso($balance) : 'Fully paid' }}</span>
                            </div>
                        </div>
                        <div class="graduation-card-actions">
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-enrollment-id="{{ $student['id'] }}" data-student="{{ $student['name'] }}" data-course="{{ $student['course'] }}" data-assessment-mode="view">View</button>
                            <button type="button" class="btn btn-primary btn-sm" data-mark-graduating data-enrollment-id="{{ $student['id'] }}" data-student="{{ $student['name'] }}" @disabled($balance > 0) @if ($balance > 0) title="This student still has a balance of {{ $peso($balance) }}." @endif>
                                <i class="bi bi-arrow-right-circle" aria-hidden="true"></i> Move to Graduating
                            </button>
                        </div>
                    </article>
                @empty
                    <p class="graduation-empty">No students are marked ready by their coach yet.</p>
                @endforelse
                <p class="graduation-empty" data-pf-empty hidden>No students match your search or filters.</p>
            </div>
        </section>
    </div>

    {{-- ===== GRADUATING (the admin uploads the certificate and finalizes the graduation) ===== --}}
    <div class="graduationSample" id="graduationPanelGraduating" hidden>
        <section class="graduation-panel" data-panel-filter>
            <h2 class="graduation-panel-heading">
                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                Graduating <span class="group-count">({{ $graduatingStudents->count() }})</span>
            </h2>
            <p class="graduation-panel-hint">Upload the certificate PDF for each student, then mark them as graduated.</p>
            @if ($graduatingStudents->isNotEmpty()) {{ $graduatingToolbar }} @endif
            <div class="graduation-student-list">
                @forelse ($graduatingStudents as $student)
                    @php $hasCertificate = $student['hasCertificate']; @endphp
                    <article class="graduation-student graduation-student--ready" data-grad-item data-name="{{ \Illuminate\Support\Str::lower($student['name']) }}" data-search="{{ $gradSearchText($student) }}" data-course-id="{{ $student['courseId'] }}" data-cert="{{ $hasCertificate ? 1 : 0 }}" data-done="{{ $student['done'] }}">
                        <div class="graduation-student-info">
                            <div class="graduation-student-name">{{ $student['name'] }}</div>
                            <div class="graduation-student-meta">
                                <strong>{{ $student['course'] }}</strong><span class="mx-2">&middot;</span><strong>{{ $student['done'] }}{{ $student['total'] > 0 ? ' / ' . $student['total'] : '' }} sessions</strong><span class="mx-2">&middot;</span>
                                <span class="graduationBadge {{ $hasCertificate ? 'is-ready' : 'is-almost' }}">{{ $hasCertificate ? 'Certificate uploaded' : 'Certificate needed' }}</span>
                            </div>
                        </div>
                        <div class="graduation-card-actions">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-move-back data-enrollment-id="{{ $student['id'] }}" data-student="{{ $student['name'] }}">
                                <i class="bi bi-arrow-left-circle" aria-hidden="true"></i> Back to Ready
                            </button>
                            @if ($hasCertificate)
                                <a class="btn btn-outline-primary btn-sm" href="{{ url('/certificates/' . $student['id']) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> View Certificate</a>
                            @endif
                            <label class="btn btn-outline-primary btn-sm graduation-upload-label">
                                <i class="bi bi-upload" aria-hidden="true"></i> {{ $hasCertificate ? 'Replace Certificate' : 'Upload Certificate' }}
                                <input class="graduation-file-input" type="file" accept="application/pdf,.pdf" data-certificate-input data-enrollment-id="{{ $student['id'] }}" data-next-tab="graduating">
                            </label>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-enrollment-id="{{ $student['id'] }}" data-student="{{ $student['name'] }}" data-course="{{ $student['course'] }}" data-assessment-mode="view">View</button>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-enrollment-id="{{ $student['id'] }}" data-student="{{ $student['name'] }}" data-course="{{ $student['course'] }}" data-assessment-mode="graduate" @disabled(! $hasCertificate) @if (! $hasCertificate) title="Upload the certificate first." @endif>
                                <i class="bi bi-award" aria-hidden="true"></i> Graduate
                            </button>
                            <span class="graduation-ready-file" data-certificate-name>{{ $hasCertificate ? ($student['certificateName'] ?? 'Certificate file') : 'Upload the certificate PDF (up to 5 MB) to enable Graduate.' }}</span>
                        </div>
                    </article>
                @empty
                    <p class="graduation-empty">No students are in Graduating right now.</p>
                @endforelse
                <p class="graduation-empty" data-pf-empty hidden>No students match your search or filters.</p>
            </div>
        </section>
    </div>

    {{-- ===== GRADUATES (enrollments with a graduation date) ===== --}}
    <div class="graduationSample" id="graduationPanelGraduated" hidden>
        <section class="graduation-panel" data-panel-filter>
            <h2 class="graduation-panel-heading">
                <i class="bi bi-mortarboard" aria-hidden="true"></i>
                Graduates <span class="group-count">({{ $graduatedStudents->count() }})</span>
            </h2>
            @if ($graduatedStudents->isNotEmpty()) {{ $graduatedToolbar }} @endif
            <div class="graduation-student-list">
                @forelse ($graduatedStudents as $student)
                    <article class="graduation-student graduation-student--graduated" data-grad-item data-name="{{ \Illuminate\Support\Str::lower($student['name']) }}" data-search="{{ $gradSearchText($student) }}" data-course-id="{{ $student['courseId'] }}" data-cert="{{ $student['hasCertificate'] ? 1 : 0 }}" data-done="{{ $student['done'] }}" data-grad-at="{{ $student['graduatedAt'] }}" data-grad-year="{{ $student['graduatedAt'] ? substr($student['graduatedAt'], 0, 4) : '' }}">
                        <div class="graduation-student-info">
                            <div class="graduation-student-name">{{ $student['name'] }}</div>
                            <div class="graduation-student-meta">
                                <strong>{{ $student['course'] }}</strong><span class="mx-2">&middot;</span><strong>{{ $student['done'] }}{{ $student['total'] > 0 ? ' / ' . $student['total'] : '' }} sessions</strong><span class="mx-2">&middot;</span>Graduated {{ $dayLabel($student['graduatedAt']) }}
                            </div>
                        </div>
                        <div class="graduation-graduate-actions">
                            @if ($student['hasCertificate'])
                                <a class="graduation-certificate-number" href="{{ url('/certificates/' . $student['id']) }}" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> View Certificate</a>
                            @else
                                <span class="graduation-certificate-number">Certificate not recorded</span>
                            @endif
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assessmentModal" data-enrollment-id="{{ $student['id'] }}" data-student="{{ $student['name'] }}" data-course="{{ $student['course'] }}" data-assessment-mode="view">View Details</button>
                        </div>
                    </article>
                @empty
                    <p class="graduation-empty">No students have graduated yet.</p>
                @endforelse
                <p class="graduation-empty" data-pf-empty hidden>No students match your search or filters.</p>
            </div>
        </section>
    </div>

</section>

<div class="modal fade assessment-modal" id="assessmentModal" tabindex="-1" aria-labelledby="assessmentStudent" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="assessment-student-name" id="assessmentStudent">Student</h2>
                    <div class="text-muted small" id="assessmentLevel">Core Skills Assessment List</div>
                    <div class="text-muted small" id="assessmentMeta"></div>
                </div>
                <span class="assessment-payment ms-auto me-2">Unpaid</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><span class="graduationBadge" id="assessmentEligibility"></span></div>
                <p class="assessment-note" id="assessmentNote" hidden></p>
                <div class="assessment-progress-summary">
                    <span class="assessment-progress-count"><span id="assessmentPassed">4</span> / <span id="assessmentTotal">18</span> passed</span>
                    <span class="assessment-progress-percent" id="assessmentPercent">22.7%</span>
                </div>
                <div class="progress assessment-progress" role="progressbar" aria-label="Skills passed" aria-valuenow="22.7" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: 22.7%"></div>
                </div>

                <div class="assessment-categories" id="assessmentCategories"></div>
                <p class="assessment-note mt-3 mb-0" id="assessmentGraduateNote" hidden>Confirming sets today as the graduation date and moves this student to Graduates. The certificate has been uploaded and the balance is paid.</p>
            </div>
            <div class="modal-footer justify-content-between">
                <div>
                    <strong class="assessment-balance">Balance: ₱0</strong>
                    <div class="text-muted small" id="assessmentArchivedNote" hidden></div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary btn-sm assessment-back" data-bs-dismiss="modal">Back</button>
                    <button type="button" class="btn btn-primary btn-sm d-none" id="confirmGraduationButton" data-bs-dismiss="modal">Mark as Graduated</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- CONFIRM POPUP (replaces the browser's confirm box: Move to Graduating, Back to Ready) --}}
<div class="modal fade assessment-modal" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionTitle" aria-describedby="confirmActionMessage" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="assessment-student-name" id="confirmActionTitle">Confirm</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="confirmActionMessage"></p>
            </div>
            <div class="modal-footer justify-content-end">
                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="confirmActionOk">Confirm</button>
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

    let students = @json($graduationStudents);   // replaced by the auto-refresh
    const COUNTS = { ready: {{ $readyStudents->count() }}, graduating: {{ $graduatingStudents->count() }}, graduated: {{ $graduatedStudents->count() }} };
    const TABS = ['active', 'ready', 'graduating', 'graduated'];
    const ASSESSMENTS = @json($assessments);   // keyed by enrollment id
    let panelsStale = false;   // the Ready / Graduating / Graduates tabs changed on the server (see auto-refresh)
    // After an upload / graduation the page reloads; this brings the admin back to the right tab.
    let activeTab = sessionStorage.getItem('graduationTab');
    sessionStorage.removeItem('graduationTab');
    if (!TABS.includes(activeTab)) activeTab = 'active';

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

    // Active tab: students who are not approved by the coach yet (the other tabs are rendered by the server).
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

        // Right side: status badge + View Progress (opens the same skills popup as the other tabs)
        const side = make('div', 'graduationCardSide');
        const viewButton = make('button', 'btn btn-outline-primary btn-sm', 'View Progress');
        viewButton.type = 'button';
        viewButton.setAttribute('data-bs-toggle', 'modal');
        viewButton.setAttribute('data-bs-target', '#assessmentModal');
        viewButton.dataset.enrollmentId = String(s.id);
        viewButton.dataset.student = s.name;
        viewButton.dataset.course = s.course;
        viewButton.dataset.assessmentMode = 'view';
        side.append(make('span', `graduationBadge is-${key}`, PROGRESS_LABELS[key]), viewButton);

        // The coach did not mark this student, but they finished every session and passed enough skills.
        if (s.canMarkReady) {
            const readyButton = make('button', 'btn btn-primary btn-sm', 'Mark as Ready');
            readyButton.type = 'button';
            readyButton.setAttribute('data-mark-ready', '');
            readyButton.dataset.enrollmentId = String(s.id);
            readyButton.dataset.student = s.name;
            readyButton.title = 'Finished all sessions and passed enough skills, but the coach has not marked this student.';
            side.append(readyButton);
        }

        card.append(info, progress, side);
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

        const counts = { active: students.length, ready: COUNTS.ready, graduating: COUNTS.graduating, graduated: COUNTS.graduated };
        el.tabs.querySelectorAll('[data-tab-count]').forEach(span => {
            span.textContent = `(${counts[span.dataset.tabCount]})`;
        });

        $('graduationRealView').hidden = activeTab !== 'active';
        $('graduationPanelReady').hidden = activeTab !== 'ready';
        $('graduationPanelGraduating').hidden = activeTab !== 'graduating';
        $('graduationPanelGraduated').hidden = activeTab !== 'graduated';
    }

    el.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-graduation-tab]');
        if (!button || button.dataset.graduationTab === activeTab) return;

        activeTab = button.dataset.graduationTab;

        // Those tabs are built by the server, so a change is shown by reloading onto the tab picked.
        if (panelsStale && activeTab !== 'active') {
            reloadPage();
            return;
        }

        updateTabs();
    });

    renderStats();
    updateTabs();
    render();

    /* =========================================================
       AUTO-REFRESH every 10 seconds (GET /graduation/data)
       - In Training tab: updated in place, so search / filters / scroll stay.
       - Ready / Graduating / Graduates tabs are built by the server: when they
         change, the page reloads onto the tab being viewed. While the In
         Training tab is open it waits and reloads when one of those tabs is clicked.
       - Paused while the tab is hidden, a popup is open, or an upload / move is running.
    ========================================================= */

    const REFRESH_MS = 10000;
    const DATA_URL = '/graduation/data';

    // What the server-rendered tabs show, so a change in them can be noticed.
    function serverTabsSignature(data) {
        const assessments = data.assessments || {};

        return JSON.stringify(['ready', 'graduating', 'graduated'].map(key =>
            (data[key] || []).map(s => [s, assessments[s.id]?.balance ?? 0, assessments[s.id]?.status ?? ''])
        ));
    }

    const serverSignature = serverTabsSignature({
        ready: @json($readyStudents->values()),
        graduating: @json($graduatingStudents->values()),
        graduated: @json($graduatedStudents->values()),
        assessments: ASSESSMENTS
    });

    let refreshing = false;

    function reloadPage() {
        sessionStorage.setItem('graduationTab', activeTab);
        window.location.reload();
    }

    async function refreshQuietly() {
        if (document.hidden || refreshing) return;
        if (document.querySelector('.modal.show, [data-certificate-input]:disabled, [data-move-back]:disabled')) return;

        refreshing = true;

        try {
            const response = await fetch(DATA_URL, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;   // e.g. the session expired: leave the page as it is

            const data = await response.json();
            if (!data.success) return;

            const serverTabsChanged = serverTabsSignature(data) !== serverSignature;

            if (serverTabsChanged && activeTab !== 'active') {
                reloadPage();
                return;
            }

            // View Progress should always show the latest results.
            Object.keys(ASSESSMENTS).forEach(key => delete ASSESSMENTS[key]);
            Object.assign(ASSESSMENTS, data.assessments || {});

            COUNTS.ready = (data.ready || []).length;
            COUNTS.graduating = (data.graduating || []).length;
            COUNTS.graduated = (data.graduated || []).length;
            if (serverTabsChanged) panelsStale = true;

            if (JSON.stringify(data.active || []) !== JSON.stringify(students)) {
                students = data.active || [];
                renderStats();
                render();
            }

            updateTabs();   // the counts on the tab buttons
        } catch (error) {
            console.error(error);
        } finally {
            refreshing = false;
        }
    }

    document.addEventListener('visibilitychange', refreshQuietly);
    setInterval(refreshQuietly, REFRESH_MS);

    /* =========================================================
       SEARCH / FILTER / SORT for the server-rendered tabs
       (Ready, Graduating, Graduates). Rows carry data-* attributes;
       filtering toggles [hidden], sorting reorders the rows.
    ========================================================= */

    document.querySelectorAll('[data-panel-filter]').forEach(panel => {
        const list = panel.querySelector('.graduation-student-list');
        const rows = [...list.querySelectorAll('[data-grad-item]')];
        const noMatch = list.querySelector('[data-pf-empty]');
        const count = panel.querySelector('[data-pf-count]');
        if (!rows.length || !count) return;   // nothing to filter

        const value = key => panel.querySelector(`[data-pf="${key}"]`)?.value || '';
        const byName = (a, b) => a.dataset.name.localeCompare(b.dataset.name);

        const sorters = {
            name: byName,
            balance: (a, b) => Number(b.dataset.balance) - Number(a.dataset.balance) || byName(a, b),
            done: (a, b) => Number(b.dataset.done) - Number(a.dataset.done) || byName(a, b),
            newest: (a, b) => (b.dataset.gradAt || '').localeCompare(a.dataset.gradAt || '') || byName(a, b),
            oldest: (a, b) => (a.dataset.gradAt || '').localeCompare(b.dataset.gradAt || '') || byName(a, b)
        };

        function apply() {
            const term = value('search').trim().toLowerCase();
            const course = value('course');
            const payment = value('payment');
            const cert = value('cert');
            const year = value('year');
            let shown = 0;

            rows.forEach(row => {
                const d = row.dataset;
                const ok = (!term || d.search.includes(term))
                    && (!course || d.courseId === course)
                    && (!payment || (payment === 'paid' ? Number(d.balance) <= 0 : Number(d.balance) > 0))
                    && (!cert || d.cert === (cert === 'yes' ? '1' : '0'))
                    && (!year || d.gradYear === year);
                row.hidden = !ok;
                if (ok) shown++;
            });

            [...rows].sort(sorters[value('sort')] || sorters.name).forEach(row => list.insertBefore(row, noMatch));

            noMatch.hidden = shown > 0;
            count.textContent = `Showing ${shown} of ${rows.length}`;
        }

        panel.addEventListener('input', event => { if (event.target.matches('[data-pf="search"]')) apply(); });
        panel.addEventListener('dropdown:change', apply);
        apply();
    });

    /* =========================================================
       ASSESSMENT POPUP (real skills results + payment) and saving
    ========================================================= */
    (function () {
        const modalEl = $('assessmentModal');
        const paymentBadge = modalEl.querySelector('.assessment-payment');
        const progressBar = modalEl.querySelector('.assessment-progress .progress-bar');
        const progressTrack = modalEl.querySelector('.assessment-progress');
        const balance = modalEl.querySelector('.assessment-balance');
        const categoriesEl = $('assessmentCategories');
        const eligibility = $('assessmentEligibility');
        const note = $('assessmentNote');
        const confirmGraduationButton = $('confirmGraduationButton');
        const graduateNote = $('assessmentGraduateNote');
        let pendingEnrollmentId = null;

        const STATUS = {
            eligible: { cls: 'is-ready', text: 'Eligible to graduate' },
            assessed: { cls: 'is-almost', text: 'Assessed' },
            not_assessed: { cls: 'is-notstarted', text: 'Not yet assessed' }
        };

        const RESULT = {
            pass: { cls: 'assessment-result--passed', text: 'Passed' },
            failed: { cls: 'assessment-result--failed', text: 'Failed' },
            none: { cls: 'assessment-result--none', text: 'Not marked' }
        };

        const peso = n => '₱' + Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });

        function renderCategories(categories) {
            categoriesEl.replaceChildren();

            if (!categories.length) {
                categoriesEl.append(make('p', 'assessment-note', 'No skill categories have been set up for this course.'));
                return;
            }

            categories.forEach((category, index) => {
                const panelId = `assessment-skills-${index}`;
                const wrapper = make('div', 'assessment-category');

                const toggle = make('button', 'assessment-category-toggle');
                toggle.type = 'button';
                toggle.setAttribute('data-bs-toggle', 'collapse');
                toggle.setAttribute('data-bs-target', `#${panelId}`);
                toggle.setAttribute('aria-expanded', 'true');
                toggle.setAttribute('aria-controls', panelId);
                toggle.append(
                    icon('bi-chevron-down'),
                    make('span', null, category.name),
                    make('span', 'assessment-category-count', `${category.passed} / ${category.total} passed`)
                );

                const panel = make('div', 'collapse show');
                panel.id = panelId;
                const skills = make('div', 'assessment-skills');

                category.skills.forEach(skill => {
                    const result = RESULT[skill.result] || RESULT.none;
                    const row = make('div', 'assessment-skill');
                    row.append(make('span', null, skill.name), make('span', `assessment-result ${result.cls}`, result.text));
                    skills.append(row);
                });

                panel.append(skills);
                wrapper.append(toggle, panel);
                categoriesEl.append(wrapper);
            });
        }

        modalEl.addEventListener('show.bs.modal', event => {
            const trigger = event.relatedTarget;
            const detail = ASSESSMENTS[trigger.dataset.enrollmentId];
            const displayName = trigger.dataset.student || 'Student';
            const nameParts = displayName.split(', ');
            const modalName = nameParts.length === 2 ? `${nameParts[1]} ${nameParts[0]}` : displayName;
            const mode = trigger.dataset.assessmentMode || 'view';
            pendingEnrollmentId = mode === 'graduate' ? trigger.dataset.enrollmentId : null;

            $('assessmentStudent').textContent = modalName;
            $('assessmentLevel').textContent = `${trigger.dataset.course || 'Course'} · Core Skills Assessment List`;
            confirmGraduationButton.classList.toggle('d-none', mode !== 'graduate');
            graduateNote.hidden = mode !== 'graduate';

            if (!detail) {
                $('assessmentMeta').textContent = '';
                eligibility.className = 'graduationBadge is-notstarted';
                eligibility.textContent = 'No assessment data';
                note.hidden = true;
                $('assessmentPassed').textContent = '0';
                $('assessmentTotal').textContent = '0';
                $('assessmentPercent').textContent = '0%';
                progressBar.style.width = '0%';
                progressTrack.setAttribute('aria-valuenow', '0');
                paymentBadge.textContent = '-';
                balance.textContent = '';
                $('assessmentArchivedNote').hidden = true;
                categoriesEl.replaceChildren();
                return;
            }

            // Who assessed + when
            $('assessmentMeta').textContent = detail.assessed_by
                ? `Assessed by ${detail.assessed_by}${detail.assessed_at ? ' on ' + formatDay(detail.assessed_at) : ''}`
                : 'Not assessed by a coach yet';

            // Eligibility badge
            const status = STATUS[detail.status] || STATUS.not_assessed;
            eligibility.className = `graduationBadge ${status.cls}`;
            eligibility.textContent = status.text;

            if (detail.status === 'eligible' && detail.ready_to_graduate && detail.passed < detail.total) {
                note.textContent = 'This student was marked as eligible even though not every skill was passed.';
                note.hidden = false;
            } else {
                note.hidden = true;
            }

            // Progress
            const percent = detail.total > 0 ? Math.round((detail.passed / detail.total) * 1000) / 10 : 0;
            $('assessmentPassed').textContent = detail.passed;
            $('assessmentTotal').textContent = detail.total;
            $('assessmentPercent').textContent = `${percent}%`;
            progressBar.style.width = `${percent}%`;
            progressTrack.setAttribute('aria-valuenow', String(percent));

            // Payment
            const paid = detail.balance <= 0;
            paymentBadge.textContent = paid ? 'Paid' : 'Unpaid';
            paymentBadge.classList.toggle('assessment-payment--paid', paid);
            balance.textContent = `Balance: ${peso(detail.balance)}`;

            // Archived payments are not counted; say so, so the balance makes sense.
            const archivedNote = $('assessmentArchivedNote');
            archivedNote.hidden = !detail.archived_count;
            archivedNote.textContent = detail.archived_count
                ? `${detail.archived_count} archived payment${detail.archived_count > 1 ? 's' : ''} (${peso(detail.archived_total)}) not counted.`
                : '';

            renderCategories(detail.categories || []);
        });

        /* ---------- confirm popup (instead of the browser's confirm box) ---------- */

        const confirmEl = $('confirmActionModal');
        let confirmDone = null;   // resolves the pending askConfirm()

        function settleConfirm(answer) {
            if (!confirmDone) return;
            const done = confirmDone;
            confirmDone = null;
            done(answer);
        }

        // Resolves true when the admin confirms, false when they cancel or close it.
        function askConfirm({ title, message, confirmText }) {
            const Modal = window.bootstrap && window.bootstrap.Modal;
            if (!Modal) return Promise.resolve(window.confirm(message));   // Bootstrap missing: still ask

            settleConfirm(false);

            $('confirmActionTitle').textContent = title;
            $('confirmActionMessage').textContent = message;
            $('confirmActionOk').textContent = confirmText;

            return new Promise(resolve => {
                confirmDone = resolve;
                Modal.getOrCreateInstance(confirmEl).show();
            });
        }

        $('confirmActionOk').addEventListener('click', () => {
            settleConfirm(true);
            window.bootstrap.Modal.getOrCreateInstance(confirmEl).hide();
        });

        // Cancel, the X, Escape or a click outside all end up here.
        confirmEl.addEventListener('hidden.bs.modal', () => settleConfirm(false));

        // "Dela Cruz, Juan A." -> "Juan A. Dela Cruz"
        function friendlyName(name) {
            const parts = String(name || 'this student').split(', ');
            return parts.length === 2 ? `${parts[1]} ${parts[0]}` : parts[0];
        }

        /* ---------- saving (upload certificate / mark as graduated) ---------- */

        async function send(url, body) {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body
            });

            // Keep the raw reply too, so an unexpected error can still be read.
            const raw = await response.text();
            let data = {};
            try { data = JSON.parse(raw); } catch (e) { /* non-JSON error page */ }

            return { ok: response.ok, status: response.status, data, raw };
        }

        // Shows the real reason. Some replies (like "file too large") have no text of their own.
        function errorText(result, fallback) {
            const data = result.data || {};
            const first = data.errors ? Object.values(data.errors).flat()[0] : null;

            if (first) return first;
            if (data.message) return data.message;

            const byStatus = {
                401: 'Your session has expired. Please log in again.',
                403: 'You are not allowed to do this.',
                413: 'The file is too large for the server. Try a smaller PDF, or raise the server upload limit.',
                419: 'Your session expired. Reload the page and try again.',
                500: 'Server error. Check storage/logs/laravel.log for the reason.'
            };

            if (byStatus[result.status]) return byStatus[result.status];

            // Unknown error: show what the server actually said (also logged in the browser console).
            console.error('Unexpected server reply', result);
            const raw = String(result.raw || '').replace(/\s+/g, ' ').trim().slice(0, 200);

            return `${fallback} (error ${result.status}${raw ? ': ' + raw : ''})`;
        }

        // The tab to open after the page reloads with the new data.
        function reloadOnTab(tab) {
            sessionStorage.setItem('graduationTab', tab);
            window.location.reload();
        }

        document.querySelectorAll('[data-move-back]').forEach(button => {
            button.addEventListener('click', async () => {
                if (!await askConfirm({
                    title: 'Back to Ready',
                    message: `Move ${friendlyName(button.dataset.student)} back to Ready for Graduation? Their uploaded certificate is kept.`,
                    confirmText: 'Move Back'
                })) return;

                button.disabled = true;

                try {
                    const result = await send(`/graduation/${button.dataset.enrollmentId}/ready`);

                    if (result.ok) {
                        showToast(result.data.message || 'Student moved back to Ready for Graduation.', 'success');
                        reloadOnTab('ready');
                        return;
                    }

                    showToast(errorText(result, 'Unable to move the student back.'), 'danger');
                } catch (error) {
                    console.error(error);
                    showToast('Something went wrong while moving the student back.', 'danger');
                }

                button.disabled = false;
            });
        });

        document.querySelectorAll('[data-mark-graduating]').forEach(button => {
            button.addEventListener('click', async () => {
                if (!await askConfirm({
                    title: 'Move to Graduating',
                    message: `Move ${friendlyName(button.dataset.student)} to Graduating? Upload their certificate next to finish.`,
                    confirmText: 'Move to Graduating'
                })) return;

                button.disabled = true;

                try {
                    const result = await send(`/graduation/${button.dataset.enrollmentId}/graduating`);

                    if (result.ok) {
                        showToast(result.data.message || 'Student moved to Graduating.', 'success');
                        reloadOnTab('graduating');
                        return;
                    }

                    showToast(errorText(result, 'Unable to move the student to Graduating.'), 'danger');
                } catch (error) {
                    console.error(error);
                    showToast('Something went wrong while moving the student.', 'danger');
                }

                button.disabled = false;
            });
        });

        document.querySelectorAll('[data-certificate-input]').forEach(input => {
            input.addEventListener('change', async () => {
                const file = input.files && input.files[0];
                if (!file) return;

                const status = input.closest('.graduation-card-actions').querySelector('[data-certificate-name]');

                if (!/\.pdf$/i.test(file.name)) {
                    showToast('Please choose a PDF file.', 'danger');
                    input.value = '';
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    showToast('The PDF must be 5 MB or smaller.', 'danger');
                    input.value = '';
                    return;
                }

                input.disabled = true;
                status.textContent = `Uploading ${file.name}...`;

                try {
                    const form = new FormData();
                    form.append('certificate', file);

                    const result = await send(`/graduation/${input.dataset.enrollmentId}/certificate`, form);

                    if (result.ok) {
                        showToast(result.data.message || 'Certificate uploaded.', 'success');
                        reloadOnTab(input.dataset.nextTab || 'graduating');
                        return;
                    }

                    showToast(errorText(result, 'Unable to upload the certificate.'), 'danger');
                } catch (error) {
                    console.error(error);
                    showToast('Something went wrong while uploading the certificate.', 'danger');
                }

                status.textContent = '';
                input.value = '';
                input.disabled = false;
            });
        });

        // "Mark as Ready" on the In Training cards. One listener on the page,
        // because those cards are rebuilt by render() and the auto-refresh.
        document.addEventListener('click', async event => {
            const button = event.target.closest('[data-mark-ready]');
            if (!button || button.disabled) return;

            if (!await askConfirm({
                title: 'Mark as Ready',
                message: `Mark ${friendlyName(button.dataset.student)} as ready for graduation? They finished all sessions and passed enough skills.`,
                confirmText: 'Mark as Ready'
            })) return;

            button.disabled = true;

            try {
                const result = await send(`/graduation/${button.dataset.enrollmentId}/mark-ready`);

                if (result.ok) {
                    showToast(result.data.message || 'Student marked as ready.', 'success');
                    reloadOnTab('ready');
                    return;
                }

                showToast(errorText(result, 'Unable to mark the student as ready.'), 'danger');
            } catch (error) {
                console.error(error);
                showToast('Something went wrong while marking the student as ready.', 'danger');
            }

            button.disabled = false;
        });

        confirmGraduationButton.addEventListener('click', async () => {
            if (!pendingEnrollmentId) return;

            try {
                const result = await send(`/graduation/${pendingEnrollmentId}/graduate`);

                if (result.ok) {
                    showToast(result.data.message || 'Student marked as graduated.', 'success');
                    reloadOnTab('graduated');
                    return;
                }

                showToast(errorText(result, 'Unable to mark the student as graduated.'), 'danger');
            } catch (error) {
                console.error(error);
                showToast('Something went wrong while saving the graduation.', 'danger');
            }
        });
    })();
})();
</script>