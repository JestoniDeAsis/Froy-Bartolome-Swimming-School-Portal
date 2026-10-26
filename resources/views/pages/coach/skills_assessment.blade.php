@extends('layouts.toast')

<style>
/* =========================================================
   Skills Assessment (coach)
   Same skeleton as the coach Attendance page. Dropdowns use the shared
   .registerDropdown + assets/js/fbss-dropdown.js (data-fbss-dropdown).
========================================================= */

.skillsContainer {
    width: 100%;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.skillsHeader {
    flex: 0 0 auto;
    margin-bottom: 1rem;
}

/* Toolbar */
.skillsToolbar {
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

.skillsFilterGroup {
    display: flex;
    flex-direction: column;
    gap: .35rem;
}

.skillsFilterLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}

.skillsClassFilter { --dd-min-width: 160px; }

/* Search */
.skillsSearchContainer {
    position: relative;
    width: min(260px, 100%);
}

.skillsSearchIcon {
    position: absolute;
    top: 50%;
    left: .75rem;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.skillsSearchInput {
    height: 40px;
    box-sizing: border-box;
    border-radius: 20px;
    padding-left: 2.25rem;
}

/* Date picker: a styled button with a real <input type="date"> underneath. */
.skillsDatePicker { position: relative; }

.skillsDateButton {
    display: inline-flex;
    align-items: center;
    gap: .6rem;
    min-width: 190px;
    height: 40px;
    padding: 0 .9rem;
    background: #fff;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    font-size: .875rem;
    color: #212529;
    cursor: pointer;
}

.skillsDateButton i { margin-left: auto; color: #6c757d; }

.skillsDateInput {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    pointer-events: none;
}

.skillsToolbarActions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-left: auto;
}

/* Table */
.skillsTableContainer {
    --skills-row-height: 49px;
    --skills-header-height: 49px;
    --skills-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;
    max-height: calc(var(--skills-header-height) + (var(--skills-row-height) * var(--skills-visible-rows)));
    width: 100%;
    overflow: auto;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    box-sizing: border-box;
}

.skillsTable {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.skillsTable thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafb;
    color: #667085;
    font-weight: 600;
    border-bottom: 1px solid #e7ebef;
    box-shadow: 0 1px 0 #e7ebef;
}

.skillsTable th,
.skillsTable td { padding: .75rem 1rem; vertical-align: middle; }

.skillsRow { transition: background-color .1s ease; cursor: pointer; }
.skillsRow:hover { background: #f8fafb; }

.skillsRow.selectedRow,
.skillsRow.selectedRow:hover,
.skillsRow.selectedRow > td { background: #e7f5ff; }

.skillsEmptyState {
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}

.skillsNameCell { display: inline-flex; align-items: center; gap: .55rem; }

.skillsStatusDot { flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%; }
.skillsStatusDot.present { background: #2f9e44; }
.skillsStatusDot.absent  { background: #e03131; }
.skillsStatusDot.expecting { background: #f59f00; }

.skillsEmptyValue { color: #adb5bd; }

@media (max-width: 575.98px) {
    .skillsContainer { padding: 1rem; }
    .skillsToolbarActions { margin-left: 0; width: 100%; }
    .skillsToolbarActions button { flex: 1 1 auto; }
}

/* Checklist modal (backdrop = shared .modalOverlay in our_theme.css) */
.skillsModal {
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(16, 24, 40, .18);
    overflow: hidden;
}

.skillsModalHeader {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e7ebef;
}

.skillsModalHeader h5 { margin: 0; font-size: 1rem; font-weight: 700; }

.skillsModalSub {
    margin: .15rem 0 0;
    font-size: .8125rem;
    color: #667085;
}

.skillsModalClose {
    border: none;
    background: transparent;
    font-size: 1.25rem;
    line-height: 1;
    color: #667085;
    cursor: pointer;
    padding: 0;
}

.skillsModalBody {
    min-height: 0;
    padding: 1.25rem;
    overflow-y: auto;
}

.skillsModalFooter {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #e7ebef;
}

.skillsModalNote {
    margin: 0;
    font-size: .8125rem;
    color: #667085;
}

.skillsCategory + .skillsCategory { margin-top: 1.25rem; }

.skillsCategoryHeader {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: .5rem;
}

.skillsCategoryTitle {
    margin: 0;
    color: #344054;
    font-size: .8125rem;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
}

.skillsCategoryCount { color: #667085; font-size: .75rem; font-weight: 600; }

.skillsSkillList { display: grid; gap: .4rem; }

.skillsSkillRow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .5rem .75rem;
    background: #f8fafb;
    border-radius: 8px;
}

.skillsSkillName { color: #344054; font-size: .875rem; font-weight: 500; }

/* Skill rows that still have no Pass / Failed when Save is pressed */
.skillsSkillRow.isMissing { background: #fff5f5; outline: 1px solid #ffc9c9; }

.skillsChecklistEmpty { margin: 0; padding: 1.5rem 0; color: #6c757d; text-align: center; }

/* Passing-grade bar (same idea as the admin Graduation popup) */
.skillsProgress { padding: .75rem 1.25rem; border-bottom: 1px solid #e7ebef; }
.skillsProgressSummary { display: flex; justify-content: space-between; gap: .5rem; margin-bottom: .3rem; font-size: .8rem; }
.skillsProgressCount { font-weight: 600; color: #344054; }
.skillsProgressPercent { color: #667085; }
.skillsProgressTrack { position: relative; height: 8px; background: #e7ebf0; border-radius: 99px; overflow: hidden; }
.skillsProgressFill { height: 100%; width: 0; background: var(--fbss-blue, #0d6efd); border-radius: 99px; transition: width .15s ease; }
.skillsProgressTrack.isPassing .skillsProgressFill { background: #1a7f3c; }
/* The passing-grade line: the bar has to reach it before "eligible" can be ticked. */
.skillsProgressMark { position: absolute; top: 0; bottom: 0; width: 2px; background: #344054; }
.skillsProgressBasis { margin-top: .35rem; font-size: .75rem; color: #667085; }
.skillsProgressBasis.isPassing { color: #1a7f3c; font-weight: 600; }

/* Small Time Out modal (same card as the checklist modal, narrower) */
.skillsModal.isSmall { max-width: 360px; }
.skillsModal.isSmall .skillsModalFooter { justify-content: flex-end; }
.skillsModal.isSmall .skillsModalBody { display: flex; flex-direction: column; gap: .5rem; }

.skillsTimeName { margin: 0 0 .25rem; font-size: .875rem; color: #344054; }
.skillsTimeName strong { color: #101828; }

.skillsTimeLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #101828;
    transition: color .15s ease;
}
.skillsTimeLabel.isDimmed { color: #adb5bd; }

.skillsTimeInput {
    height: 44px;
    padding: 0 .9rem;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    font-size: 1rem;
    color: #212529;
    background: #fff;
}

.skillsTimeInput:focus {
    outline: none;
    border-color: #4dabf7;
    box-shadow: 0 0 0 3px rgba(77, 171, 247, .15);
}

@media (max-width: 575.98px) {
    .skillsModalFooter { flex-direction: column-reverse; align-items: stretch; }
}
.skillsNotice {
    margin-bottom: .75rem;
    padding: .55rem .85rem;
    border-radius: .5rem;
    border: 1px solid #ffe08a;
    background: #fff8e1;
    color: #8a6100;
    font-size: .85rem;
}

.skillsLastUpdated {
    margin: .25rem 0 .35rem;
    font-size: .75rem;
    color: #98a2b3;
}
</style>


<section class="skillsContainer" id="skillsContainer" aria-labelledby="skillsTitle">

    <header class="skillsHeader">
        <h2 class="mb-1" id="skillsTitle">Skills Assessment</h2>
        <p class="text-muted mb-0" id="skillsDescription"></p>
        <p class="text-muted small mb-0" id="skillsHint">
            Shows today's students you have an attendance record for (timed in, Expected or Absent). Select a student to assess their course skills; use Time In (Late) for a student marked Expected.
        </p>
    </header>

    <!-- Shown while the coach has not timed in yet (Attendance page) -->
    <div class="skillsNotice" id="sessionNotice" role="status" hidden></div>

    <!-- TOOLBAR -->
    <div class="skillsToolbar">

        <!-- Select Class (only the coach's own classes; options come from the database) -->
        <div class="skillsFilterGroup">
            <label class="skillsFilterLabel">Select Class</label>

            <div class="registerDropdown skillsClassFilter" id="classFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="classFilterToggle" type="button" aria-expanded="false" aria-controls="classFilterOptions" aria-label="Filter by class">
                    <span>All My Classes</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="classFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All My Classes</button>
                </div>

                <input id="classFilterInput" name="classFilter" type="hidden" value="">
            </div>
        </div>

        <!-- Search -->
        <div class="skillsFilterGroup">
            <label class="skillsFilterLabel" for="skillsSearch">Search</label>

            <div class="skillsSearchContainer">
                <i class="bi bi-search skillsSearchIcon" aria-hidden="true"></i>
                <input class="form-control skillsSearchInput" id="skillsSearch" type="search"
                       placeholder="Search" autocomplete="off" aria-label="Search students">
            </div>
        </div>

        <!-- Actions -->
        <div class="skillsToolbarActions">
            <button class="btn btn-primary" id="timeInLateButton" type="button" disabled title="Only for students marked Expected">Time In (Late)</button>
            <button class="btn btn-primary" id="recordTimeOutButton" type="button" disabled title="Time Out is available once the student has a Time In">Time Out</button>
            <button class="btn btn-outline-danger" id="markAbsentButton" type="button" disabled title="Mark a student absent if they have not timed in">Mark Absent</button>
            <button class="btn btn-outline-danger" id="removeMarkButton" type="button" disabled title="Remove an Expected or Absent mark from the selected student">Remove Mark</button>
            <button class="btn btn-primary" id="viewChecklistButton" type="button" disabled>View Checklist</button>
        </div>

    </div>

    <!-- Auto-refresh note -->
    <div class="skillsLastUpdated" id="lastUpdated" aria-hidden="true"></div>

    <!-- TABLE -->
    <div class="skillsTableContainer" id="skillsTableContainer">
        <table class="table skillsTable" id="skillsTable">
            <thead id="skillsTableHead"></thead>
            <tbody id="skillsTableBody"></tbody>
        </table>
    </div>

</section>


<!-- CONFIRM MODAL (Mark absent) -->
<div class="modalOverlay" id="confirmModalOverlay" hidden>
    <div class="skillsModal isSmall" role="alertdialog" aria-modal="true" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalMessage">

        <div class="skillsModalHeader">
            <div>
                <h5 id="confirmModalTitle">Confirm</h5>
            </div>
            <button class="skillsModalClose" id="confirmModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="skillsModalBody">
            <p class="skillsTimeName mb-0" id="confirmModalMessage"></p>
        </div>

        <div class="skillsModalFooter">
            <button class="btn btn-outline-primary" id="confirmModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="confirmModalOkButton" type="button">Confirm</button>
        </div>

    </div>
</div>


<!-- CHECKLIST MODAL (Pass / Failed for every skill, saved to the database) -->
<div class="modalOverlay" id="checklistModalOverlay" hidden>
    <div class="skillsModal" id="checklistModal" role="dialog" aria-modal="true" aria-labelledby="checklistModalTitle">

        <div class="skillsModalHeader">
            <div>
                <h5 id="checklistModalTitle">Student</h5>
                <p class="skillsModalSub" id="checklistModalCourse"></p>
            </div>
            <button class="skillsModalClose" id="checklistModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <!-- Passing-grade bar: updates as Pass / Failed is picked -->
        <div class="skillsProgress" id="checklistProgress">
            <div class="skillsProgressSummary">
                <span class="skillsProgressCount"><span id="checklistPassed">0</span> / <span id="checklistTotal">0</span> passed</span>
                <span class="skillsProgressPercent" id="checklistPercent">0%</span>
            </div>
            <div class="skillsProgressTrack" id="checklistProgressTrack" role="progressbar" aria-label="Skills passed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div class="skillsProgressFill" id="checklistProgressFill"></div>
                <div class="skillsProgressMark" id="checklistProgressMark"></div>
            </div>
            <div class="skillsProgressBasis" id="checklistBasis"></div>
        </div>

        <div class="skillsModalBody" id="checklistModalBody"></div>

        <div class="skillsModalFooter">
            <div>
                <div class="form-check mb-0">
                    <input class="form-check-input" id="readyToGraduateInput" type="checkbox">
                    <label class="form-check-label" for="readyToGraduateInput">Mark as eligible to graduate</label>
                </div>
                <p class="skillsModalNote">You can save skills one at a time. A student can only be marked as eligible once the bar reaches the passing grade.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary" id="checklistModalDoneButton" type="button">Cancel</button>
                <button class="btn btn-primary" id="checklistModalSaveButton" type="button">Save</button>
            </div>
        </div>

    </div>
</div>


<!-- TIME OUT MODAL -->
<div class="modalOverlay" id="timeModalOverlay" hidden>
    <div class="skillsModal isSmall" id="timeModal" role="dialog" aria-modal="true" aria-labelledby="timeModalTitle">

        <div class="skillsModalHeader">
            <h5 id="timeModalTitle">Record Time Out</h5>
            <button class="skillsModalClose" id="timeModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="skillsModalBody">
            <p class="skillsTimeName" id="timeModalRecordName"></p>
            <p class="skillsModalSub" id="timeModalMeta" hidden></p>

            <label class="skillsTimeLabel" id="timeModalLabel" for="timeModalInput">Current Time</label>
            <input class="skillsTimeInput" id="timeModalInput" type="time">
        </div>

        <div class="skillsModalFooter">
            <button class="btn btn-outline-primary" id="timeModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="timeModalSaveButton" type="button">Save</button>
        </div>

    </div>
</div>


<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    /* =========================================================
       CONFIG
    ========================================================= */

    const URLS = {
        timeOut: '/coach/attendance/students/time-out',
        timeInLate: '/coach/attendance/students/time-in-late',
        absent: '/coach/skills-assessment/absent',
        removeMark: '/coach/skills-assessment/remove-mark',
        data: '/coach/skills-assessment/data',
        save: '/coach/skills-assessment'
    };

    // "key" matches the field names the server returns.
    const COLUMNS = [
        { label: 'Student Name', key: 'name', isNameColumn: true },
        { label: 'Course Level', key: 'course' },
        { label: 'Date', key: 'date', isDate: true },
        { label: 'Time In', key: 'time_in', isTime: true },
        { label: 'Time Out', key: 'time_out', isTime: true },
        { label: 'Status', key: 'state', isState: true },
        { label: 'Assessment', key: 'assessment' }
    ];

    const SEARCH_KEYS = ['name', 'course', 'state', 'assessment'];

    const REFRESH_MS = 10000;   // auto-refresh every 10 seconds

    // The stored attendance_state stays 'Expecting'; this is only the wording people read.
    const EXPECTING_LABEL = 'Expected';

    /* =========================================================
       ELEMENTS + STATE
    ========================================================= */

    const $ = id => document.getElementById(id);

    const el = {
        description: $('skillsDescription'),

        tableHead: $('skillsTableHead'),
        tableBody: $('skillsTableBody'),

        classFilter: $('classFilterDropdown'),

        search: $('skillsSearch'),

        timeOutButton: $('recordTimeOutButton'),
        absentButton: $('markAbsentButton'),
        removeMarkButton: $('removeMarkButton'),
        notice: $('sessionNotice'),
        lastUpdated: $('lastUpdated'),
        timeInLateButton: $('timeInLateButton'),
        timeTitle: $('timeModalTitle'),
        viewButton: $('viewChecklistButton'),

        timeOverlay: $('timeModalOverlay'),

        confirmOverlay: $('confirmModalOverlay'),
        confirmTitle: $('confirmModalTitle'),
        confirmMessage: $('confirmModalMessage'),
        confirmOk: $('confirmModalOkButton'),
        confirmCancel: $('confirmModalCancelButton'),
        confirmClose: $('confirmModalCloseButton'),
        timeName: $('timeModalRecordName'),
        timeMeta: $('timeModalMeta'),
        timeLabel: $('timeModalLabel'),
        timeInput: $('timeModalInput'),
        timeClose: $('timeModalCloseButton'),
        timeCancel: $('timeModalCancelButton'),
        timeSave: $('timeModalSaveButton'),

        overlay: $('checklistModalOverlay'),
        modalTitle: $('checklistModalTitle'),
        modalCourse: $('checklistModalCourse'),
        modalBody: $('checklistModalBody'),
        modalClose: $('checklistModalCloseButton'),
        modalDone: $('checklistModalDoneButton'),
        modalSave: $('checklistModalSaveButton'),
        readyInput: $('readyToGraduateInput'),

        progressPassed: $('checklistPassed'),
        progressTotal: $('checklistTotal'),
        progressPercent: $('checklistPercent'),
        progressTrack: $('checklistProgressTrack'),
        progressFill: $('checklistProgressFill'),
        progressMark: $('checklistProgressMark'),
        progressBasis: $('checklistBasis')
    };

    let rows = [];
    let loading = false;
    let loadSeq = 0;            // ignores out-of-order responses
    let selectedId = null;
    let classOptionsBuilt = false;
    let classOptionsKey = '';
    let busy = false;
    let timeModalMode = 'late';
    let canLog = false;         // the coach has timed in (server decides)
    let blockReason = null;
    let passingPercent = 80;    // the server sends the real value with every load

    /* =========================================================
       HELPERS
    ========================================================= */

    function make(tag, { className, text, attributes } = {}) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        if (attributes) Object.entries(attributes).forEach(([k, v]) => node.setAttribute(k, v));
        return node;
    }

    // Local (browser) date as YYYY-MM-DD (toISOString() would give the UTC date).
    function toLocalISODate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function parseISODate(iso) {
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    function formatLongDate(date) {
        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function formatShortDate(date) {
        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    // "HH:MM" (24h) -> "8:05 AM"
    function formatTimeLabel(value) {
        if (!value) return '';
        const [h, m] = value.split(':').map(Number);
        const ref = new Date();
        ref.setHours(h, m, 0, 0);
        return ref.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    // Value of a shared dropdown (assets/js/fbss-dropdown.js).
    const dropdownValue = root => window.FBSSDropdown.get(root);

    async function api(url, { method = 'GET', body } = {}) {
        const headers = {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        };
        if (body !== undefined) headers['Content-Type'] = 'application/json';

        const response = await fetch(url, {
            method,
            headers,
            body: body !== undefined ? JSON.stringify(body) : undefined
        });

        let data = {};
        try { data = await response.json(); } catch (e) { /* non-JSON error page */ }

        return { ok: response.ok, status: response.status, data };
    }

    function toastServerErrors(result, fallback) {
        const errors = result.data.errors;

        if (errors) {
            [...new Set(Object.values(errors).flat())].slice(0, 3).forEach(m => showToast(m, 'danger'));
            return;
        }

        showToast(result.data.message || fallback, 'danger');
    }

    /* =========================================================
       HEADER
    ========================================================= */

    // The student was dropped while this page was open (or the server says so):
    // close any open modal and reload, so the row is gone. Returns true when handled.
    async function dropHandled(result) {
        if (!result.data || !result.data.dropped) return false;

        closeChecklistModal();
        closeTimeModal();
        await loadRows();

        return true;
    }

    function renderCurrentDateLine() {
        el.description.textContent = `Current date: ${formatLongDate(new Date())}`;
    }

    /* =========================================================
       CLASS FILTER (only this coach's classes, from the database)
    ========================================================= */

    function buildClassOptions(courses) {
        const items = [{ value: '', label: 'All My Classes' }]
            .concat(courses.map(course => ({ value: String(course.id), label: course.name })));

        window.FBSSDropdown.setOptions(el.classFilter, items, dropdownValue(el.classFilter));
        classOptionsBuilt = true;
    }

    /* =========================================================
       TABLE
    ========================================================= */

    // attendance_state -> the text people read
    const stateLabel = state => state === 'Expecting' ? EXPECTING_LABEL : (state ?? '');

    // 'Absent' | 'Expected' for a student who only has a mark (no Time In), else null.
    function markLabelOf(record) {
        if (!record || record.time_in) return null;
        if (record.state === 'Absent') return 'Absent';
        return record.is_expecting ? EXPECTING_LABEL : null;
    }

    function getSelectedRecord() {
        return selectedId === null ? null : rows.find(r => r.id === selectedId) || null;
    }

    function renderNameCell(record) {
        const wrapper = make('span', { className: 'skillsNameCell' });
        wrapper.append(
            make('span', { className: `skillsStatusDot ${record.time_in ? 'present' : (record.is_expecting ? 'expecting' : 'absent')}` }),
            document.createTextNode(record.name)
        );
        return wrapper;
    }

    function renderCell(column, record) {
        if (column.isNameColumn) return renderNameCell(record);

        let value;
        if (column.isTime) {
            value = formatTimeLabel(record[column.key]);
        } else if (column.isDate) {
            value = record[column.key] ? formatShortDate(parseISODate(record[column.key])) : '';
        } else if (column.isState) {
            value = stateLabel(record[column.key]);
        } else {
            value = record[column.key] ?? '';
        }

        return value
            ? document.createTextNode(value)
            : make('span', { className: 'skillsEmptyValue', text: column.isTime ? '-' : '--' });
    }

    function visibleRows() {
        const term = el.search.value.trim().toLowerCase();

        if (!term) return rows;

        return rows.filter(record =>
            SEARCH_KEYS.some(key => String(key === 'state' ? stateLabel(record.state) : (record[key] ?? '')).toLowerCase().includes(term))
        );
    }

    // Search: re-filter what is already loaded, and drop the selection if that row is hidden.
    function applyLocalFilters() {
        if (selectedId !== null && !visibleRows().some(r => r.id === selectedId)) selectedId = null;

        updateToolbarState();
        renderTable();
    }

    function renderTable() {
        const list = visibleRows();

        el.tableHead.replaceChildren();
        el.tableBody.replaceChildren();

        const headerRow = make('tr');
        headerRow.append(make('th', { text: 'No.', attributes: { scope: 'col' } }));
        COLUMNS.forEach(c => headerRow.append(make('th', { text: c.label, attributes: { scope: 'col' } })));
        el.tableHead.append(headerRow);

        if (loading || !list.length) {
            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'skillsEmptyState',
                text: loading
                    ? 'Loading...'
                    : (rows.length ? 'No matching students found.' : 'No students are logged, Expected or Absent for you today.'),
                attributes: { colSpan: COLUMNS.length + 1 }
            }));
            el.tableBody.append(emptyRow);
            return;
        }

        list.forEach((record, index) => {
            const tableRow = make('tr', { className: 'skillsRow' });
            if (selectedId === record.id) tableRow.classList.add('selectedRow');
            tableRow.addEventListener('click', () => selectRow(record.id));

            tableRow.append(make('td', { text: String(index + 1) }));

            COLUMNS.forEach(column => {
                const cell = make('td');
                cell.append(renderCell(column, record));
                tableRow.append(cell);
            });

            el.tableBody.append(tableRow);
        });
    }

    function selectRow(id) {
        selectedId = selectedId === id ? null : id;
        updateToolbarState();
        renderTable();
    }

    function updateToolbarState() {
        const record = getSelectedRecord();

        // Attendance actions need the coach to be timed in; the checklist does not.
        // A graduated student is closed: no attendance button works on that row.
        const graduated = Boolean(record && record.graduated);
        const open = canLog && !busy && !graduated;
        const mark = markLabelOf(record);

        el.viewButton.disabled = busy || !record;
        el.timeInLateButton.disabled = !open || !record || !record.is_expecting;
        el.timeOutButton.disabled = !open || !record || !record.time_in || !!record.time_out;
        el.absentButton.disabled = !open || !record || !!record.time_in || record.state === 'Absent';

        el.removeMarkButton.disabled = !open || !mark;
        el.removeMarkButton.textContent = mark ? `Remove ${mark}` : 'Remove Mark';

        el.notice.hidden = canLog || !blockReason;
        if (blockReason && el.notice.textContent !== blockReason) el.notice.textContent = blockReason;
    }

    /* =========================================================
       LOAD DATA (the class filter is sent to the server)
    ========================================================= */

    async function loadRows({ keepSelection = false, silent = false } = {}) {
        const seq = ++loadSeq;

        if (!keepSelection) selectedId = null;

        // A silent (auto) refresh swaps the data in place without a "Loading..." flash.
        if (!silent) {
            loading = true;
            updateToolbarState();
            renderTable();
        }

        // Only today's attendance records are listed (the browser's local date).
        const params = new URLSearchParams({ date: toLocalISODate(new Date()) });
        const courseId = dropdownValue(el.classFilter);
        if (courseId) params.set('course_id', courseId);

        try {
            const result = await api(`${URLS.data}?${params}`);
            if (seq !== loadSeq) return;   // a newer request replaced this one

            loading = false;

            if (!result.ok) {
                if (!silent) {
                    rows = [];
                    toastServerErrors(result, 'Unable to load students.');
                }
            } else {
                rows = result.data.rows || [];
                canLog = Boolean(result.data.can_log);
                blockReason = result.data.block_reason || null;
                passingPercent = Number(result.data.passing_percent) || passingPercent;
                el.lastUpdated.textContent = `Auto-refreshes every ${REFRESH_MS / 1000}s. Updated ${new Date().toLocaleTimeString('en-US')}`;

                // Rebuilt only when the list of today's classes changes.
                const courses = result.data.courses || [];
                const coursesKey = courses.map(c => c.id).join(',');

                if (!classOptionsBuilt || coursesKey !== classOptionsKey) {
                    classOptionsKey = coursesKey;
                    buildClassOptions(courses);
                }
                if (selectedId !== null && !rows.some(r => r.id === selectedId)) selectedId = null;
            }
        } catch (error) {
            if (seq !== loadSeq) return;
            console.error(error);
            loading = false;

            if (!silent) {
                rows = [];
                showToast('Something went wrong while loading students.', 'danger');
            }
        }

        updateToolbarState();
        renderTable();
    }

    el.search.addEventListener('input', applyLocalFilters);
    el.classFilter.addEventListener('dropdown:change', () => loadRows());
    // Keeps the list current by itself (other coaches or the admin may change
    // attendance). Skipped while the tab is hidden, a modal is open or a save is running.
    let refreshing = false;

    async function refreshQuietly() {
        if (document.hidden || busy || refreshing || loading
            || !el.timeOverlay.hidden || !el.overlay.hidden || !el.confirmOverlay.hidden) return;

        refreshing = true;
        try {
            await loadRows({ keepSelection: true, silent: true });
        } finally {
            refreshing = false;
        }
    }

    document.addEventListener('visibilitychange', refreshQuietly);
    setInterval(refreshQuietly, REFRESH_MS);

    /* =========================================================
       CHECKLIST MODAL
       Each skill gets a native Pass / Failed radio pair. The picks are read
       straight from the radios when Save is pressed (nothing is kept in JS).
    ========================================================= */

    function renderSkillRow(skill) {
        const row = make('div', { className: 'skillsSkillRow', attributes: { 'data-skill-id': skill.id } });
        row.append(make('span', { className: 'skillsSkillName', text: skill.name }));

        const group = make('div', { className: 'btn-group btn-group-sm', attributes: { role: 'group', 'aria-label': `Result for ${skill.name}` } });

        [
            { value: 'pass', label: 'Pass', variant: 'success' },
            { value: 'failed', label: 'Failed', variant: 'danger' }
        ].forEach(option => {
            const inputId = `skill_${skill.id}_${option.value}`;

            const input = make('input', {
                className: 'btn-check',
                attributes: { type: 'radio', name: `skill_${skill.id}`, id: inputId, autocomplete: 'off' }
            });
            input.checked = skill.result === option.value;
            input.addEventListener('change', () => {
                row.classList.remove('isMissing');
                updateChecklistProgress();
            });

            const label = make('label', {
                className: `btn btn-outline-${option.variant}`,
                text: option.label,
                attributes: { for: inputId }
            });

            group.append(input, label);
        });

        row.append(group);
        return row;
    }

    function renderChecklist(record) {
        el.modalBody.replaceChildren();

        const checklist = record.checklist || [];

        if (!checklist.length) {
            el.modalBody.append(make('p', {
                className: 'skillsChecklistEmpty',
                text: 'No skill categories have been set up for this course yet.'
            }));
            return;
        }

        checklist.forEach(category => {
            const section = make('div', { className: 'skillsCategory' });

            const header = make('div', { className: 'skillsCategoryHeader' });
            header.append(
                make('h6', { className: 'skillsCategoryTitle', text: category.name }),
                make('span', { className: 'skillsCategoryCount', text: `${category.skills.length} skill${category.skills.length === 1 ? '' : 's'}` })
            );
            section.append(header);

            const list = make('div', { className: 'skillsSkillList' });
            category.skills.forEach(skill => list.append(renderSkillRow(skill)));
            section.append(list);

            el.modalBody.append(section);
        });
    }

    let checklistReadOnly = false;   // a graduated student's checklist can only be viewed

    // Passing-grade bar. Counts the radios as they are right now (not only what is
    // saved), so it moves as the coach picks. Only when it reaches the passing grade
    // can "Mark as eligible to graduate" be ticked; the server checks the same rule.
    function updateChecklistProgress() {
        const skillRows = el.modalBody.querySelectorAll('.skillsSkillRow');
        const total = skillRows.length;
        let passed = 0;

        skillRows.forEach(row => {
            if (row.querySelector('input[id$="_pass"]:checked')) passed++;
        });

        const percent = total > 0 ? Math.round((passed / total) * 1000) / 10 : 0;
        const meets = total > 0 && passed * 100 >= passingPercent * total;
        const needed = Math.ceil((total * passingPercent) / 100);

        el.progressPassed.textContent = String(passed);
        el.progressTotal.textContent = String(total);
        el.progressPercent.textContent = `${percent}%`;
        el.progressFill.style.width = `${percent}%`;
        el.progressMark.style.left = `${passingPercent}%`;
        el.progressTrack.setAttribute('aria-valuenow', String(percent));
        el.progressTrack.classList.toggle('isPassing', meets);

        el.progressBasis.classList.toggle('isPassing', meets);
        el.progressBasis.textContent = total === 0
            ? 'No skills to assess yet.'
            : (meets
                ? `Passing grade (${passingPercent}%) reached. You can mark this student as eligible to graduate.`
                : `Passing grade: ${passingPercent}%. Needs ${needed} of ${total} skills passed to be marked as eligible (${passed} now).`);

        // Below the passing grade the box is locked and cleared.
        if (checklistReadOnly) {
            el.readyInput.disabled = true;   // graduated: shown as it was saved
            return;
        }

        el.readyInput.disabled = !meets;
        if (!meets) el.readyInput.checked = false;
    }

    function openChecklistModal() {
        const record = getSelectedRecord();
        if (!record) return;

        el.modalTitle.textContent = record.name;
        checklistReadOnly = Boolean(record.graduated);

        const courseLine = record.course ? `${record.course} · Core Skills Checklist` : 'Core Skills Checklist';
        el.modalCourse.textContent = checklistReadOnly
            ? `${courseLine} · Graduated${record.graduated_at ? ' on ' + formatShortDate(parseISODate(record.graduated_at)) : ''} (view only)`
            : courseLine;
        el.readyInput.checked = Boolean(record.ready_to_graduate);
        el.modalSave.disabled = checklistReadOnly || !(record.checklist || []).length;

        renderChecklist(record);
        el.modalBody.querySelectorAll('input.btn-check').forEach(input => { input.disabled = checklistReadOnly; });
        updateChecklistProgress();

        el.overlay.hidden = false;
        el.modalBody.scrollTop = 0;
    }

    function closeChecklistModal() {
        el.overlay.hidden = true;
        window.FBSSDropdown.close();
    }

    async function saveChecklist() {
        const record = getSelectedRecord();
        if (!record || busy || checklistReadOnly) return;

        // Read the picks straight from the radios.
        const results = {};
        const needsAll = el.readyInput.checked;   // "eligible to graduate" (only possible at the passing grade)

        el.modalBody.querySelectorAll('.skillsSkillRow').forEach(row => {
            const picked = row.querySelector('input.btn-check:checked');

            if (picked) {
                results[row.dataset.skillId] = picked.id.endsWith('_pass') ? 'pass' : 'failed';
            }
        });

        if (!needsAll && !Object.keys(results).length) {
            showToast('Mark at least one skill as Pass or Failed before saving.', 'danger');
            return;
        }

        busy = true;
        el.modalSave.disabled = true;
        el.modalSave.textContent = 'Saving...';

        try {
            const result = await api(`${URLS.save}/${record.id}`, {
                method: 'PUT',
                body: { results, ready_to_graduate: el.readyInput.checked }
            });

            if (result.ok) {
                closeChecklistModal();
                showToast(`Skills assessment saved for ${record.name}.`, 'success');
                await loadRows({ keepSelection: true });
            } else {
                toastServerErrors(result, 'Unable to save the assessment.');
                await dropHandled(result);
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving the assessment.', 'danger');
        } finally {
            busy = false;
            el.modalSave.disabled = false;
            el.modalSave.textContent = 'Save';
        }
    }

    /* =========================================================
       TIME IN (LATE) - only for students marked Expecting.
       Reuses the small time modal; the server turns the status into Present-Late.
    ========================================================= */

    function currentTimeInputValue() {
        const now = new Date();
        return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    }

    function openTimeInLateModal() {
        timeModalMode = 'late';
        const record = getSelectedRecord();
        if (!record || !record.is_expecting) return;

        el.timeTitle.textContent = 'Record Time In (Late)';
        el.timeName.replaceChildren(document.createTextNode('Recording for '), make('strong', { text: record.name }));
        el.timeMeta.textContent = 'The status will change to Present-Late.';
        el.timeMeta.hidden = false;

        el.timeInput.value = currentTimeInputValue();
        el.timeLabel.textContent = 'Current Time';
        el.timeLabel.classList.remove('isDimmed');

        el.timeOverlay.hidden = false;
        el.timeInput.focus();
    }

    function openTimeOutModal() {
        const record = getSelectedRecord();
        if (!record || !record.time_in || record.time_out) return;

        timeModalMode = 'out';
        el.timeTitle.textContent = 'Record Time Out';
        el.timeName.replaceChildren(document.createTextNode('Recording for '), make('strong', { text: record.name }));
        el.timeMeta.textContent = 'Time Out must be later than the recorded Time In.';
        el.timeMeta.hidden = false;
        el.timeInput.value = currentTimeInputValue();
        el.timeLabel.textContent = 'Current Time';
        el.timeLabel.classList.remove('isDimmed');
        el.timeOverlay.hidden = false;
        el.timeInput.focus();
    }

    /* Confirm popup (instead of the browser's confirm box). Resolves true / false. */
    let confirmResolve = null;

    function askConfirm({ title, message, confirmText }) {
        if (confirmResolve) closeConfirm(false);

        el.confirmTitle.textContent = title;
        el.confirmMessage.textContent = message;
        el.confirmOk.textContent = confirmText;
        el.confirmOverlay.hidden = false;
        el.confirmOk.focus();

        return new Promise(resolve => { confirmResolve = resolve; });
    }

    function closeConfirm(answer) {
        el.confirmOverlay.hidden = true;

        if (confirmResolve) {
            confirmResolve(answer);
            confirmResolve = null;
        }
    }

    el.confirmOk.addEventListener('click', () => closeConfirm(true));
    el.confirmCancel.addEventListener('click', () => closeConfirm(false));
    el.confirmClose.addEventListener('click', () => closeConfirm(false));

    async function markSelectedAbsent() {
        const record = getSelectedRecord();
        if (busy || !record || record.time_in || record.state === 'Absent') return;

        if (!(await askConfirm({
            title: 'Mark Absent',
            message: `Mark ${record.name} as absent for today?`,
            confirmText: 'Mark Absent'
        }))) return;

        busy = true;
        updateToolbarState();
        try {
            const result = await api(URLS.absent, {
                method: 'POST',
                body: { enrollment_id: record.id, date: toLocalISODate(new Date()) }
            });
            if (result.ok) {
                showToast(`${record.name} marked as absent.`, 'success');
                await loadRows({ keepSelection: true });
            } else {
                toastServerErrors(result, 'Unable to mark the student absent.');
                await dropHandled(result);
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while marking the student absent.', 'danger');
        } finally {
            busy = false;
            updateToolbarState();
        }
    }

    // Removes the Expected / Absent mark. The student then leaves this list.
    async function removeSelectedMark() {
        const record = getSelectedRecord();
        const mark = markLabelOf(record);
        if (busy || !canLog || !mark) return;

        busy = true;
        updateToolbarState();

        try {
            const result = await api(URLS.removeMark, {
                method: 'POST',
                body: { enrollment_id: record.id, date: toLocalISODate(new Date()) }
            });

            if (result.ok) {
                showToast(`${mark} removed for ${record.name}.`, 'success');
                await loadRows({ keepSelection: true, silent: true });
            } else {
                toastServerErrors(result, `Unable to remove ${mark}.`);

                if (await dropHandled(result)) return;

                if (result.status === 403 || result.status === 404) await loadRows({ keepSelection: true, silent: true });
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while removing the mark.', 'danger');
        } finally {
            busy = false;
            updateToolbarState();
        }
    }

    function closeTimeModal() {
        el.timeOverlay.hidden = true;
    }

    async function saveTimeInLate() {
        const record = getSelectedRecord();
        if (busy) return;

        if (!record || (timeModalMode === 'late' && !record.is_expecting) ||
            (timeModalMode === 'out' && (!record.time_in || record.time_out))) {
            closeTimeModal();
            return;
        }

        const time = el.timeInput.value;   // "HH:MM"

        if (!time) {
            showToast('Please choose a time.', 'danger');
            return;
        }

        busy = true;
        el.timeSave.disabled = true;
        el.timeSave.textContent = 'Saving...';
        updateToolbarState();

        try {
            const isTimeOut = timeModalMode === 'out';
            const result = await api(isTimeOut ? URLS.timeOut : URLS.timeInLate, {
                method: 'POST',
                body: { enrollment_id: record.id, date: toLocalISODate(new Date()), time }
            });

            if (result.ok) {
                closeTimeModal();
                showToast(`${isTimeOut ? 'Time Out' : 'Late Time In'} recorded for ${record.name}.`, 'success');
                await loadRows({ keepSelection: true });
            } else {
                toastServerErrors(result, 'Unable to save the time.');
                await dropHandled(result);

                // e.g. the admin timed this coach out while the modal was open.
                if (result.status === 403) {
                    closeTimeModal();
                    await loadRows({ keepSelection: true });
                }
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving the time.', 'danger');
        } finally {
            busy = false;
            el.timeSave.disabled = false;
            el.timeSave.textContent = 'Save';
            updateToolbarState();
        }
    }

    el.timeInLateButton.addEventListener('click', openTimeInLateModal);
    el.timeOutButton.addEventListener('click', openTimeOutModal);
    el.absentButton.addEventListener('click', markSelectedAbsent);
    el.removeMarkButton.addEventListener('click', removeSelectedMark);
    el.timeClose.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.timeCancel.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.timeSave.addEventListener('click', saveTimeInLate);

    // Greys out "Current Time" once the coach edits the field.
    el.timeInput.addEventListener('input', () => el.timeLabel.classList.add('isDimmed'));

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !el.confirmOverlay.hidden) closeConfirm(false);
        if (event.key === 'Escape' && !el.timeOverlay.hidden && !busy) closeTimeModal();
    });

    el.viewButton.addEventListener('click', openChecklistModal);
    el.modalSave.addEventListener('click', saveChecklist);
    el.modalClose.addEventListener('click', () => { if (!busy) closeChecklistModal(); });
    el.modalDone.addEventListener('click', () => { if (!busy) closeChecklistModal(); });

    // Clicking outside the card does NOT close the modal; Escape does.
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !el.overlay.hidden && !busy) closeChecklistModal();
    });

    /* =========================================================
       INIT
    ========================================================= */

    renderCurrentDateLine();
    updateToolbarState();
    loadRows();

})();
</script>