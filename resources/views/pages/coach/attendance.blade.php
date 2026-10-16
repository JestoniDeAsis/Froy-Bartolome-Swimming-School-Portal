@extends('layouts.toast')

<style>
/* =========================================================
   Attendance (coach)
   Same skeleton as the admin page. Dropdowns use the shared
   .registerDropdown + assets/js/fbss-dropdown.js (data-fbss-dropdown).
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

/* Coach session banner: tells the coach whether logging is open */
.attendanceSessionBanner {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: .6rem;
    margin-bottom: .75rem;
    padding: .65rem .9rem;
    border: 1px solid transparent;
    border-radius: 10px;
    font-size: .875rem;
}
.attendanceSessionBanner i { flex-shrink: 0; font-size: 1rem; }
.attendanceSessionBanner.isOk     { background: #ebfbee; border-color: #b2f2bb; color: #2b8a3e; }
.attendanceSessionBanner.isWait   { background: #fff9db; border-color: #ffec99; color: #a35c00; }
.attendanceSessionBanner.isClosed { background: #fff5f5; border-color: #ffc9c9; color: #c92a2a; }
.attendanceSessionBanner.isView   { background: #f1f3f5; border-color: #dee2e6; color: #495057; }

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

/* Rows are selectable so a student can be logged / their log viewed. */
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
.attendanceStatusDot.expecting { background: #f59f00; }

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

.attendanceLogTableContainer {
    max-height: 50vh;
    overflow: auto;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}
</style>


<section class="attendanceContainer" id="attendanceContainer" aria-labelledby="attendanceTitle">

    <header class="attendanceHeader">
        <h2 class="mb-1" id="attendanceTitle">Attendance</h2>
        <p class="text-muted mb-0" id="attendanceDescription"></p>
        <p class="text-muted small mb-0" id="attendanceHint">
            Select a student, then record their Time In or Time Out. Logging opens once the admin has timed you in.
        </p>
    </header>

    <!-- Tells the coach whether logging is open (admin must time them in first) -->
    <div class="attendanceSessionBanner isWait" id="sessionBanner" role="status" aria-live="polite">
        <i class="bi bi-hourglass-split" id="sessionBannerIcon" aria-hidden="true"></i>
        <span id="sessionBannerText">Checking your attendance...</span>
    </div>

    <!-- TOOLBAR -->
    <div class="attendanceToolbar">

        <!-- Select Class (only the coach's own classes; options come from the database) -->
        <div class="attendanceFilterGroup">
            <label class="attendanceFilterLabel">Select Class</label>

            <div class="registerDropdown attendanceClassFilter" id="classFilterDropdown" data-fbss-dropdown>
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

        <!-- Attendance Date -->
        <div class="attendanceFilterGroup">
            <label class="attendanceFilterLabel" for="attendanceDateInput">Attendance Date</label>

            <div class="attendanceDatePicker">
                <button class="attendanceDateButton" id="attendanceDateButton" type="button">
                    <span id="attendanceDateSelected">Today</span>
                    <i class="bi bi-calendar3" aria-hidden="true"></i>
                </button>
                <input class="attendanceDateInput" id="attendanceDateInput" type="date" aria-label="Attendance date" tabindex="-1">
            </div>
        </div>

        <!-- Attendance filter: logged in that day or not -->
        <div class="attendanceFilterGroup">
            <label class="attendanceFilterLabel">Attendance</label>

            <div class="registerDropdown attendanceLoggedFilter" id="loggedFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="loggedFilterToggle" type="button" aria-expanded="false" aria-controls="loggedFilterOptions" aria-label="Filter by attendance">
                    <span>All</span>
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
        <div class="attendanceFilterGroup">
            <label class="attendanceFilterLabel" for="attendanceSearch">Search</label>

            <div class="attendanceSearchContainer">
                <i class="bi bi-search attendanceSearchIcon" aria-hidden="true"></i>
                <input class="form-control attendanceSearchInput" id="attendanceSearch" type="search"
                       placeholder="Search" autocomplete="off" aria-label="Search students">
            </div>
        </div>

        <!-- Actions -->
        <div class="attendanceToolbarActions">
            <button class="btn btn-primary" id="recordTimeInButton" type="button" disabled>Time In</button>
            <button class="btn btn-primary" id="recordTimeOutButton" type="button" disabled>Time Out</button>
            <button class="btn btn-outline-primary" id="markExpectingButton" type="button" disabled title="Mark the selected student as Expecting (no Time In / Time Out is recorded)">Expecting</button>
            <button class="btn btn-outline-primary" id="viewLogsButton" type="button" disabled>View Logs</button>
            <button class="btn btn-outline-primary" id="refreshButton" type="button" title="Check again whether the admin has timed you in">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Refresh
            </button>
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
                <div class="attendanceFilterGroup">
                    <label class="attendanceFilterLabel">Course</label>

                    <div class="registerDropdown attendanceLogFilter" id="logFilterDropdown" data-fbss-dropdown>
                        <button class="registerDropdownToggle" id="logFilterToggle" type="button" aria-expanded="false" aria-controls="logFilterOptions" aria-label="Filter log by course">
                            <span>Loading...</span>
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
        data: '/coach/attendance/data',
        timeIn: '/coach/attendance/students/time-in',
        timeOut: '/coach/attendance/students/time-out',
        expecting: '/coach/attendance/students/expecting',
        studentLogs: '/coach/attendance/student-logs'
    };

    // "key" matches the field names the server returns.
    const COLUMNS = [
        { label: 'Student Name', key: 'name', isNameColumn: true },
        { label: 'Logged By', key: 'coach' },
        { label: 'Course Level', key: 'course' },
        { label: 'Schedule', key: 'schedule' },
        { label: 'Sessions Completed', key: 'sessions' },
        { label: 'Time In', key: 'time_in', isTime: true },
        { label: 'Time Out', key: 'time_out', isTime: true },
        { label: 'Status', key: 'state' }
    ];

    const SEARCH_KEYS = ['name', 'coach', 'course', 'schedule', 'state'];

    const REFRESH_MS = 60000;   // re-check the coach's session (admin may have timed them in/out)

    /* =========================================================
       ELEMENTS + STATE
    ========================================================= */

    const $ = id => document.getElementById(id);

    const el = {
        description: $('attendanceDescription'),

        banner: $('sessionBanner'),
        bannerIcon: $('sessionBannerIcon'),
        bannerText: $('sessionBannerText'),

        tableHead: $('attendanceTableHead'),
        tableBody: $('attendanceTableBody'),

        classFilter: $('classFilterDropdown'),
        loggedFilter: $('loggedFilterDropdown'),
        logFilter: $('logFilterDropdown'),

        dateButton: $('attendanceDateButton'),
        dateSelected: $('attendanceDateSelected'),
        dateInput: $('attendanceDateInput'),

        search: $('attendanceSearch'),

        timeInButton: $('recordTimeInButton'),
        timeOutButton: $('recordTimeOutButton'),
        expectingButton: $('markExpectingButton'),
        viewLogsButton: $('viewLogsButton'),
        refreshButton: $('refreshButton'),

        logsOverlay: $('logsModalOverlay'),
        logsStudent: $('logsModalStudent'),
        logsSummary: $('logsSummary'),
        logsTableHead: $('logsTableHead'),
        logsTableBody: $('logsTableBody'),
        logsClose: $('logsModalCloseButton'),
        logsDone: $('logsModalDoneButton'),

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

    let rows = [];
    let loading = false;
    let loadSeq = 0;            // ignores out-of-order responses
    let selectedId = null;
    let pendingAction = null;   // 'in' | 'out'
    let classOptionsBuilt = false;
    let busy = false;
    let sessionLoaded = false;
    let session = { state: 'not_in', time_in: null, time_out: null, can_log: false, block_reason: null };
    let logsAnchorId = null;    // enrollment the log modal was opened from
    let logsAnchorRecord = null; // the row it was opened from (an athlete has no enrollment)
    let logsSeq = 0;            // ignores out-of-order log responses
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
        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function formatShortDate(date) {
        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
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
        return ref.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    const isToday = () => el.dateInput.value === toLocalISODate(new Date());

    // Logging needs the server's OK (admin timed the coach in, not out) AND today's date.
    const canLog = () => session.can_log && isToday();

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
       HEADER, SESSION BANNER + DATE PICKER
    ========================================================= */

    function renderCurrentDateLine() {
        el.description.textContent = `Current date: ${formatLongDate(new Date())}`;
    }

    function renderBanner() {
        let tone;
        let icon;
        let text;

        if (!sessionLoaded) {
            tone = 'isWait';
            icon = 'bi-hourglass-split';
            text = 'Checking your attendance...';
        } else if (!isToday()) {
            tone = 'isView';
            icon = 'bi-eye';
            text = `Viewing ${formatShortDate(parseISODate(el.dateInput.value))}. Past dates are view only; logging is available for today.`;
        } else if (session.can_log) {
            tone = 'isOk';
            icon = 'bi-check-circle-fill';
            text = `You were timed in at ${formatTimeLabel(session.time_in)}. You can log your students' attendance.`;
        } else if (session.state === 'out') {
            tone = 'isClosed';
            icon = 'bi-lock-fill';
            text = `You were timed out at ${formatTimeLabel(session.time_out)}, so you can no longer log attendance today.`;
        } else {
            tone = 'isWait';
            icon = 'bi-hourglass-split';
            text = session.block_reason || 'Waiting for the admin to record your Time In.';
        }

        el.banner.className = `attendanceSessionBanner ${tone}`;
        el.bannerIcon.className = `bi ${icon}`;
        el.bannerText.textContent = text;
    }

    function updateDateLabel() {
        if (!el.dateInput.value) {
            el.dateSelected.textContent = 'Select date';
            return;
        }

        const picked = parseISODate(el.dateInput.value);

        el.dateSelected.textContent = isToday()
            ? `Today - ${formatShortDate(picked)}`
            : formatShortDate(picked);
    }

    function initDatePicker() {
        const today = toLocalISODate(new Date());
        el.dateInput.max = today;          // no future dates
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
            // Cleared or future date -> fall back to today.
            if (!el.dateInput.value || el.dateInput.value > el.dateInput.max) {
                el.dateInput.value = el.dateInput.max;
            }

            updateDateLabel();
            renderBanner();
            loadRows();
        });
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

    // green = timed in, amber = marked Expecting, red = nothing yet
    function statusDotClass(record) {
        if (record.time_in) return 'present';
        return record.state === 'Expecting' ? 'expecting' : 'absent';
    }

    function renderNameCell(record) {
        const wrapper = make('span', { className: 'attendanceNameCell' });
        wrapper.append(
            make('span', { className: `attendanceStatusDot ${statusDotClass(record)}` }),
            document.createTextNode(record.name)
        );
        return wrapper;
    }

    function renderCell(column, record) {
        if (column.isNameColumn) return renderNameCell(record);

        const value = column.isTime ? formatTimeLabel(record[column.key]) : (record[column.key] ?? '');

        return value
            ? document.createTextNode(value)
            : make('span', { className: 'attendanceEmptyValue', text: column.isTime ? '-' : '--' });
    }

    function visibleRows() {
        const term = el.search.value.trim().toLowerCase();
        const logged = dropdownValue(el.loggedFilter);   // 'all' | 'in' | 'out'

        let list = rows;

        // Green dot = logged in that day, red dot = not.
        if (logged !== 'all') {
            list = list.filter(record => logged === 'in' ? Boolean(record.time_in) : !record.time_in);
        }

        if (!term) return list;

        return list.filter(record =>
            SEARCH_KEYS.some(key => String(record[key] ?? '').toLowerCase().includes(term))
        );
    }

    // Search / attendance filter: re-filter what is already loaded, and drop
    // the selection if that row is no longer visible.
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
                className: 'attendanceEmptyState',
                text: loading ? 'Loading...' : (rows.length ? 'No matching students found.' : 'No students found in your classes.'),
                attributes: { colSpan: COLUMNS.length + 1 }
            }));
            el.tableBody.append(emptyRow);
            return;
        }

        list.forEach((record, index) => {
            const tableRow = make('tr', { className: 'attendanceRow isSelectable' });
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
        const open = canLog();

        el.timeInButton.disabled = busy || !open || !record || Boolean(record.time_in);
        el.timeOutButton.disabled = busy || !open || !record || !record.time_in || Boolean(record.time_out);
        // Students only (athletes are not part of the skills assessment); not once timed in or already Expecting.
        el.expectingButton.disabled = busy || !open || !record || record.kind === 'athlete'
            || Boolean(record.time_in) || record.state === 'Expecting';
        el.viewLogsButton.disabled = busy || !record;
    }

    /* =========================================================
       LOAD DATA (date + class are sent to the server; the response
       also carries this coach's own session state)
    ========================================================= */

    async function loadRows({ keepSelection = false, silent = false } = {}) {
        const seq = ++loadSeq;

        if (!el.dateInput.value) el.dateInput.value = el.dateInput.max;
        if (!keepSelection) selectedId = null;

        if (!silent) {
            loading = true;
            updateToolbarState();
            renderTable();
        }

        const params = new URLSearchParams({ date: el.dateInput.value });
        const courseId = dropdownValue(el.classFilter);
        if (courseId) params.set('course_id', courseId);

        try {
            const result = await api(`${URLS.data}?${params}`);
            if (seq !== loadSeq) return;   // a newer request replaced this one

            loading = false;

            if (!result.ok) {
                if (!silent) {
                    rows = [];
                    toastServerErrors(result, 'Unable to load attendance.');
                }
            } else {
                rows = result.data.rows || [];
                session = {
                    state: result.data.session?.state || 'not_in',
                    time_in: result.data.session?.time_in || null,
                    time_out: result.data.session?.time_out || null,
                    can_log: Boolean(result.data.can_log),
                    block_reason: result.data.block_reason || null
                };
                sessionLoaded = true;

                if (!classOptionsBuilt) buildClassOptions(result.data.courses || []);
                if (selectedId !== null && !rows.some(r => r.id === selectedId)) selectedId = null;
            }
        } catch (error) {
            if (seq !== loadSeq) return;
            console.error(error);
            loading = false;
            if (!silent) {
                rows = [];
                showToast('Something went wrong while loading attendance.', 'danger');
            }
        }

        updateToolbarState();
        renderBanner();
        renderTable();
    }

    el.search.addEventListener('input', applyLocalFilters);
    el.classFilter.addEventListener('dropdown:change', () => loadRows());
    el.loggedFilter.addEventListener('dropdown:change', applyLocalFilters);
    el.refreshButton.addEventListener('click', () => loadRows({ keepSelection: true }));

    // The admin times the coach in / out from another screen, so re-check
    // when the coach comes back to this tab and once a minute while it is open.
    function refreshQuietly() {
        if (document.hidden || busy || !el.modalOverlay.hidden || !el.logsOverlay.hidden) return;
        loadRows({ keepSelection: true, silent: true });
    }

    document.addEventListener('visibilitychange', refreshQuietly);
    setInterval(refreshQuietly, REFRESH_MS);

    /* =========================================================
       TIME ENTRY MODAL
    ========================================================= */

    function openTimeModal(action) {
        const record = getSelectedRecord();
        if (!record || !canLog()) return;

        pendingAction = action;

        el.modalTitle.textContent = action === 'in' ? 'Record Time In' : 'Record Time Out';
        el.modalName.replaceChildren(document.createTextNode('Recording for '), make('strong', { text: record.name }));

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

        if (!record || !action) {
            closeTimeModal();
            return;
        }

        const time = el.modalInput.value;   // "HH:MM"

        if (!time) {
            showToast('Please choose a time.', 'danger');
            return;
        }

        if (session.time_in && time < session.time_in) {
            showToast(`The time cannot be earlier than your own Time In (${formatTimeLabel(session.time_in)}).`, 'danger');
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
            const result = await api(action === 'in' ? URLS.timeIn : URLS.timeOut, {
                method: 'POST',
                // An athlete is sent by user id; a student by enrollment id.
                body: record.kind === 'athlete'
                    ? { athlete_id: record.user_id, date: el.dateInput.value, time }
                    : { enrollment_id: record.id, date: el.dateInput.value, time }
            });

            if (result.ok) {
                closeTimeModal();
                showToast(`${action === 'in' ? 'Time In' : 'Time Out'} recorded for ${record.name}.`, 'success');
                await loadRows({ keepSelection: true, silent: true });
            } else {
                toastServerErrors(result, 'Unable to save the time.');

                // e.g. the admin timed this coach out while the modal was open:
                // close it and show the new state.
                if (result.status === 403) {
                    closeTimeModal();
                    await loadRows({ keepSelection: true, silent: true });
                }
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

    // "Expecting": saves only the state (no time) so the student is listed in
    // the Skills Assessment page. Time In later works as usual.
    async function markExpecting() {
        const record = getSelectedRecord();
        if (!record || busy || !canLog() || record.kind === 'athlete') return;

        busy = true;
        updateToolbarState();

        try {
            const result = await api(URLS.expecting, {
                method: 'POST',
                body: { enrollment_id: record.id, date: el.dateInput.value }
            });

            if (result.ok) {
                showToast(`${record.name} marked as Expecting.`, 'success');
                await loadRows({ keepSelection: true, silent: true });
            } else {
                toastServerErrors(result, 'Unable to mark the student as Expecting.');

                if (result.status === 403) await loadRows({ keepSelection: true, silent: true });
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while marking the student as Expecting.', 'danger');
        } finally {
            busy = false;
            updateToolbarState();
        }
    }

    el.expectingButton.addEventListener('click', markExpecting);
    el.timeInButton.addEventListener('click', () => openTimeModal('in'));
    el.timeOutButton.addEventListener('click', () => openTimeModal('out'));
    el.modalClose.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.modalCancel.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.modalSave.addEventListener('click', saveTimeModal);

    // Greys out "Current Time" once the coach edits the field.
    el.modalInput.addEventListener('input', () => el.modalLabel.classList.add('isDimmed'));

    // Clicking outside the card does NOT close a modal; Escape does.
    // (The shared dropdown script closes open dropdowns on Escape by itself.)
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        if (!el.logsOverlay.hidden) closeLogsModal();
        if (!el.modalOverlay.hidden && !busy) closeTimeModal();
    });

    /* =========================================================
       STUDENT LOG MODAL (view only)
       The Course filter lists every enrollment the student has had in
       THIS coach's classes, so earlier courses can be reviewed too.
    ========================================================= */

    function logCell(value, dash = '--') {
        return value
            ? document.createTextNode(value)
            : make('span', { className: 'attendanceEmptyValue', text: dash });
    }

    function renderLogs(list, isLoading) {
        const headers = ['Date', 'Course', 'Time In', 'Time Out', 'Status', 'Logged By'];

        el.logsTableHead.replaceChildren();
        el.logsTableBody.replaceChildren();

        const headerRow = make('tr');
        headers.forEach(h => headerRow.append(make('th', { text: h, attributes: { scope: 'col' } })));
        el.logsTableHead.append(headerRow);

        if (isLoading || !list.length) {
            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'attendanceEmptyState',
                text: isLoading ? 'Loading...' : 'No attendance has been logged for this selection.',
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
                make('span', { className: `attendanceStatusDot ${log.time_in ? 'present' : 'absent'}` }),
                document.createTextNode(formatShortDate(parseISODate(log.date)))
            );
            dateCell.append(wrapper);
            row.append(dateCell);

            [
                logCell(log.course),
                logCell(formatTimeLabel(log.time_in), '-'),
                logCell(formatTimeLabel(log.time_out), '-'),
                logCell(log.state),
                logCell(log.logged_by)
            ].forEach(content => {
                const cell = make('td');
                cell.append(content);
                row.append(cell);
            });

            el.logsTableBody.append(row);
        });

        const attended = list.filter(log => log.time_in).length;
        el.logsSummary.textContent = `${list.length} log${list.length === 1 ? '' : 's'} · ${attended} attended`;
    }

    function buildLogFilter(enrollments) {
        const choices = [{ value: 'all', label: 'All courses' }]
            .concat(enrollments.map(e => ({ value: String(e.id), label: e.label })));

        // Keeps the current pick when it is still in the list, else "All courses".
        const current = choices.find(c => c.value === dropdownValue(el.logFilter)) || choices[0];
        window.FBSSDropdown.setOptions(el.logFilter, choices, current.value);

        logFilterBuilt = true;
    }

    async function loadStudentLogs() {
        const seq = ++logsSeq;

        renderLogs([], true);

        const params = logsAnchorRecord && logsAnchorRecord.kind === 'athlete'
            ? new URLSearchParams({ athlete_id: String(logsAnchorRecord.user_id) })
            : new URLSearchParams({
                enrollment_id: String(logsAnchorId),
                filter: dropdownValue(el.logFilter) || String(logsAnchorId)
            });

        try {
            const result = await api(`${URLS.studentLogs}?${params}`);
            if (seq !== logsSeq) return;   // modal closed or a newer request replaced this one

            if (!result.ok) {
                renderLogs([], false);
                toastServerErrors(result, 'Unable to load the attendance log.');
                return;
            }

            if (!logFilterBuilt) buildLogFilter(result.data.enrollments || []);
            renderLogs(result.data.logs || [], false);
        } catch (error) {
            if (seq !== logsSeq) return;
            console.error(error);
            renderLogs([], false);
            showToast('Something went wrong while loading the attendance log.', 'danger');
        }
    }

    function openLogsModal() {
        const record = getSelectedRecord();
        if (!record) return;

        logsAnchorId = record.id;
        logsAnchorRecord = record;
        logFilterBuilt = false;

        // Start on the enrollment the coach clicked; "All courses" is one pick away.
        window.FBSSDropdown.setOptions(
            el.logFilter,
            [{ value: String(record.id), label: record.course || 'Loading...' }],
            String(record.id)
        );

        el.logsStudent.replaceChildren(document.createTextNode('Student: '), make('strong', { text: record.name }));

        el.logsOverlay.hidden = false;
        loadStudentLogs();
    }

    function closeLogsModal() {
        logsSeq++;   // discard any response still in flight
        el.logsOverlay.hidden = true;
        window.FBSSDropdown.close();
    }

    el.viewLogsButton.addEventListener('click', openLogsModal);
    el.logsClose.addEventListener('click', closeLogsModal);
    el.logsDone.addEventListener('click', closeLogsModal);
    el.logFilter.addEventListener('dropdown:change', loadStudentLogs);

    /* =========================================================
       INIT
    ========================================================= */

    renderCurrentDateLine();
    initDatePicker();
    renderBanner();
    updateToolbarState();
    loadRows();

})();
</script>