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

/* Tabs */
.attendanceTabsContainer {
    flex: 0 0 auto;
    display: flex;
    gap: .5rem;
    margin-bottom: .75rem;
}

.attendanceTab { min-width: 150px; }

/* Each tab's content. display:flex would override [hidden], so it is restated. */
.attendancePanel {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.attendancePanel[hidden] { display: none; }

/* My Attendance tab */
.attendanceMonthInput { height: 40px; min-width: 190px; }

.attendanceStats {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: .5rem;
}

.attendanceStat {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    min-width: 150px;
    padding: .6rem .9rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 10px;
}

.attendanceStat strong { font-size: 1.15rem; color: #101828; }

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
.attendanceSessionBanner { flex-wrap: wrap; }
.attendanceSessionActions { display: flex; gap: .5rem; margin-left: auto; flex-wrap: wrap; }
.attendanceLastUpdated { margin: .5rem 0 .25rem; font-size: .75rem; color: #98a2b3; }
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

.attendanceModalRemove {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin-top: .5rem;
    font-size: .875rem;
    color: #b42318;
    cursor: pointer;
}
.attendanceModalRemove input { width: 16px; height: 16px; accent-color: #b42318; }
.attendanceModalInput:disabled { background: #f2f4f7; color: #98a2b3; cursor: not-allowed; }

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
    </header>

    <!-- TABS -->
    <nav class="attendanceTabsContainer" id="attendanceTabs" aria-label="Attendance sections">
        <button class="btn btn-primary attendanceTab active" type="button" data-attendance-tab="log" aria-selected="true">
            Log Attendance
        </button>
        <button class="btn btn-outline-primary attendanceTab" type="button" data-attendance-tab="mine" aria-selected="false">
            My Attendance
        </button>
    </nav>

    <!-- TAB 1: Log Attendance (students and athletes) -->
    <div class="attendancePanel" id="loggingPanel">

    <p class="text-muted small mb-2" id="attendanceHint">
        Time In first (My Time In, below), then select a student to record their Time In or Time Out.
    </p>

    <!-- The coach's own Time In / Time Out. The server records its own current time; nothing can be typed in. -->
    <div class="attendanceSessionBanner isWait" id="sessionBanner">
        <i class="bi bi-hourglass-split" id="sessionBannerIcon" aria-hidden="true"></i>
        <span id="sessionBannerText" role="status" aria-live="polite">Checking your attendance...</span>

        <div class="attendanceSessionActions">
            <button class="btn btn-sm btn-primary" id="sessionTimeInButton" type="button" hidden>My Time In</button>
            <button class="btn btn-sm btn-primary" id="sessionTimeOutButton" type="button" hidden>My Time Out</button>
            <button class="btn btn-sm btn-outline-danger" id="sessionCancelButton" type="button" hidden>Cancel Time In</button>
            <button class="btn btn-sm btn-outline-danger" id="sessionCancelOutButton" type="button" hidden>Cancel Time Out</button>
        </div>
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

        <!-- Attendance filter: logged in that day or not, or logged by this coach -->
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
                    <button class="registerDropdownOption" type="button" data-value="mine">Logged by Me</button>
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
            <button class="btn btn-outline-primary" id="markExpectingButton" type="button" disabled title="Mark the selection as Expected (no Time In / Time Out is recorded yet)">Expected</button>
            <button class="btn btn-outline-danger" id="markAbsentButton" type="button" disabled title="Mark the selection as Absent (only if they have not timed in)">Absent</button>
            <button class="btn btn-outline-primary" id="viewLogsButton" type="button" disabled>View Logs</button>
        </div>

    </div>

    <!-- Auto-refresh note -->
    <div class="attendanceLastUpdated" id="lastUpdated" aria-hidden="true"></div>

    <!-- TABLE -->
    <div class="attendanceTableContainer" id="attendanceTableContainer">
        <table class="table attendanceTable" id="attendanceTable">
            <thead id="attendanceTableHead"></thead>
            <tbody id="attendanceTableBody"></tbody>
        </table>
    </div>

    </div><!-- /loggingPanel -->

    <!-- TAB 2: My Attendance (the coach's own, view only) -->
    <div class="attendancePanel" id="myPanel" hidden>

        <p class="text-muted small mb-2">
            Your own Time In and Time Out. You time in and out from the Log Attendance tab; only the admin can edit these.
        </p>

        <div class="attendanceToolbar">
            <div class="attendanceFilterGroup">
                <label class="attendanceFilterLabel" for="myMonthInput">Month</label>
                <input class="form-control attendanceMonthInput" id="myMonthInput" type="month" aria-label="Attendance month">
            </div>
        </div>

        <div class="attendanceStats">
            <div class="attendanceStat">
                <span class="attendanceFilterLabel">Days Present</span>
                <strong id="myStatDays">--</strong>
            </div>
            <div class="attendanceStat">
                <span class="attendanceFilterLabel">Hours Worked</span>
                <strong id="myStatHours">--</strong>
            </div>
            <div class="attendanceStat">
                <span class="attendanceFilterLabel">Missing Time Out</span>
                <strong id="myStatMissing">--</strong>
            </div>
        </div>

        <div class="attendanceLastUpdated" id="myUpdated" aria-hidden="true"></div>

        <div class="attendanceTableContainer">
            <table class="table attendanceTable" id="myTable">
                <thead id="myTableHead"></thead>
                <tbody id="myTableBody"></tbody>
            </table>
        </div>

    </div><!-- /myPanel -->

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

            <label class="attendanceModalRemove" id="timeModalRemoveWrap" for="timeModalRemove" hidden>
                <input type="checkbox" id="timeModalRemove">
                <span id="timeModalRemoveText">Remove Time In</span>
            </label>
        </div>

        <div class="attendanceModalFooter">
            <button class="btn btn-outline-primary" id="timeModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="timeModalSaveButton" type="button">Save</button>
        </div>

    </div>
</div>


<!-- CONFIRM MODAL (asks before one-click actions: My Time In / Out, cancels, Expected, Absent) -->
<div class="modalOverlay" id="confirmModalOverlay" hidden>
    <div class="attendanceModal" id="confirmModal" role="alertdialog" aria-modal="true" aria-labelledby="confirmModalTitle" aria-describedby="confirmModalMessage">

        <div class="attendanceModalHeader">
            <h5 id="confirmModalTitle">Confirm</h5>
            <button class="attendanceModalClose" id="confirmModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="attendanceModalBody">
            <p class="attendanceModalName mb-0" id="confirmModalMessage"></p>
        </div>

        <div class="attendanceModalFooter">
            <button class="btn btn-outline-primary" id="confirmModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="confirmModalOkButton" type="button">Confirm</button>
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
        absent: '/coach/attendance/students/absent',
        studentLogs: '/coach/attendance/student-logs',
        selfTimeIn: '/coach/attendance/self/time-in',
        selfTimeOut: '/coach/attendance/self/time-out',
        selfCancelIn: '/coach/attendance/self/cancel-time-in',
        selfCancelOut: '/coach/attendance/self/cancel-time-out',
        myLogs: '/coach/attendance/my-logs'
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
        { label: 'Status', key: 'state', isState: true }
    ];

    const SEARCH_KEYS = ['name', 'coach', 'course', 'schedule', 'state'];

    const REFRESH_MS = 10000;   // auto-refresh every 10 seconds (table + the coach's own session)

    // The stored attendance_state stays 'Expecting' (existing rows and the skills
    // assessment page rely on it); this is only the wording people read.
    const EXPECTING_LABEL = 'Expected';

    /* =========================================================
       ELEMENTS + STATE
    ========================================================= */

    const $ = id => document.getElementById(id);

    const el = {
        description: $('attendanceDescription'),

        tabs: $('attendanceTabs'),
        loggingPanel: $('loggingPanel'),
        myPanel: $('myPanel'),
        myMonth: $('myMonthInput'),
        myStatDays: $('myStatDays'),
        myStatHours: $('myStatHours'),
        myStatMissing: $('myStatMissing'),
        myUpdated: $('myUpdated'),
        myTableHead: $('myTableHead'),
        myTableBody: $('myTableBody'),

        banner: $('sessionBanner'),
        bannerIcon: $('sessionBannerIcon'),
        bannerText: $('sessionBannerText'),
        sessionTimeInButton: $('sessionTimeInButton'),
        sessionTimeOutButton: $('sessionTimeOutButton'),
        sessionCancelButton: $('sessionCancelButton'),
        sessionCancelOutButton: $('sessionCancelOutButton'),

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
        absentButton: $('markAbsentButton'),
        viewLogsButton: $('viewLogsButton'),
        lastUpdated: $('lastUpdated'),

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
        modalSave: $('timeModalSaveButton'),
        modalRemoveWrap: $('timeModalRemoveWrap'),
        modalRemove: $('timeModalRemove'),
        modalRemoveText: $('timeModalRemoveText'),

        confirmOverlay: $('confirmModalOverlay'),
        confirmTitle: $('confirmModalTitle'),
        confirmMessage: $('confirmModalMessage'),
        confirmOk: $('confirmModalOkButton'),
        confirmCancel: $('confirmModalCancelButton'),
        confirmClose: $('confirmModalCloseButton')
    };

    let rows = [];
    let loading = false;
    let loadSeq = 0;            // ignores out-of-order responses
    let selectedId = null;
    let pendingAction = null;   // 'in' | 'out'
    let classOptionsBuilt = false;
    let busy = false;
    let sessionLoaded = false;
    let session = {
        state: 'not_in', time_in: null, time_out: null,
        can_log: false, block_reason: null,
        can_time_in: false, can_time_out: false, can_cancel_in: false, can_cancel_out: false, has_logs: false
    };
    let logsAnchorId = null;    // enrollment the log modal was opened from
    let logsAnchorRecord = null; // the row it was opened from (an athlete has no enrollment)
    let logsSeq = 0;            // ignores out-of-order log responses
    let logFilterBuilt = false;

    let activeTab = 'log';     // 'log' = Log Attendance, 'mine' = My Attendance
    let myData = { logs: [], summary: null };
    let myLoading = false;
    let mySeq = 0;             // ignores out-of-order responses

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

    // attendance_state -> the text people read
    const stateLabel = state => state === 'Expecting' ? EXPECTING_LABEL : (state ?? '');

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
        } else if (session.state === 'out' && session.can_log) {
            tone = 'isOk';
            icon = 'bi-check-circle-fill';
            text = `You timed out at ${formatTimeLabel(session.time_out)}. You can still log attendance today. Cancel your Time Out if you are not done.`;
        } else if (session.state === 'in' && session.can_log) {
            tone = 'isOk';
            icon = 'bi-check-circle-fill';
            text = `You timed in at ${formatTimeLabel(session.time_in)}. You can log your students' attendance.`;
            if (session.has_logs) text += ' To cancel your Time In, remove the attendance you logged today first.';
        } else {
            tone = 'isWait';
            icon = 'bi-hourglass-split';
            text = session.block_reason || 'Time In to start logging attendance.';
        }

        el.banner.className = `attendanceSessionBanner ${tone}`;
        el.bannerIcon.className = `bi ${icon}`;
        // Only touch the text when it changes, so screen readers are not re-announcing it on every auto-refresh.
        if (el.bannerText.textContent !== text) el.bannerText.textContent = text;

        // The coach's own buttons. The server decides which ones apply.
        const lock = busy || !sessionLoaded;

        el.sessionTimeInButton.hidden = !session.can_time_in;
        el.sessionTimeInButton.disabled = lock;

        el.sessionTimeOutButton.hidden = !session.can_time_out;
        el.sessionTimeOutButton.disabled = lock;

        el.sessionCancelButton.hidden = !session.can_time_out;
        el.sessionCancelButton.disabled = lock || !session.can_cancel_in;
        el.sessionCancelOutButton.hidden = !session.can_cancel_out;
        el.sessionCancelOutButton.disabled = lock;

        el.sessionCancelButton.title = session.has_logs ? 'Remove the attendance you logged today first.' : '';
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

        const value = column.isTime
            ? formatTimeLabel(record[column.key])
            : (column.isState ? stateLabel(record[column.key]) : (record[column.key] ?? ''));

        return value
            ? document.createTextNode(value)
            : make('span', { className: 'attendanceEmptyValue', text: column.isTime ? '-' : '--' });
    }

    function visibleRows() {
        const term = el.search.value.trim().toLowerCase();
        const logged = dropdownValue(el.loggedFilter);   // 'all' | 'in' | 'out' | 'mine'

        let list = rows;

        // Green dot = logged in that day, red dot = not.
        if (logged === 'in' || logged === 'out') {
            list = list.filter(record => logged === 'in' ? Boolean(record.time_in) : !record.time_in);
        } else if (logged === 'mine') {
            // Only the rows this coach logged on that date.
            list = list.filter(record => record.logged_by_me);
        }

        if (!term) return list;

        return list.filter(record =>
            SEARCH_KEYS.some(key => String(key === 'state' ? stateLabel(record.state) : (record[key] ?? '')).toLowerCase().includes(term))
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
        // A row from an enrollment that has ended (shown for the record) is view only.
        const open = canLog() && !(record && record.ended);

        el.timeInButton.disabled = busy || !open || !record;
        el.timeOutButton.disabled = busy || !open || !record || !record.time_in;

        // Buttons switch to edit mode when a time is already recorded.
        el.timeInButton.textContent = record && record.time_in ? 'Time In (Edit)' : 'Time In';
        el.timeOutButton.textContent = record && record.time_out ? 'Time Out (Edit)' : 'Time Out';
        // Expected / Absent: students and athletes, only while they have not timed in.
        // When the person already has that mark, the button removes it.
        const markable = Boolean(record) && !record.time_in;
        const hasExpected = markable && record.state === 'Expecting';
        const hasAbsent = markable && record.state === 'Absent';

        el.expectingButton.disabled = busy || !open || !markable;
        el.expectingButton.textContent = hasExpected ? `Remove ${EXPECTING_LABEL}` : EXPECTING_LABEL;
        el.expectingButton.title = hasExpected
            ? `Remove the ${EXPECTING_LABEL} mark from the selection`
            : `Mark the selection as ${EXPECTING_LABEL} (no Time In / Time Out is recorded yet)`;

        el.absentButton.disabled = busy || !open || !markable;
        el.absentButton.textContent = hasAbsent ? 'Remove Absent' : 'Absent';
        el.absentButton.title = hasAbsent
            ? 'Remove the Absent mark from the selection'
            : 'Mark the selection as Absent (only if they have not timed in)';
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
                    block_reason: result.data.block_reason || null,
                    can_time_in: Boolean(result.data.session?.can_time_in),
                    can_time_out: Boolean(result.data.session?.can_time_out),
                    can_cancel_in: Boolean(result.data.session?.can_cancel_in),
                    can_cancel_out: Boolean(result.data.session?.can_cancel_out),
                    has_logs: Boolean(result.data.session?.has_logs)
                };
                sessionLoaded = true;
                el.lastUpdated.textContent = `Auto-refreshes every ${REFRESH_MS / 1000}s. Updated ${new Date().toLocaleTimeString('en-US')}`;

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
    // Keeps the page current by itself: the admin or another
    // coach may change attendance while this tab is open. Skipped while the tab
    // is hidden, a modal is open or a save is running, and requests never stack.
    let refreshing = false;

    async function refreshQuietly() {
        if (document.hidden || busy || refreshing || loading
            || !el.modalOverlay.hidden || !el.logsOverlay.hidden || !el.confirmOverlay.hidden) return;

        refreshing = true;
        try {
            if (activeTab === 'mine') {
                await loadMyLogs({ silent: true });
            } else {
                await loadRows({ keepSelection: true, silent: true });
            }
        } finally {
            refreshing = false;
        }
    }

    document.addEventListener('visibilitychange', refreshQuietly);
    setInterval(refreshQuietly, REFRESH_MS);

    /* =========================================================
       MY TIME IN / TIME OUT (the coach's own)
       No time is sent: the server stamps its own current time.
    ========================================================= */

    let confirmResolve = null;

    function askConfirm({ title, message, confirmText, cancelText = 'Cancel' }) {
        if (confirmResolve) closeConfirm(false);

        el.confirmTitle.textContent = title;
        el.confirmMessage.textContent = message;
        el.confirmOk.textContent = confirmText;
        el.confirmCancel.textContent = cancelText;
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

    const SESSION_ACTIONS = {
        in: {
            url: URLS.selfTimeIn,
            confirm: {
                title: 'Time In',
                message: 'Time in now? Your Time In uses the current time, and only the admin can edit it later.',
                confirmText: 'Time In'
            }
        },
        out: {
            url: URLS.selfTimeOut,
            confirm: {
                title: 'Time Out',
                message: 'Time out now? You can still log attendance today, and you can cancel your Time Out if you are not done.',
                confirmText: 'Time Out'
            }
        },
        cancelOut: {
            url: URLS.selfCancelOut,
            confirm: {
                title: 'Cancel Time Out',
                message: 'Cancel your Time Out? You will be timed in again and can time out later.',
                confirmText: 'Cancel Time Out',
                cancelText: 'Keep as is'
            }
        },
        cancel: {
            url: URLS.selfCancelIn,
            confirm: {
                title: 'Cancel Time In',
                message: 'Cancel your Time In? You will need to Time In again before you can log attendance.',
                confirmText: 'Cancel Time In',
                cancelText: 'Keep as is'
            }
        }
    };

    async function runSessionAction(kind) {
        const action = SESSION_ACTIONS[kind];
        if (!action || busy) return;

        if (action.confirm && !(await askConfirm(action.confirm))) return;

        busy = true;
        updateToolbarState();
        renderBanner();

        try {
            const result = await api(action.url, { method: 'POST' });

            if (result.ok) {
                showToast(result.data.message || 'Saved.', 'success');
            } else {
                toastServerErrors(result, 'Unable to save your attendance.');
            }

            // Whatever happened, show what the server now says.
            await loadRows({ keepSelection: true, silent: true });
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving your attendance.', 'danger');
        } finally {
            busy = false;
            updateToolbarState();
            renderBanner();
        }
    }

    el.sessionTimeInButton.addEventListener('click', () => runSessionAction('in'));
    el.sessionTimeOutButton.addEventListener('click', () => runSessionAction('out'));
    el.sessionCancelButton.addEventListener('click', () => runSessionAction('cancel'));
    el.sessionCancelOutButton.addEventListener('click', () => runSessionAction('cancelOut'));

    el.confirmOk.addEventListener('click', () => closeConfirm(true));
    el.confirmCancel.addEventListener('click', () => closeConfirm(false));
    el.confirmClose.addEventListener('click', () => closeConfirm(false));

    /* =========================================================
       TIME ENTRY MODAL
    ========================================================= */

    function openTimeModal(action) {
        const record = getSelectedRecord();
        if (!record || !canLog()) return;

        pendingAction = action;

        const existing = action === 'in' ? record.time_in : record.time_out;
        const isEdit = Boolean(existing);
        const label = action === 'in' ? 'Time In' : 'Time Out';

        el.modalTitle.textContent = isEdit ? `Edit ${label}` : `Record ${label}`;
        el.modalName.replaceChildren(
            document.createTextNode(isEdit ? 'Editing for ' : 'Recording for '),
            make('strong', { text: record.name })
        );

        // Show the other time for reference.
        if (action === 'out' && record.time_in) {
            el.modalMeta.textContent = `Time In: ${formatTimeLabel(record.time_in)}`;
            el.modalMeta.hidden = false;
        } else if (action === 'in' && record.time_out) {
            el.modalMeta.textContent = `Time Out: ${formatTimeLabel(record.time_out)}`;
            el.modalMeta.hidden = false;
        } else {
            el.modalMeta.hidden = true;
        }

        el.modalInput.value = existing || currentTimeInputValue();
        el.modalInput.disabled = false;
        el.modalLabel.textContent = isEdit ? label : 'Current Time';
        el.modalLabel.classList.toggle('isDimmed', isEdit);

        // Remove option only appears when there is a time to remove.
        el.modalRemove.checked = false;
        el.modalRemoveText.textContent = `Remove ${label}`;
        el.modalRemoveWrap.hidden = !isEdit;

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

        const remove = el.modalRemove.checked;
        const time = el.modalInput.value;   // "HH:MM"

        if (!remove) {
            if (!time) {
                showToast('Please choose a time.', 'danger');
                return;
            }

            if (action === 'out' && record.time_in && time <= record.time_in) {
                showToast('Time Out must be later than Time In.', 'danger');
                return;
            }

            if (action === 'in' && record.time_out && time >= record.time_out) {
                showToast('Time In must be earlier than Time Out.', 'danger');
                return;
            }
        } else if (action === 'in' && record.time_out) {
            showToast('Remove the Time Out first before removing the Time In.', 'danger');
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
                    ? { athlete_id: record.user_id, date: el.dateInput.value, time: remove ? null : time, remove }
                    : { enrollment_id: record.id, date: el.dateInput.value, time: remove ? null : time, remove }
            });

            if (result.ok) {
                closeTimeModal();
                showToast(`${action === 'in' ? 'Time In' : 'Time Out'} ${remove ? 'removed' : 'saved'} for ${record.name}.`, 'success');
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
    // Expected / Absent: marks the selected student, or removes the mark when it is already set.
    const MARKS = {
        expecting: { url: URLS.expecting, state: 'Expecting', label: EXPECTING_LABEL },
        absent: { url: URLS.absent, state: 'Absent', label: 'Absent' }
    };

    async function toggleMark(kind) {
        const mark = MARKS[kind];
        const record = getSelectedRecord();
        if (!mark || !record || busy || !canLog() || record.time_in) return;

        const removing = record.state === mark.state;

        // Ask first. Nothing else can be clicked behind the popup, and auto-refresh waits while it is open.
        const confirmed = await askConfirm(removing
            ? {
                title: `Remove ${mark.label}`,
                message: `Remove the ${mark.label} mark from ${record.name}?`,
                confirmText: 'Remove',
                cancelText: 'Keep'
            }
            : {
                title: `Mark as ${mark.label}`,
                message: kind === 'absent'
                    ? `Mark ${record.name} as Absent? This only works if they have not timed in.`
                    : `Mark ${record.name} as ${mark.label}? No Time In or Time Out is recorded yet.`,
                confirmText: `Mark ${mark.label}`
            });

        if (!confirmed) return;

        busy = true;
        updateToolbarState();

        try {
            // An athlete is sent by user id; a student by enrollment id.
            const body = record.kind === 'athlete'
                ? { athlete_id: record.user_id, date: el.dateInput.value }
                : { enrollment_id: record.id, date: el.dateInput.value };
            if (removing) body.remove = true;

            const result = await api(mark.url, { method: 'POST', body });

            if (result.ok) {
                showToast(
                    removing ? `${mark.label} removed for ${record.name}.` : `${record.name} marked as ${mark.label}.`,
                    'success'
                );
                await loadRows({ keepSelection: true, silent: true });
            } else {
                toastServerErrors(result, removing
                    ? `Unable to remove ${mark.label}.`
                    : `Unable to mark as ${mark.label}.`);

                if (result.status === 403) await loadRows({ keepSelection: true, silent: true });
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving. Please try again.', 'danger');
        } finally {
            busy = false;
            updateToolbarState();
        }
    }

    el.expectingButton.addEventListener('click', () => toggleMark('expecting'));
    el.absentButton.addEventListener('click', () => toggleMark('absent'));
    el.timeInButton.addEventListener('click', () => openTimeModal('in'));
    el.timeOutButton.addEventListener('click', () => openTimeModal('out'));
    el.modalClose.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.modalCancel.addEventListener('click', () => { if (!busy) closeTimeModal(); });
    el.modalSave.addEventListener('click', saveTimeModal);

    // Ticking "Remove" disables the time field.
    el.modalRemove.addEventListener('change', () => {
        el.modalInput.disabled = el.modalRemove.checked;
    });

    // Greys out "Current Time" once the coach edits the field.
    el.modalInput.addEventListener('input', () => el.modalLabel.classList.add('isDimmed'));

    // Clicking outside the card does NOT close a modal; Escape does.
    // (The shared dropdown script closes open dropdowns on Escape by itself.)
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        if (!el.confirmOverlay.hidden) closeConfirm(false);
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

    // "2026-10-24 16:30:00" -> "Oct 24, 2026 4:30 PM"
    function formatDateTimeLabel(value) {
        if (!value) return '';

        const d = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return String(value);

        return `${formatShortDate(d)} ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
    }

    function renderLogs(list, isLoading) {
        const headers = ['Date', 'Course', 'Time In', 'Time Out', 'Status', 'Logged By', 'Last Edited By', 'Last Edited At'];

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
                logCell(stateLabel(log.state)),
                logCell(log.logged_by),
                logCell(log.last_edited_by),
                logCell(formatDateTimeLabel(log.last_edited_at))
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
       MY ATTENDANCE TAB (the coach's own, view only)
    ========================================================= */

    function monthLabel(value) {
        const [y, m] = value.split('-').map(Number);
        return new Date(y, m - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

    function renderMyLogs() {
        const headers = ['Date', 'Time In', 'Time Out', 'Hours Worked', 'Status', 'Recorded By', 'Last Edited By', 'Last Edited At'];
        const logs = myData.logs;
        const summary = myData.summary;

        el.myTableHead.replaceChildren();
        el.myTableBody.replaceChildren();

        const headerRow = make('tr');
        headers.forEach(h => headerRow.append(make('th', { text: h, attributes: { scope: 'col' } })));
        el.myTableHead.append(headerRow);

        el.myStatDays.textContent = summary ? String(summary.days) : '--';
        el.myStatHours.textContent = summary ? summary.hours_label : '--';
        el.myStatMissing.textContent = summary ? String(summary.incomplete) : '--';

        if (myLoading || !logs.length) {
            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'attendanceEmptyState',
                text: myLoading ? 'Loading...' : `No attendance recorded for ${monthLabel(el.myMonth.value)}.`,
                attributes: { colSpan: headers.length }
            }));
            el.myTableBody.append(emptyRow);
            return;
        }

        logs.forEach(log => {
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
                logCell(formatTimeLabel(log.time_in), '-'),
                logCell(formatTimeLabel(log.time_out), '-'),
                logCell(log.worked, '-'),
                logCell(stateLabel(log.state)),
                logCell(log.logged_by),
                logCell(log.last_edited_by),
                logCell(formatDateTimeLabel(log.last_edited_at))
            ].forEach(content => {
                const cell = make('td');
                cell.append(content);
                row.append(cell);
            });

            el.myTableBody.append(row);
        });
    }

    async function loadMyLogs({ silent = false } = {}) {
        const seq = ++mySeq;

        if (!el.myMonth.value) el.myMonth.value = el.myMonth.max;

        if (!silent) {
            myLoading = true;
            renderMyLogs();
        }

        try {
            const result = await api(`${URLS.myLogs}?${new URLSearchParams({ month: el.myMonth.value })}`);
            if (seq !== mySeq) return;   // a newer request replaced this one

            myLoading = false;

            if (!result.ok) {
                if (!silent) {
                    myData = { logs: [], summary: null };
                    toastServerErrors(result, 'Unable to load your attendance.');
                }
            } else {
                myData = { logs: result.data.logs || [], summary: result.data.summary || null };
                el.myUpdated.textContent = `Auto-refreshes every ${REFRESH_MS / 1000}s. Updated ${new Date().toLocaleTimeString('en-US')}`;
            }
        } catch (error) {
            if (seq !== mySeq) return;
            console.error(error);
            myLoading = false;
            if (!silent) {
                myData = { logs: [], summary: null };
                showToast('Something went wrong while loading your attendance.', 'danger');
            }
        }

        renderMyLogs();
    }

    function setTab(tab) {
        if (tab === activeTab) return;

        activeTab = tab;
        const isMine = tab === 'mine';

        el.loggingPanel.hidden = isMine;
        el.myPanel.hidden = !isMine;

        el.tabs.querySelectorAll('[data-attendance-tab]').forEach(button => {
            const on = button.dataset.attendanceTab === tab;
            button.classList.toggle('active', on);
            button.classList.toggle('btn-primary', on);
            button.classList.toggle('btn-outline-primary', !on);
            button.setAttribute('aria-selected', String(on));
        });

        window.FBSSDropdown.close();

        if (isMine) {
            loadMyLogs();
        } else {
            // Show the current session / rows again after being away.
            loadRows({ keepSelection: true, silent: true });
        }
    }

    function initMyAttendance() {
        const thisMonth = toLocalISODate(new Date()).slice(0, 7);
        el.myMonth.max = thisMonth;        // no future months
        el.myMonth.value = thisMonth;

        el.myMonth.addEventListener('change', () => {
            if (!el.myMonth.value || el.myMonth.value > el.myMonth.max) {
                el.myMonth.value = el.myMonth.max;
            }
            loadMyLogs();
        });

        el.tabs.addEventListener('click', event => {
            const button = event.target.closest('[data-attendance-tab]');
            if (button) setTab(button.dataset.attendanceTab);
        });
    }

    /* =========================================================
       INIT
    ========================================================= */

    renderCurrentDateLine();
    initDatePicker();
    initMyAttendance();
    renderBanner();
    updateToolbarState();
    loadRows();

})();
</script>