@extends('layouts.toast')

<style>
/* =========================================================
   Attendance (admin)
   Same skeleton as Registers/Coaches: header, tabs, toolbar,
   table, shared .registerDropdown from our_theme.css.
========================================================= */

.attendanceContainer {
    width: 100%;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.attendanceHeader {
    flex: 0 0 auto;
    margin-bottom: 1rem;
}

/* Tabs */
.attendanceTabsContainer {
    flex: 0 0 auto;
    display: flex;
    gap: .5rem;
    margin-bottom: .75rem;
}

.attendanceTab { min-width: 150px; }

/* Toolbar */
.attendanceToolbar {
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

.attendanceFilterGroup {
    display: flex;
    flex-direction: column;
    gap: .35rem;
}

.attendanceFilterLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}

/* Sizes come from the shared dropdown variables (our_theme.css). */
.attendanceClassFilter { --dd-min-width: 160px; }

/* Same fixed width whether "All", "Logged In" or "Not Logged In" is
   selected, so the control doesn't shrink and grow as the choice changes. */
.attendanceLoggedFilter { --dd-min-width: 160px; }

/* Search */
.attendanceSearchContainer {
    position: relative;
    width: min(260px, 100%);
}

.attendanceSearchIcon {
    position: absolute;
    top: 50%;
    left: .75rem;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.attendanceSearchInput {
    height: 40px;
    box-sizing: border-box;
    border-radius: 20px;
    padding-left: 2.25rem;
}

/* Date picker: a styled button with a real <input type="date"> underneath.
   The input ignores pointer events; the button opens it via showPicker(). */
.attendanceDatePicker { position: relative; }

.attendanceDateButton {
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

.attendanceDateButton i { margin-left: auto; color: #6c757d; }

.attendanceDateInput {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    pointer-events: none;
}

.attendanceToolbarActions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-left: auto;
}

/* Table */
.attendanceTableContainer {
    --attendance-row-height: 49px;
    --attendance-header-height: 49px;
    --attendance-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;
    max-height: calc(var(--attendance-header-height) + (var(--attendance-row-height) * var(--attendance-visible-rows)));
    width: 100%;
    overflow: auto;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    box-sizing: border-box;
}

.attendanceTable {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.attendanceTable thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafb;
    color: #667085;
    font-weight: 600;
    border-bottom: 1px solid #e7ebef;
    box-shadow: 0 1px 0 #e7ebef;
}

.attendanceTable th,
.attendanceTable td { padding: .75rem 1rem; vertical-align: middle; }

.attendanceRow { transition: background-color .1s ease; }
.attendanceRow:hover { background: #f8fafb; }

/* Only selectable tabs have selectable rows. */
.attendanceRow.isSelectable { cursor: pointer; }

.attendanceRow.selectedRow,
.attendanceRow.selectedRow:hover,
.attendanceRow.selectedRow > td { background: #e7f5ff; }

.attendanceEmptyState {
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}

.attendanceNameCell { display: inline-flex; align-items: center; gap: .55rem; }

.attendanceStatusDot { flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%; }
.attendanceStatusDot.present { background: #2f9e44; }
.attendanceStatusDot.absent  { background: #e03131; }

.attendanceEmptyValue { color: #adb5bd; }

@media (max-width: 575.98px) {
    .attendanceContainer { padding: 1rem; }
    .attendanceToolbarActions { margin-left: 0; width: 100%; }
    .attendanceToolbarActions button { flex: 1 1 auto; }
}

/* Time entry modal (backdrop = shared .modalOverlay in our_theme.css) */
.attendanceModal {
    width: 100%;
    max-width: 360px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(16, 24, 40, .18);
    overflow: hidden;
}

.attendanceModalHeader {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e7ebef;
}

.attendanceModalHeader h5 { margin: 0; font-size: 1rem; font-weight: 700; }

.attendanceModalClose {
    border: none;
    background: transparent;
    font-size: 1.25rem;
    line-height: 1;
    color: #667085;
    cursor: pointer;
    padding: 0;
}

.attendanceModalBody { padding: 1.25rem; display: flex; flex-direction: column; gap: .5rem; }

.attendanceModalName { margin: 0 0 .25rem; font-size: .875rem; color: #344054; }
.attendanceModalName strong { color: #101828; }

.attendanceModalMeta { margin: 0 0 .5rem; font-size: .8125rem; color: #667085; }

.attendanceModalLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #101828;
    transition: color .15s ease;
}
.attendanceModalLabel.isDimmed { color: #adb5bd; }

.attendanceModalInput {
    height: 44px;
    padding: 0 .9rem;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    font-size: 1rem;
    color: #212529;
    background: #fff;
}

.attendanceModalInput:focus {
    outline: none;
    border-color: #4dabf7;
    box-shadow: 0 0 0 3px rgba(77, 171, 247, .15);
}

.attendanceModalFooter {
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #e7ebef;
}

/* Dots inside the "Attendance" filter options */
.attendanceOptionDot {
    display: inline-block;
    margin-right: .5rem;
    vertical-align: middle;
}

/* Student log modal: wider, only the table area scrolls */
.attendanceModal.isWide {
    max-width: 780px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.attendanceModal.isWide .attendanceModalBody {
    min-height: 0;
    overflow-y: auto;
}

.attendanceLogToolbar {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: .5rem;
}

.attendanceLogFilter { --dd-min-width: 280px; }

.attendanceLogSummary {
    margin: 0;
    font-size: .8125rem;
    color: #667085;
}
</style>


<section class="attendanceContainer" id="attendanceContainer" aria-labelledby="attendanceTitle">

    <header class="attendanceHeader">
        <h2 class="mb-1" id="attendanceTitle">Attendance</h2>
        <p class="text-muted mb-0" id="attendanceDescription"></p>
        <p class="text-muted small mb-0" id="attendanceHint"></p>
    </header>

    <!-- TABS -->
    <nav class="attendanceTabsContainer" id="attendanceTabs" aria-label="Attendance sections">
        <button class="btn btn-primary attendanceTab active" type="button" id="activeCoachesTabButton" data-attendance-tab="coaches" aria-selected="true">
            Active Coaches
        </button>
        <button class="btn btn-outline-primary attendanceTab" type="button" id="activeStudentsTabButton" data-attendance-tab="students" aria-selected="false">
            Active Students
        </button>
        <button class="btn btn-outline-primary attendanceTab" type="button" id="activeAthletesTabButton" data-attendance-tab="athletes" aria-selected="false">
            Active Athletes
        </button>
    </nav>

    <!-- TOOLBAR -->
    <div class="attendanceToolbar">

        <!-- Select Class (options are built from the database) -->
        <div class="attendanceFilterGroup" id="classFilterGroup">
            <label class="attendanceFilterLabel" id="classFilterLabel">Select Class</label>

            <div class="registerDropdown attendanceClassFilter" id="classFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="classFilterToggle" type="button" aria-expanded="false" aria-controls="classFilterOptions">
                    <span id="classFilterSelected">All Classes</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="classFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All Classes</button>
                </div>

                <input id="classFilterInput" name="classFilter" type="hidden" value="">
            </div>
        </div>

        <!-- Attendance Date -->
        <div class="attendanceFilterGroup" id="attendanceDateGroup">
            <label class="attendanceFilterLabel" for="attendanceDateInput">Attendance Date</label>

            <div class="attendanceDatePicker">
                <button class="attendanceDateButton" id="attendanceDateButton" type="button">
                    <span id="attendanceDateSelected">Today</span>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                </button>
                <input class="attendanceDateInput" id="attendanceDateInput" type="date" aria-label="Attendance date" tabindex="-1">
            </div>
        </div>

        <!-- Attendance filter: logged in that day or not (students tab only) -->
        <div class="attendanceFilterGroup" id="loggedFilterGroup">
            <label class="attendanceFilterLabel" id="loggedFilterLabel">Attendance</label>

            <div class="registerDropdown attendanceLoggedFilter" id="loggedFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="loggedFilterToggle" type="button" aria-expanded="false" aria-controls="loggedFilterOptions">
                    <span id="loggedFilterSelected">All</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="loggedFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="all">All</button>
                    <button class="registerDropdownOption" type="button" data-value="in">
                        <span class="attendanceStatusDot present attendanceOptionDot"></span>Logged In
                    </button>
                    <button class="registerDropdownOption" type="button" data-value="out">
                        <span class="attendanceStatusDot absent attendanceOptionDot"></span>Not Logged In
                    </button>
                </div>

                <input id="loggedFilterInput" name="loggedFilter" type="hidden" value="all">
            </div>
        </div>

        <!-- Search -->
        <div class="attendanceFilterGroup" id="attendanceSearchGroup">
            <label class="attendanceFilterLabel" for="attendanceSearch">Search</label>

            <div class="attendanceSearchContainer">
                <i class="bi bi-search attendanceSearchIcon" aria-hidden="true"></i>
                <input class="form-control attendanceSearchInput" id="attendanceSearch" type="search"
                       placeholder="Search" autocomplete="off" aria-label="Search attendance">
            </div>
        </div>

        <!-- Time actions (coaches tab only) -->
        <div class="attendanceToolbarActions" id="attendanceToolbarActions">
            <button class="btn btn-primary" id="recordTimeInButton" type="button" disabled>Time In</button>
            <button class="btn btn-primary" id="recordTimeOutButton" type="button" disabled>Time Out</button>
            <button class="btn btn-primary" id="coachViewLogsButton" type="button" disabled>View Logs</button>
        </div>

        <!-- Student actions (students tab only) -->
        <div class="attendanceToolbarActions" id="studentToolbarActions">
            <button class="btn btn-primary" id="viewLogsButton" type="button" disabled>View Logs</button>
        </div>

    </div>

    <!-- TABLE -->
    <div class="attendanceTableContainer" id="attendanceTableContainer">
        <table class="table attendanceTable" id="attendanceTable">
            <thead id="attendanceTableHead"></thead>
            <tbody id="attendanceTableBody"></tbody>
        </table>
    </div>

</section>


<!-- TIME ENTRY MODAL (Time In and Time Out) -->
<div class="modalOverlay" id="timeModalOverlay" hidden>
    <div class="attendanceModal" id="timeModal" role="dialog" aria-modal="true" aria-labelledby="timeModalTitle">

        <div class="attendanceModalHeader">
            <h5 id="timeModalTitle">Record Time In</h5>
            <button class="attendanceModalClose" id="timeModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="attendanceModalBody">
            <p class="attendanceModalName" id="timeModalRecordName"></p>
            <p class="attendanceModalMeta" id="timeModalMeta" hidden></p>

            <label class="attendanceModalLabel" id="timeModalLabel" for="timeModalInput">Current Time</label>
            <input class="attendanceModalInput" id="timeModalInput" type="time">
        </div>

        <div class="attendanceModalFooter">
            <button class="btn btn-outline-primary" id="timeModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="timeModalSaveButton" type="button">Save</button>
        </div>

    </div>
</div>


<!-- STUDENT ATTENDANCE LOG MODAL (view only) -->
<div class="modalOverlay" id="logsModalOverlay" hidden>
    <div class="attendanceModal isWide" id="logsModal" role="dialog" aria-modal="true" aria-labelledby="logsModalTitle">

        <div class="attendanceModalHeader">
            <h5 id="logsModalTitle">Attendance Log</h5>
            <button class="attendanceModalClose" id="logsModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="attendanceModalBody">
            <p class="attendanceModalName" id="logsModalStudent"></p>

            <div class="attendanceLogToolbar">
                <div class="attendanceFilterGroup" id="logCourseFilterGroup">
                    <label class="attendanceFilterLabel">Course</label>

                    <div class="registerDropdown attendanceLogFilter" id="logFilterDropdown" data-fbss-dropdown>
                        <button class="registerDropdownToggle" id="logFilterToggle" type="button" aria-expanded="false" aria-controls="logFilterOptions">
                            <span id="logFilterSelected">Loading...</span>
                            <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                        </button>

                        <div class="registerDropdownOptions" id="logFilterOptions" hidden></div>

                        <input id="logFilterInput" name="logFilter" type="hidden" value="">
                    </div>
                </div>

                <p class="attendanceLogSummary" id="logsSummary"></p>
            </div>

            <div class="attendanceLogTableContainer">
                <table class="table attendanceTable" id="logsTable">
                    <thead id="logsTableHead"></thead>
                    <tbody id="logsTableBody"></tbody>
                </table>
            </div>
        </div>

        <div class="attendanceModalFooter">
            <button class="btn btn-outline-primary" id="logsModalDoneButton" type="button">Close</button>
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
        data: '/admin/attendance/data',
        timeIn: '/admin/attendance/coaches/time-in',
        timeOut: '/admin/attendance/coaches/time-out',
        studentLogs: '/admin/attendance/student-logs',
        athleteLogs: '/admin/attendance/athlete-logs',
        coachLogs: '/admin/attendance/coach-logs'
    };

    // Per-tab columns. "key" matches the field names the server returns.
    const TAB_CONFIGS = {
        coaches: {
            hint: 'Record the Time In and Time Out of each coach, or view the full attendance log. Select a coach to enable the buttons.',
            selectable: true,
            columns: [
                { label: 'Coach Name', key: 'name', isNameColumn: true },
                { label: 'Assigned Classes', key: 'classes' },
                { label: 'Time In', key: 'time_in', isTime: true },
                { label: 'Time Out', key: 'time_out', isTime: true },
                { label: 'Status', key: 'state' }
            ],
            searchKeys: ['name', 'classes', 'state']
        },
        students: {
            hint: 'View only. This is the attendance your coaches logged. Select a student to view their full attendance log.',
            selectable: true,
            columns: [
                { label: 'Student Name', key: 'name', isNameColumn: true },
                { label: 'Coach', key: 'coach' },
                { label: 'Course Level', key: 'course' },
                { label: 'Schedule', key: 'schedule' },
                { label: 'Sessions Completed', key: 'sessions' },
                { label: 'Time In', key: 'time_in', isTime: true },
                { label: 'Time Out', key: 'time_out', isTime: true },
                { label: 'Status', key: 'state' }
            ],
            searchKeys: ['name', 'coach', 'course', 'state']
        },
        athletes: {
            hint: 'View only. Review attendance records for athletes.',
            selectable: true,
            columns: [
                { label: 'Athlete Name', key: 'name', isNameColumn: true },
                { label: 'Time In', key: 'time_in', isTime: true },
                { label: 'Time Out', key: 'time_out', isTime: true },
                { label: 'Status', key: 'state' }
            ],
            searchKeys: ['name', 'state']
        }
    };

    /* =========================================================
       ELEMENTS + STATE
    ========================================================= */

    const $ = id => document.getElementById(id);

    const el = {
        description: $('attendanceDescription'),
        hint: $('attendanceHint'),
        tabs: $('attendanceTabs'),

        tableHead: $('attendanceTableHead'),
        tableBody: $('attendanceTableBody'),

        classFilterGroup: $('classFilterGroup'),
        classFilter: $('classFilterDropdown'),
        classFilterInput: $('classFilterInput'),

        dateButton: $('attendanceDateButton'),
        dateSelected: $('attendanceDateSelected'),
        dateInput: $('attendanceDateInput'),

        search: $('attendanceSearch'),

        loggedFilterGroup: $('loggedFilterGroup'),
        loggedFilterInput: $('loggedFilterInput'),

        studentActions: $('studentToolbarActions'),
        viewLogsButton: $('viewLogsButton'),

        logsOverlay: $('logsModalOverlay'),
        logsStudent: $('logsModalStudent'),
        logsSummary: $('logsSummary'),
        logsTableHead: $('logsTableHead'),
        logsTableBody: $('logsTableBody'),
        logFilter: $('logFilterDropdown'),
        logFilterInput: $('logFilterInput'),
        logsClose: $('logsModalCloseButton'),
        logsDone: $('logsModalDoneButton'),
        logCourseFilterGroup: $('logCourseFilterGroup'),

        timeActions: $('attendanceToolbarActions'),
        timeInButton: $('recordTimeInButton'),
        timeOutButton: $('recordTimeOutButton'),
        coachViewLogsButton: $('coachViewLogsButton'),

        modalOverlay: $('timeModalOverlay'),
        modalTitle: $('timeModalTitle'),
        modalName: $('timeModalRecordName'),
        modalMeta: $('timeModalMeta'),
        modalLabel: $('timeModalLabel'),
        modalInput: $('timeModalInput'),
        modalClose: $('timeModalCloseButton'),
        modalCancel: $('timeModalCancelButton'),
        modalSave: $('timeModalSaveButton')
    };

    let activeTab = 'coaches';
    let rows = [];
    let loading = false;
    let loadSeq = 0;
    let selectedId = null;
    let pendingAction = null;
    let classOptionsBuilt = false;
    let busy = false;
    let logsAnchorId = null;
    let logsSeq = 0;
    let logFilterBuilt = false;

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

    // Local (browser) date as YYYY-MM-DD. toISOString() would give the UTC
    // date, which is "yesterday" for the first 8 hours of the day in the
    // Philippines.
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
        return date.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    function formatShortDate(date) {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric'
        });
    }

    function currentTimeInputValue() {
        const now = new Date();
        return `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    }

    // "HH:MM" (24h) -> "8:05 AM"
    function formatTimeLabel(value) {
        if (!value) return '';
        const [h, m] = value.split(':').map(Number);
        const ref = new Date();
        ref.setHours(h, m, 0, 0);
        return ref.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit'
        });
    }

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
        try {
            data = await response.json();
        } catch (e) {
            /* non-JSON error page */
        }

        return { ok: response.ok, status: response.status, data };
    }

    function toastServerErrors(result, fallback) {
        const errors = result.data.errors;

        if (errors) {
            [...new Set(Object.values(errors).flat())]
                .slice(0, 3)
                .forEach(m => showToast(m, 'danger'));
            return;
        }

        showToast(result.data.message || fallback, 'danger');
    }

    /* =========================================================
       HEADER + DATE PICKER
    ========================================================= */

    function renderCurrentDateLine() {
        el.description.textContent = `Current date: ${formatLongDate(new Date())}`;
    }

    function updateDateLabel() {
        if (!el.dateInput.value) {
            el.dateSelected.textContent = 'Select date';
            return;
        }

        const picked = parseISODate(el.dateInput.value);
        const isToday = el.dateInput.value === toLocalISODate(new Date());

        el.dateSelected.textContent = isToday
            ? `Today - ${formatShortDate(picked)}`
            : formatShortDate(picked);
    }

    function initDatePicker() {
        const today = toLocalISODate(new Date());
        el.dateInput.max = today;
        el.dateInput.value = today;
        updateDateLabel();

        el.dateButton.addEventListener('click', () => {
            if (typeof el.dateInput.showPicker === 'function') {
                el.dateInput.showPicker();
            } else {
                el.dateInput.focus();
                el.dateInput.click();
            }
        });

        el.dateInput.addEventListener('change', () => {
            if (!el.dateInput.value || el.dateInput.value > el.dateInput.max) {
                el.dateInput.value = el.dateInput.max;
            }

            updateDateLabel();
            loadRows();
        });
    }

    /* =========================================================
       CLASS FILTER (options come from the database)
    ========================================================= */

    function buildClassOptions(courses) {
        const items = [{ value: '', label: 'All Classes' }]
            .concat(courses.map(course => ({
                value: String(course.id),
                label: course.name
            })));

        window.FBSSDropdown.setOptions(
            el.classFilter,
            items,
            el.classFilterInput.value
        );

        classOptionsBuilt = true;
    }

    /* =========================================================
       TABLE
    ========================================================= */

    function getSelectedRecord() {
        if (selectedId === null) return null;

        return rows.find(r => String(r.id) === String(selectedId)) || null;
    }

    function renderNameCell(record) {
        const wrapper = make('span', { className: 'attendanceNameCell' });
        wrapper.append(
            make('span', {
                className: `attendanceStatusDot ${record.time_in ? 'present' : 'absent'}`
            }),
            document.createTextNode(record.name)
        );
        return wrapper;
    }

    function renderCell(column, record) {
        if (column.isNameColumn) return renderNameCell(record);

        const value = column.isTime
            ? formatTimeLabel(record[column.key])
            : (record[column.key] ?? '');

        return value
            ? document.createTextNode(value)
            : make('span', {
                className: 'attendanceEmptyValue',
                text: column.isTime ? '-' : '--'
            });
    }

    function visibleRows() {
        const config = TAB_CONFIGS[activeTab];
        const term = el.search.value.trim().toLowerCase();
        const logged = el.loggedFilterInput.value;

        let list = rows;

        // Students tab: green dot = logged in that day, red dot = not.
        if (activeTab === 'students' && logged !== 'all') {
            list = list.filter(record =>
                logged === 'in' ? Boolean(record.time_in) : !record.time_in
            );
        }

        if (!term) return list;

        return list.filter(record =>
            config.searchKeys.some(key =>
                String(record[key] ?? '').toLowerCase().includes(term)
            )
        );
    }

    // Search / attendance filter: re-filter what is already loaded, and drop
    // the selection if that row is no longer visible.
    function applyLocalFilters() {
        if (selectedId !== null && !visibleRows().some(r => String(r.id) === String(selectedId))) {
            selectedId = null;
        }

        updateToolbarState();
        renderTable();
    }

    function renderTable() {
        const config = TAB_CONFIGS[activeTab];
        const list = visibleRows();

        el.tableHead.replaceChildren();
        el.tableBody.replaceChildren();

        const headerRow = make('tr');
        headerRow.append(make('th', {
            text: 'No.',
            attributes: { scope: 'col' }
        }));

        config.columns.forEach(c => {
            headerRow.append(make('th', {
                text: c.label,
                attributes: { scope: 'col' }
            }));
        });

        el.tableHead.append(headerRow);

        if (loading || !list.length) {
            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'attendanceEmptyState',
                text: loading
                    ? 'Loading...'
                    : (rows.length ? 'No matching records found.' : 'No records found.'),
                attributes: { colSpan: config.columns.length + 1 }
            }));
            el.tableBody.append(emptyRow);
            return;
        }

        list.forEach((record, index) => {
            const tableRow = make('tr', { className: 'attendanceRow' });

            if (config.selectable) {
                tableRow.classList.add('isSelectable');

                if (String(selectedId) === String(record.id)) {
                    tableRow.classList.add('selectedRow');
                }

                tableRow.dataset.recordId = String(record.id);
                tableRow.setAttribute('aria-selected', String(
                    String(selectedId) === String(record.id)
                ));
            }

            tableRow.append(make('td', { text: String(index + 1) }));

            config.columns.forEach(column => {
                const cell = make('td');
                cell.append(renderCell(column, record));
                tableRow.append(cell);
            });

            el.tableBody.append(tableRow);
        });
    }

    el.tableBody.addEventListener('click', event => {
        const tableRow = event.target.closest('tr[data-record-id]');

        if (!tableRow || !el.tableBody.contains(tableRow)) return;
        if (!TAB_CONFIGS[activeTab].selectable) return;

        selectRow(tableRow.dataset.recordId);
    });

    function selectRow(id) {
        const clickedId = String(id);
        selectedId = String(selectedId) === clickedId ? null : clickedId;

        updateToolbarState();
        renderTable();
    }

    function updateToolbarState() {
        const record = getSelectedRecord();

        const isCoaches = activeTab === 'coaches';
        const canViewLogs = activeTab === 'students' || activeTab === 'athletes';

        el.timeInButton.disabled = busy || !record || Boolean(record.time_in);
        el.timeOutButton.disabled = busy || !record || !record.time_in || Boolean(record.time_out);
        el.viewLogsButton.disabled = busy || !record || !canViewLogs;
        el.coachViewLogsButton.disabled = busy || !record || !isCoaches;

        el.timeActions.style.display = isCoaches ? '' : 'none';
        el.studentActions.style.display = canViewLogs ? '' : 'none';
        el.loggedFilterGroup.style.display = activeTab === 'students' ? '' : 'none';

        // Athletes have no registered courses, so the class filter is hidden there.
        el.classFilterGroup.style.display = activeTab === 'athletes' ? 'none' : '';
    }

    /* =========================================================
       LOAD DATA (tab + date + class are all sent to the server)
    ========================================================= */

    async function loadRows({ keepSelection = false } = {}) {
        const seq = ++loadSeq;

        if (!el.dateInput.value) {
            el.dateInput.value = el.dateInput.max;
        }

        if (!keepSelection) selectedId = null;

        loading = true;
        updateToolbarState();
        renderTable();

        const params = new URLSearchParams({
            tab: activeTab,
            date: el.dateInput.value
        });

        if (activeTab !== 'athletes' && el.classFilterInput.value) {
            params.set('course_id', el.classFilterInput.value);
        }

        try {
            const result = await api(`${URLS.data}?${params}`);

            if (seq !== loadSeq) return;

            loading = false;

            if (!result.ok) {
                rows = [];
                toastServerErrors(result, 'Unable to load attendance.');
            } else {
                rows = (result.data.rows || []).map(r => ({ ...r, id: String(r.id) }));

                if (!classOptionsBuilt) {
                    buildClassOptions(result.data.courses || []);
                }

                if (selectedId !== null && !rows.some(r => String(r.id) === String(selectedId))) {
                    selectedId = null;
                }
            }
        } catch (error) {
            if (seq !== loadSeq) return;

            console.error(error);
            loading = false;
            rows = [];
            showToast('Something went wrong while loading attendance.', 'danger');
        }

        updateToolbarState();
        renderTable();
    }

    el.search.addEventListener('input', applyLocalFilters);

    /* =========================================================
       TABS
    ========================================================= */

    function updateTabButtons() {
        el.tabs.querySelectorAll('[data-attendance-tab]').forEach(button => {
            const isSelected = button.dataset.attendanceTab === activeTab;

            button.classList.toggle('btn-primary', isSelected);
            button.classList.toggle('btn-outline-primary', !isSelected);
            button.classList.toggle('active', isSelected);
            button.setAttribute('aria-selected', String(isSelected));
        });

        el.hint.textContent = TAB_CONFIGS[activeTab].hint;
    }

    function switchTab(tabName) {
        if (!TAB_CONFIGS[tabName] || tabName === activeTab) return;

        activeTab = tabName;
        selectedId = null;
        rows = [];

        updateTabButtons();
        updateToolbarState();
        loadRows();
    }

    el.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-attendance-tab]');
        if (button) switchTab(button.dataset.attendanceTab);
    });

    /* =========================================================
       TIME ENTRY MODAL
    ========================================================= */

    function openTimeModal(action) {
        const record = getSelectedRecord();
        if (!record || activeTab !== 'coaches') return;

        pendingAction = action;

        el.modalTitle.textContent = action === 'in'
            ? 'Record Time In'
            : 'Record Time Out';

        el.modalName.replaceChildren(
            document.createTextNode('Recording for '),
            make('strong', { text: record.name })
        );

        if (action === 'out' && record.time_in) {
            el.modalMeta.textContent = `Time In: ${formatTimeLabel(record.time_in)}`;
            el.modalMeta.hidden = false;
        } else {
            el.modalMeta.hidden = true;
        }

        el.modalInput.value = currentTimeInputValue();
        el.modalLabel.textContent = 'Current Time';
        el.modalLabel.classList.remove('isDimmed');

        el.modalOverlay.hidden = false;
        el.modalInput.focus();
    }

    function closeTimeModal() {
        el.modalOverlay.hidden = true;
        pendingAction = null;
    }

    async function saveTimeModal() {
        if (busy) return;

        const record = getSelectedRecord();
        const action = pendingAction;

        if (!record || !action || activeTab !== 'coaches') {
            closeTimeModal();
            return;
        }

        const time = el.modalInput.value;

        if (!time) {
            showToast('Please choose a time.', 'danger');
            return;
        }

        if (action === 'out' && record.time_in && time <= record.time_in) {
            showToast('Time Out must be later than Time In.', 'danger');
            return;
        }

        busy = true;
        el.modalSave.disabled = true;
        el.modalSave.textContent = 'Saving...';
        updateToolbarState();

        try {
            const result = await api(
                action === 'in' ? URLS.timeIn : URLS.timeOut,
                {
                    method: 'POST',
                    body: {
                        coach_id: record.id,
                        date: el.dateInput.value,
                        time
                    }
                }
            );

            if (result.ok) {
                closeTimeModal();

                showToast(
                    `${action === 'in' ? 'Time In' : 'Time Out'} recorded for ${record.name}.`,
                    'success'
                );

                await loadRows({ keepSelection: true });
            } else {
                toastServerErrors(result, 'Unable to save the time.');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving the time.', 'danger');
        } finally {
            busy = false;
            el.modalSave.disabled = false;
            el.modalSave.textContent = 'Save';
            updateToolbarState();
        }
    }

    el.timeInButton.addEventListener('click', () => openTimeModal('in'));
    el.timeOutButton.addEventListener('click', () => openTimeModal('out'));

    el.modalClose.addEventListener('click', () => {
        if (!busy) closeTimeModal();
    });

    el.modalCancel.addEventListener('click', () => {
        if (!busy) closeTimeModal();
    });

    el.modalSave.addEventListener('click', saveTimeModal);

    // Greys out "Current Time" once the admin edits the field.
    el.modalInput.addEventListener('input', () => {
        el.modalLabel.classList.add('isDimmed');
    });

    // Clicking outside the card does NOT close the modal; Escape does.
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        if (!el.logsOverlay.hidden) closeLogsModal();
        if (!el.modalOverlay.hidden && !busy) closeTimeModal();
    });

    /* =========================================================
       STUDENT LOG MODAL (view only)
       Opened from the Students tab with the View Logs button.
       The Course filter lists every enrollment the student has had,
       so logs from courses they used to be in can be reviewed too.
    ========================================================= */

    function logCell(value, dash = '--') {
        return value
            ? document.createTextNode(value)
            : make('span', { className: 'attendanceEmptyValue', text: dash });
    }

    function renderLogs(list, isLoading, isAthlete = false) {
        const headers = isAthlete
            ? ['Date', 'Time In', 'Time Out', 'Status', 'Logged By']
            : ['Date', 'Course', 'Time In', 'Time Out', 'Status', 'Logged By'];

        el.logsTableHead.replaceChildren();
        el.logsTableBody.replaceChildren();

        const headerRow = make('tr');

        headers.forEach(h => {
            headerRow.append(make('th', {
                text: h,
                attributes: { scope: 'col' }
            }));
        });

        el.logsTableHead.append(headerRow);

        if (isLoading || !list.length) {
            const emptyRow = make('tr');

            emptyRow.append(make('td', {
                className: 'attendanceEmptyState',
                text: isLoading
                    ? 'Loading...'
                    : 'No attendance has been logged for this selection.',
                attributes: { colSpan: headers.length }
            }));

            el.logsTableBody.append(emptyRow);
            el.logsSummary.textContent = '';
            return;
        }

        list.forEach(log => {
            const row = make('tr', { className: 'attendanceRow' });
            const dateCell = make('td');
            const wrapper = make('span', { className: 'attendanceNameCell' });

            wrapper.append(
                make('span', {
                    className: `attendanceStatusDot ${log.time_in ? 'present' : 'absent'}`
                }),
                document.createTextNode(
                    formatShortDate(parseISODate(log.date))
                )
            );

            dateCell.append(wrapper);
            row.append(dateCell);

            const values = [];

            if (!isAthlete) {
                values.push(log.course);
            }

            values.push(
                formatTimeLabel(log.time_in),
                formatTimeLabel(log.time_out),
                log.state,
                log.logged_by
            );

            values.forEach((value, index) => {
                const cell = make('td');
                const isTimeColumn = isAthlete
                    ? index === 0 || index === 1
                    : index === 1 || index === 2;

                cell.append(logCell(value, isTimeColumn ? '-' : '--'));
                row.append(cell);
            });

            el.logsTableBody.append(row);
        });

        const attended = list.filter(log => log.time_in).length;

        el.logsSummary.textContent =
            `${list.length} log${list.length === 1 ? '' : 's'} · ${attended} attended`;
    }

    function buildLogFilter(enrollments) {
        const choices = [{ value: 'all', label: 'All courses' }]
            .concat(enrollments.map(e => ({
                value: String(e.id),
                label: e.label
            })));

        const current = choices.find(c => c.value === el.logFilterInput.value) || choices[0];

        window.FBSSDropdown.setOptions(
            el.logFilter,
            choices,
            current.value
        );

        logFilterBuilt = true;
    }

    async function loadStudentLogs() {
        const seq = ++logsSeq;

        renderLogs([], true);

        const params = new URLSearchParams({
            enrollment_id: String(logsAnchorId),
            filter: el.logFilterInput.value || String(logsAnchorId)
        });

        try {
            const result = await api(`${URLS.studentLogs}?${params}`);

            if (seq !== logsSeq) return;

            if (!result.ok) {
                renderLogs([], false);
                toastServerErrors(result, 'Unable to load the attendance log.');
                return;
            }

            if (!logFilterBuilt) {
                buildLogFilter(result.data.enrollments || []);
            }

            renderLogs(result.data.logs || [], false);
        } catch (error) {
            if (seq !== logsSeq) return;

            console.error(error);
            renderLogs([], false);
            showToast('Something went wrong while loading the attendance log.', 'danger');
        }
    }

    async function loadAthleteLogs() {
        const seq = ++logsSeq;

        renderLogs([], true, true);

        const params = new URLSearchParams({
            [activeTab === 'coaches' ? 'coach_id' : 'athlete_id']: String(logsAnchorId)
        });

        try {
            const result = await api(`${activeTab === 'coaches' ? URLS.coachLogs : URLS.athleteLogs}?${params}`);

            if (seq !== logsSeq) return;

            if (!result.ok) {
                renderLogs([], false, true);
                toastServerErrors(result, 'Unable to load the attendance log.');
                return;
            }

            renderLogs(result.data.logs || [], false, true);
        } catch (error) {
            if (seq !== logsSeq) return;

            console.error(error);
            renderLogs([], false, true);
            showToast('Something went wrong while loading the attendance log.', 'danger');
        }
    }

    function openLogsModal() {
        const record = getSelectedRecord();

        if (!record || !['students', 'athletes', 'coaches'].includes(activeTab)) return;

        const isAthlete = activeTab !== 'students';

        logsSeq++;
        logsAnchorId = record.id;
        logFilterBuilt = false;
        el.logFilterInput.value = '';

        el.logsStudent.replaceChildren(
            document.createTextNode(
                activeTab === 'coaches' ? 'Coach: ' : (isAthlete ? 'Athlete: ' : 'Student: ')
            ),
            make('strong', { text: record.name })
        );

        el.logCourseFilterGroup.style.display = isAthlete ? 'none' : '';

        if (isAthlete) {
            el.logsOverlay.hidden = false;
            loadAthleteLogs();
            return;
        }

        // Preserve existing student enrollment filtering.
        window.FBSSDropdown.setOptions(
            el.logFilter,
            [{ value: String(record.id), label: record.course || 'Loading...' }],
            String(record.id)
        );

        el.logsOverlay.hidden = false;
        loadStudentLogs();
    }

    function closeLogsModal() {
        logsSeq++;
        el.logsOverlay.hidden = true;
        window.FBSSDropdown.close();
    }

    el.viewLogsButton.addEventListener('click', openLogsModal);
    el.coachViewLogsButton.addEventListener('click', openLogsModal);
    el.logsClose.addEventListener('click', closeLogsModal);
    el.logsDone.addEventListener('click', closeLogsModal);

    /* =========================================================
       DROPDOWNS — open/close, selection and positioning come from the
       shared assets/js/fbss-dropdown.js. This page only reacts to the
       `dropdown:change` event it fires.
    ========================================================= */

    el.classFilter.addEventListener('dropdown:change', () => {
        if (activeTab !== 'athletes') loadRows();
    });

    $('loggedFilterDropdown').addEventListener('dropdown:change', applyLocalFilters);
    el.logFilter.addEventListener('dropdown:change', () => {
        if (activeTab === 'students' && !el.logsOverlay.hidden) {
            loadStudentLogs();
        }
    });

    /* =========================================================
       INIT
    ========================================================= */

    renderCurrentDateLine();
    initDatePicker();
    updateTabButtons();
    updateToolbarState();
    loadRows();

})();
</script>