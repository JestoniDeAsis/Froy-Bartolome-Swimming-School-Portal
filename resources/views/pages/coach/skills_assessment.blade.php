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
</style>


<section class="skillsContainer" id="skillsContainer" aria-labelledby="skillsTitle">

    <header class="skillsHeader">
        <h2 class="mb-1" id="skillsTitle">Skills Assessment</h2>
        <p class="text-muted mb-0" id="skillsDescription"></p>
        <p class="text-muted small mb-0" id="skillsHint">
            Shows today's students you have an attendance record for (timed in, or marked Expecting). Select a student to assess their course skills; use Time In (Late) for a student marked Expecting.
        </p>
    </header>

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
            <button class="btn btn-primary" id="timeInLateButton" type="button" disabled title="Only for students marked Expecting">Time In (Late)</button>
            <button class="btn btn-primary" id="recordTimeOutButton" type="button" disabled title="Time Out is available once the student has a Time In">Time Out</button>
            <button class="btn btn-outline-danger" id="markAbsentButton" type="button" disabled title="Mark a student absent if they have not timed in">Mark Absent</button>
            <button class="btn btn-primary" id="viewChecklistButton" type="button" disabled>View Checklist</button>
            <button class="btn btn-outline-primary" id="refreshButton" type="button">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh
            </button>
        </div>

    </div>

    <!-- TABLE -->
    <div class="skillsTableContainer" id="skillsTableContainer">
        <table class="table skillsTable" id="skillsTable">
            <thead id="skillsTableHead"></thead>
            <tbody id="skillsTableBody"></tbody>
        </table>
    </div>

</section>


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

        <div class="skillsModalBody" id="checklistModalBody"></div>

        <div class="skillsModalFooter">
            <div>
                <div class="form-check mb-0">
                    <input class="form-check-input" id="readyToGraduateInput" type="checkbox">
                    <label class="form-check-label" for="readyToGraduateInput">Mark as eligible to graduate</label>
                </div>
                <p class="skillsModalNote">You can save skills one at a time. Every skill needs a Pass or Failed only when marking as eligible to graduate.</p>
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
        { label: 'Status', key: 'state' },
        { label: 'Assessment', key: 'assessment' }
    ];

    const SEARCH_KEYS = ['name', 'course', 'state', 'assessment'];

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
        timeInLateButton: $('timeInLateButton'),
        timeTitle: $('timeModalTitle'),
        viewButton: $('viewChecklistButton'),

        timeOverlay: $('timeModalOverlay'),
        timeName: $('timeModalRecordName'),
        timeMeta: $('timeModalMeta'),
        timeLabel: $('timeModalLabel'),
        timeInput: $('timeModalInput'),
        timeClose: $('timeModalCloseButton'),
        timeCancel: $('timeModalCancelButton'),
        timeSave: $('timeModalSaveButton'),
        refreshButton: $('refreshButton'),

        overlay: $('checklistModalOverlay'),
        modalTitle: $('checklistModalTitle'),
        modalCourse: $('checklistModalCourse'),
        modalBody: $('checklistModalBody'),
        modalClose: $('checklistModalCloseButton'),
        modalDone: $('checklistModalDoneButton'),
        modalSave: $('checklistModalSaveButton'),
        readyInput: $('readyToGraduateInput')
    };

    let rows = [];
    let loading = false;
    let loadSeq = 0;            // ignores out-of-order responses
    let selectedId = null;
    let classOptionsBuilt = false;
    let classOptionsKey = '';
    let busy = false;
    let timeModalMode = 'late';

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
            SEARCH_KEYS.some(key => String(record[key] ?? '').toLowerCase().includes(term))
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
                    : (rows.length ? 'No matching students found.' : 'No students are logged or marked Expecting for you today.'),
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

        el.viewButton.disabled = busy || !record;
        el.timeInLateButton.disabled = busy || !record || !record.is_expecting;
        el.timeOutButton.disabled = busy || !record || !record.time_in || !!record.time_out;
        el.absentButton.disabled = busy || !record || !!record.time_in || record.state === 'Absent';
    }

    /* =========================================================
       LOAD DATA (the class filter is sent to the server)
    ========================================================= */

    async function loadRows({ keepSelection = false } = {}) {
        const seq = ++loadSeq;

        if (!keepSelection) selectedId = null;

        loading = true;
        updateToolbarState();
        renderTable();

        // Only today's attendance records are listed (the browser's local date).
        const params = new URLSearchParams({ date: toLocalISODate(new Date()) });
        const courseId = dropdownValue(el.classFilter);
        if (courseId) params.set('course_id', courseId);

        try {
            const result = await api(`${URLS.data}?${params}`);
            if (seq !== loadSeq) return;   // a newer request replaced this one

            loading = false;

            if (!result.ok) {
                rows = [];
                toastServerErrors(result, 'Unable to load students.');
            } else {
                rows = result.data.rows || [];

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
            rows = [];
            showToast('Something went wrong while loading students.', 'danger');
        }

        updateToolbarState();
        renderTable();
    }

    el.search.addEventListener('input', applyLocalFilters);
    el.classFilter.addEventListener('dropdown:change', () => loadRows());
    el.refreshButton.addEventListener('click', () => loadRows({ keepSelection: true }));

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
            input.addEventListener('change', () => row.classList.remove('isMissing'));

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

    function openChecklistModal() {
        const record = getSelectedRecord();
        if (!record) return;

        el.modalTitle.textContent = record.name;
        el.modalCourse.textContent = record.course ? `${record.course} · Core Skills Checklist` : 'Core Skills Checklist';
        el.readyInput.checked = Boolean(record.ready_to_graduate);
        el.modalSave.disabled = !(record.checklist || []).length;

        renderChecklist(record);

        el.overlay.hidden = false;
        el.modalBody.scrollTop = 0;
    }

    function closeChecklistModal() {
        el.overlay.hidden = true;
        window.FBSSDropdown.close();
    }

    async function saveChecklist() {
        const record = getSelectedRecord();
        if (!record || busy) return;

        // Read the picks straight from the radios.
        const results = {};
        let missing = 0;
        const needsAll = el.readyInput.checked;   // only "eligible to graduate" requires every skill

        el.modalBody.querySelectorAll('.skillsSkillRow').forEach(row => {
            const picked = row.querySelector('input.btn-check:checked');
            row.classList.toggle('isMissing', needsAll && !picked);

            if (picked) {
                results[row.dataset.skillId] = picked.id.endsWith('_pass') ? 'pass' : 'failed';
            } else {
                missing++;
            }
        });

        if (needsAll && missing > 0) {
            showToast(`To mark as eligible to graduate, every skill needs a Pass or Failed. ${missing} still ${missing === 1 ? 'has' : 'have'} no remark.`, 'danger');
            return;
        }

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

    async function markSelectedAbsent() {
        const record = getSelectedRecord();
        if (busy || !record || record.time_in || record.state === 'Absent') return;
        if (!window.confirm(`Mark ${record.name} as absent for today?`)) return;

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
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while marking the student absent.', 'danger');
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
    el.timeClose.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.timeCancel.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.timeSave.addEventListener('click', saveTimeInLate);

    // Greys out "Current Time" once the coach edits the field.
    el.timeInput.addEventListener('input', () => el.timeLabel.classList.add('isDimmed'));

    document.addEventListener('keydown', event => {
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