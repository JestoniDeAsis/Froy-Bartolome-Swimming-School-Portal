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
    flex-wrap: wrap;
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

@media (max-width: 768px) {
    .attendanceContainer {
        height: 100vh;
        padding: 1rem;
        overflow-x: hidden;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }

    .attendanceTabsContainer {
        width: 100%;
        gap: .4rem;
    }

    .attendanceTab {
        min-width: 0;
        flex: 1 1 auto;
        text-align: center;
        padding: .5rem .75rem;
        font-size: .875rem;
        min-height: 44px;
    }

    .attendanceToolbar {
        gap: .75rem;
    }

    .attendanceFilterGroup {
        width: 100%;
    }

    .attendanceClassFilter,
    .attendanceLoggedFilter {
        --dd-min-width: 0;
        --dd-width: 100%;
        width: 100%;
    }

    .attendanceClassFilter .registerDropdownToggle,
    .attendanceLoggedFilter .registerDropdownToggle {
        width: 100%;
        min-width: 0;
        min-height: 44px;
        font-size: 16px;
    }

    .attendanceDatePicker {
        width: 100%;
    }

    .attendanceDateButton {
        width: 100%;
        min-width: 0;
        min-height: 44px;
        font-size: 16px;
    }

    .attendanceSearchContainer {
        width: 100%;
        min-width: 0;
    }

    .attendanceSearchInput {
        width: 100%;
        min-height: 44px;
        font-size: 16px;
    }

    #monthGroup input {
        width: 100%;
        min-height: 44px;
        font-size: 16px;
    }

    .attendanceToolbarActions {
        margin-left: 0;
        width: 100%;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .5rem;
    }

    .attendanceToolbarActions button {
        min-height: 44px;
        font-size: .8125rem;
        padding: .45rem .4rem;
        white-space: nowrap;
        text-align: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .attendanceToolbarActions button:only-child {
        grid-column: 1 / -1;
    }

    .attendanceTableContainer {
        min-height: 260px;
        -webkit-overflow-scrolling: touch;
    }

    .attendanceTable {
        font-size: .875rem;
    }

    .attendanceTable th,
    .attendanceTable td {
        padding: .65rem .75rem;
    }

    .attendanceModal {
        max-width: calc(100vw - 1.5rem);
    }

    .attendanceModal.isWide {
        max-width: 100%;
        max-height: 92vh;
        margin: 0.5rem;
    }

    .attendanceModalBody {
        padding: 1rem;
    }

    .attendanceModalInput {
        min-height: 44px;
        font-size: 16px;
    }

    .attendanceCoachPick {
        --dd-min-width: 0;
        --dd-width: 100%;
        width: 100%;
    }

    .attendanceCoachPick .registerDropdownToggle {
        width: 100%;
        min-width: 0;
        min-height: 44px;
        font-size: 16px;
    }

    .attendanceModalClose {
        min-width: 44px;
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .attendanceModalRemove {
        min-height: 44px;
    }

    .attendanceModalFooter button {
        min-height: 44px;
        min-width: 80px;
    }

    .attendanceLogToolbar {
        flex-direction: column;
        align-items: stretch;
        gap: .5rem;
    }

    .attendanceLogFilter {
        --dd-min-width: 0;
        --dd-width: 100%;
        width: 100%;
    }

    .attendanceLogFilter .registerDropdownToggle {
        width: 100%;
        min-width: 0;
        min-height: 44px;
        font-size: 16px;
    }

    .attendanceLogTableContainer {
        min-height: 180px;
    }
}

@media (max-width: 375px) {
    .attendanceContainer {
        padding: .65rem;
    }

    .attendanceTab {
        font-size: .8rem;
        padding: .45rem .35rem;
    }

    .attendanceToolbarActions button {
        font-size: .75rem;
        padding: .45rem .25rem;
    }
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
.attendanceCoachPick { --dd-min-width: 280px; }
.attendanceCoachPickGroup { display: flex; flex-direction: column; gap: .5rem; }
.attendanceCoachPickGroup[hidden] { display: none; }

.attendanceLogSummary {
    margin: 0;
    font-size: .8125rem;
    color: #667085;
}
.attendanceLogTableContainer {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #e7ebef;
    border-radius: 8px;
    background: #fff;
}

.attendanceLastUpdated { margin: .5rem 0 .25rem; font-size: .75rem; color: #98a2b3; }
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
            Coaches
        </button>
        <button class="btn btn-outline-primary attendanceTab" type="button" id="activeStudentsTabButton" data-attendance-tab="students" aria-selected="false">
            Students
        </button>
        <button class="btn btn-outline-primary attendanceTab" type="button" id="activeAthletesTabButton" data-attendance-tab="athletes" aria-selected="false">
            Athletes
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

        <!-- View: Daily or Monthly (coaches tab only) -->
        <div class="attendanceFilterGroup" id="viewFilterGroup">
            <label class="attendanceFilterLabel" id="viewFilterLabel">View</label>

            <div class="registerDropdown attendanceLoggedFilter" id="viewFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="viewFilterToggle" type="button" aria-expanded="false" aria-controls="viewFilterOptions">
                    <span id="viewFilterSelected">Daily</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="viewFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="daily">Daily</button>
                    <button class="registerDropdownOption" type="button" data-value="monthly">Monthly</button>
                </div>

                <input id="viewFilterInput" name="viewFilter" type="hidden" value="daily">
            </div>
        </div>

        <!-- Month (Monthly view only) -->
        <div class="attendanceFilterGroup" id="monthGroup" style="display: none;">
            <label class="attendanceFilterLabel" for="attendanceMonthInput">Month</label>
            <input class="form-control" id="attendanceMonthInput" type="month" aria-label="Attendance month">
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

        <!-- Time actions (every tab: add, edit or remove a time) -->
        <div class="attendanceToolbarActions" id="attendanceToolbarActions">
            <button class="btn btn-primary" id="recordTimeInButton" type="button" disabled>Time In</button>
            <button class="btn btn-primary" id="recordTimeOutButton" type="button" disabled>Time Out</button>
            <button class="btn btn-primary" id="coachAbsentButton" type="button" disabled title="Mark as Absent (only if there is no Time In)">Absent</button>
            <button class="btn btn-primary" id="coachViewLogsButton" type="button" disabled>View Logs</button>
        </div>

        <!-- Student / athlete actions (students and athletes tabs) -->
        <div class="attendanceToolbarActions" id="studentToolbarActions">
            <button class="btn btn-primary" id="markAbsentButton" type="button" disabled title="Mark as Absent (only if there is no Time In)">Absent</button>
            <button class="btn btn-primary" id="viewLogsButton" type="button" disabled>View Logs</button>
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
            <p class="attendanceModalMeta" id="timeModalAudit" hidden></p>

            <label class="attendanceModalLabel" id="timeModalLabel" for="timeModalInput">Current Time</label>
            <input class="attendanceModalInput" id="timeModalInput" type="time">

            <!-- Only shown when this creates the day's record for a student / athlete -->
            <div class="attendanceCoachPickGroup" id="timeModalCoachGroup" hidden>
                <label class="attendanceModalLabel" id="timeModalCoachLabel">Coach</label>

                <div class="registerDropdown attendanceCoachPick" id="coachPickDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" id="coachPickToggle" type="button" aria-expanded="false" aria-controls="coachPickOptions">
                        <span id="coachPickSelected">Select a coach</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>

                    <div class="registerDropdownOptions" id="coachPickOptions" hidden>
                        <button class="registerDropdownOption active" type="button" data-value="">Select a coach</button>
                    </div>

                    <input id="coachPickInput" name="coachPick" type="hidden" value="">
                </div>

                <p class="attendanceModalMeta" id="coachPickNote" hidden></p>
            </div>

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
        coachAbsent: '/admin/attendance/coaches/absent',
        handlingCoaches: '/admin/attendance/handling-coaches',
        studentLogs: '/admin/attendance/student-logs',
        athleteLogs: '/admin/attendance/athlete-logs',
        coachLogs: '/admin/attendance/coach-logs',
        record: '/admin/attendance/records'
    };

    // The stored attendance_state stays 'Expecting'; this is only the wording people read.
    const EXPECTING_LABEL = 'Expected';

    // Per-tab columns. "key" matches the field names the server returns.
    const TAB_CONFIGS = {
        coaches: {
            hint: 'Record, edit or remove the Time In and Time Out of each coach, mark them Absent, or view the full attendance log. Select a coach to enable the buttons. Switch View to Monthly for monthly totals.',
            selectable: true,
            columns: [
                { label: 'Coach Name', key: 'name', isNameColumn: true },
                { label: 'Assigned Classes', key: 'classes' },
                { label: 'Time In', key: 'time_in', isTime: true },
                { label: 'Time Out', key: 'time_out', isTime: true },
                { label: 'Status', key: 'state', isState: true }
            ],
            searchKeys: ['name', 'classes', 'state']
        },
        students: {
            hint: 'Log or correct attendance for students: Time In / Time Out (edit or remove) or Absent, or view the full log. When a student has no record yet, choose the coach who handled them.',
            selectable: true,
            columns: [
                { label: 'Student Name', key: 'name', isNameColumn: true },
                { label: 'Coach', key: 'coach' },
                { label: 'Course Level', key: 'course' },
                { label: 'Schedule', key: 'schedule' },
                { label: 'Sessions Completed', key: 'sessions' },
                { label: 'Time In', key: 'time_in', isTime: true },
                { label: 'Time Out', key: 'time_out', isTime: true },
                { label: 'Status', key: 'state', isState: true }
            ],
            searchKeys: ['name', 'coach', 'course', 'state']
        },
        athletes: {
            hint: 'Log or correct attendance for athletes: Time In / Time Out (edit or remove) or Absent, or view the full log. When an athlete has no record yet, choose the coach who handled them.',
            selectable: true,
            columns: [
                { label: 'Athlete Name', key: 'name', isNameColumn: true },
                { label: 'Time In', key: 'time_in', isTime: true },
                { label: 'Time Out', key: 'time_out', isTime: true },
                { label: 'Status', key: 'state', isState: true }
            ],
            searchKeys: ['name', 'state']
        }
    };

    // Coaches tab, Monthly view (a base for salary): totals per coach for the chosen month.
    const COACH_MONTHLY_CONFIG = {
        hint: 'Monthly totals per coach: days present and hours worked (Time In to Time Out). Select a coach and press View Logs to see the days behind the totals.',
        selectable: true,
        columns: [
            { label: 'Coach Name', key: 'name', isNameColumn: true },
            { label: 'Assigned Classes', key: 'classes' },
            { label: 'Days Present', key: 'days', isCount: true },
            { label: 'Total Hours', key: 'hours_label' },
            { label: 'Hours (decimal)', key: 'hours', isCount: true },
            { label: 'Missing Time Out', key: 'incomplete', isCount: true }
        ],
        searchKeys: ['name', 'classes']
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

        viewFilterGroup: $('viewFilterGroup'),
        viewFilter: $('viewFilterDropdown'),
        viewFilterInput: $('viewFilterInput'),
        monthGroup: $('monthGroup'),
        monthInput: $('attendanceMonthInput'),
        dateGroup: $('attendanceDateGroup'),

        absentButton: $('markAbsentButton'),
        coachAbsentButton: $('coachAbsentButton'),

        dateButton: $('attendanceDateButton'),
        dateSelected: $('attendanceDateSelected'),
        dateInput: $('attendanceDateInput'),

        search: $('attendanceSearch'),
        lastUpdated: $('lastUpdated'),

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
        modalAudit: $('timeModalAudit'),
        modalLabel: $('timeModalLabel'),
        modalInput: $('timeModalInput'),
        modalClose: $('timeModalCloseButton'),
        modalCancel: $('timeModalCancelButton'),
        modalSave: $('timeModalSaveButton'),
        modalRemoveWrap: $('timeModalRemoveWrap'),
        modalRemove: $('timeModalRemove'),
        modalRemoveText: $('timeModalRemoveText'),

        coachGroup: $('timeModalCoachGroup'),
        coachPick: $('coachPickDropdown'),
        coachPickInput: $('coachPickInput'),
        coachPickNote: $('coachPickNote')
    };

    const REFRESH_MS = 10000;   // auto-refresh every 10 seconds

    let activeTab = 'coaches';
    let monthly = false;        // Coaches tab: Monthly view
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
    let coachPickSeq = 0;
    let coachPickLoaded = false;

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

    // attendance_state -> the text people read
    const stateLabel = state => state === 'Expecting' ? EXPECTING_LABEL : (state ?? '');

    const isMonthlyView = () => activeTab === 'coaches' && monthly;
    const tabConfig = () => isMonthlyView() ? COACH_MONTHLY_CONFIG : TAB_CONFIGS[activeTab];

    const actionLabel = action => action === 'absent' ? 'Absent' : (action === 'in' ? 'Time In' : 'Time Out');

    // "2026-10-24 16:30:00" -> "Oct 24, 2026 4:30 PM"
    function formatDateTimeLabel(value) {
        if (!value) return '';

        const d = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return String(value);

        return `${formatShortDate(d)} ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
    }

    // A student / athlete with no record at all for the chosen date: saving a Time In
    // or Absent creates the row, so the admin has to say which coach handled them.
    function needsCoachPick(record, action) {
        return activeTab !== 'coaches'
            && (action === 'in' || action === 'absent')
            && !record.state && !record.time_in && !record.time_out;
    }

    function monthLabel(value) {
        const [y, m] = value.split('-').map(Number);
        return new Date(y, m - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }

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
        const present = record.days !== undefined ? record.days > 0 : Boolean(record.time_in);
        wrapper.append(
            make('span', {
                className: `attendanceStatusDot ${present ? 'present' : 'absent'}`
            }),
            document.createTextNode(record.name)
        );
        return wrapper;
    }

    function renderCell(column, record) {
        if (column.isNameColumn) return renderNameCell(record);
        if (column.isCount) return document.createTextNode(String(record[column.key] ?? 0));

        const value = column.isTime
            ? formatTimeLabel(record[column.key])
            : (column.isDateTime
                ? formatDateTimeLabel(record[column.key])
                : (column.isState ? stateLabel(record[column.key]) : (record[column.key] ?? '')));

        return value
            ? document.createTextNode(value)
            : make('span', {
                className: 'attendanceEmptyValue',
                text: column.isTime ? '-' : '--'
            });
    }

    function visibleRows() {
        const config = tabConfig();
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
                String(key === 'state' ? stateLabel(record.state) : (record[key] ?? '')).toLowerCase().includes(term)
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
        const config = tabConfig();
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
        if (!tabConfig().selectable) return;

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
        const monthlyView = isMonthlyView();

        // Time In / Time Out: add, edit or remove a time (the modal has a Remove option).
        el.timeInButton.disabled = busy || !record;
        el.timeOutButton.disabled = busy || !record || !record.time_in;

        // Buttons switch to edit mode when a time is already recorded.
        el.timeInButton.textContent = record && record.time_in ? 'Time In (Edit)' : 'Time In';
        el.timeOutButton.textContent = record && record.time_out ? 'Time Out (Edit)' : 'Time Out';

        // Absent (every tab): marks, or removes the mark when it is already set.
        const markable = Boolean(record) && !record.time_in;
        const hasAbsent = markable && record.state === 'Absent';
        const absentText = hasAbsent ? 'Remove Absent' : 'Absent';

        el.absentButton.disabled = busy || !markable || isCoaches;
        el.absentButton.textContent = absentText;

        el.coachAbsentButton.disabled = busy || !markable || !isCoaches;
        el.coachAbsentButton.textContent = absentText;
        el.coachAbsentButton.style.display = isCoaches && !monthlyView ? '' : 'none';

        el.viewLogsButton.disabled = busy || !record || isCoaches;
        el.coachViewLogsButton.disabled = busy || !record || !isCoaches;

        // Which controls show for which tab / view.
        el.timeInButton.style.display = monthlyView ? 'none' : '';
        el.timeOutButton.style.display = monthlyView ? 'none' : '';
        el.coachViewLogsButton.style.display = isCoaches ? '' : 'none';
        el.studentActions.style.display = isCoaches ? 'none' : '';
        el.loggedFilterGroup.style.display = activeTab === 'students' ? '' : 'none';

        el.viewFilterGroup.style.display = isCoaches ? '' : 'none';
        el.dateGroup.style.display = monthlyView ? 'none' : '';
        el.monthGroup.style.display = monthlyView ? '' : 'none';

        // Athletes have no registered courses, so the class filter is hidden there.
        el.classFilterGroup.style.display = activeTab === 'athletes' ? 'none' : '';
    }

    /* =========================================================
       LOAD DATA (tab + date + class are all sent to the server)
    ========================================================= */

    async function loadRows({ keepSelection = false, silent = false } = {}) {
        const seq = ++loadSeq;

        if (!el.dateInput.value) {
            el.dateInput.value = el.dateInput.max;
        }

        if (!keepSelection) selectedId = null;

        // A silent (auto) refresh swaps the data in place without a "Loading..." flash.
        if (!silent) {
            loading = true;
            updateToolbarState();
            renderTable();
        }

        const params = new URLSearchParams({
            tab: activeTab,
            date: el.dateInput.value
        });

        if (activeTab !== 'athletes' && el.classFilterInput.value) {
            params.set('course_id', el.classFilterInput.value);
        }

        if (isMonthlyView() && el.monthInput.value) {
            params.set('month', el.monthInput.value);
        }

        try {
            const result = await api(`${URLS.data}?${params}`);

            if (seq !== loadSeq) return;

            loading = false;

            if (!result.ok) {
                if (!silent) {
                    rows = [];
                    toastServerErrors(result, 'Unable to load attendance.');
                }
            } else {
                rows = (result.data.rows || []).map(r => ({ ...r, id: String(r.id) }));
                el.lastUpdated.textContent = `Auto-refreshes every ${REFRESH_MS / 1000}s. Updated ${new Date().toLocaleTimeString('en-US')}`;

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

            if (!silent) {
                rows = [];
                showToast('Something went wrong while loading attendance.', 'danger');
            }
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

        el.hint.textContent = tabConfig().hint;
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
        if (!record || isMonthlyView()) return;

        pendingAction = action;

        const isAbsent = action === 'absent';
        const existing = action === 'in' ? record.time_in : (isAbsent ? null : record.time_out);
        const isEdit = Boolean(existing);
        const label = actionLabel(action);

        el.modalTitle.textContent = isAbsent ? 'Mark as Absent' : (isEdit ? `Edit ${label}` : `Record ${label}`);

        el.modalName.replaceChildren(
            document.createTextNode(isAbsent ? 'Marking absent: ' : (isEdit ? 'Editing for ' : 'Recording for ')),
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

        // Who logged this row and who changed it last (when there is a row).
        const loggedBy = record.coach || record.logged_by;
        const auditParts = [];

        if (loggedBy) auditParts.push(`Logged by ${loggedBy}`);

        if (record.last_edited_by) {
            const when = formatDateTimeLabel(record.last_edited_at);
            auditParts.push(`Last edited by ${record.last_edited_by}${when ? ' on ' + when : ''}`);
        }

        el.modalAudit.textContent = auditParts.join(' · ');
        el.modalAudit.hidden = !auditParts.length;

        el.modalInput.value = existing || currentTimeInputValue();
        el.modalInput.disabled = false;
        el.modalLabel.textContent = isEdit ? label : 'Current Time';
        el.modalLabel.classList.toggle('isDimmed', isEdit);

        // Remove option only appears when there is a time to remove.
        el.modalRemove.checked = false;
        el.modalRemoveText.textContent = `Remove ${label}`;
        el.modalRemoveWrap.hidden = !isEdit;

        // Absent has no time to enter.
        el.modalLabel.hidden = isAbsent;
        el.modalInput.hidden = isAbsent;

        // The coach dropdown only appears when this save creates the day's record.
        if (needsCoachPick(record, action)) {
            loadCoachPick(record);
        } else {
            el.coachGroup.hidden = true;
        }

        el.modalOverlay.hidden = false;
        if (!isAbsent) el.modalInput.focus();
    }

    // Fills the "Coach" dropdown: coaches who handle this student's course,
    // or, for athletes, coaches who handle athletes.
    async function loadCoachPick(record) {
        const seq = ++coachPickSeq;
        const isAthlete = activeTab === 'athletes';
        const noun = isAthlete ? 'athlete' : 'student';

        coachPickLoaded = false;
        el.coachGroup.hidden = false;
        el.coachPickNote.hidden = true;

        window.FBSSDropdown.setOptions(el.coachPick, [{ value: '', label: 'Loading coaches...' }], '');

        // The server only lists coaches who have timed in on this date.
        const params = new URLSearchParams({ date: el.dateInput.value });
        if (!isAthlete) params.set('enrollment_id', record.id);

        try {
            const result = await api(`${URLS.handlingCoaches}?${params}`);
            if (seq !== coachPickSeq) return;

            if (!result.ok) {
                window.FBSSDropdown.setOptions(el.coachPick, [{ value: '', label: 'Unable to load coaches' }], '');
                toastServerErrors(result, 'Unable to load the coaches.');
                return;
            }

            const coaches = result.data.coaches || [];

            window.FBSSDropdown.setOptions(
                el.coachPick,
                [{ value: '', label: coaches.length ? 'Select a coach' : 'No coach available' }]
                    .concat(coaches.map(c => ({ value: String(c.id), label: c.name }))),
                ''
            );

            if (!coaches.length) {
                el.coachPickNote.textContent = (result.data.total || 0) > 0
                    ? 'No eligible coach has timed in on this date. Record the coach\'s Time In first on the Coaches tab.'
                    : (isAthlete
                        ? 'No active coach is marked as handling athletes.'
                        : 'No active coach is assigned to this student\'s course.');
                el.coachPickNote.hidden = false;
            } else {
                coachPickLoaded = true;
            }
        } catch (error) {
            if (seq !== coachPickSeq) return;

            console.error(error);
            window.FBSSDropdown.setOptions(el.coachPick, [{ value: '', label: 'Unable to load coaches' }], '');
            showToast(`Something went wrong while loading the coaches for this ${noun}.`, 'danger');
        }
    }

    function closeTimeModal() {
        coachPickSeq++;
        coachPickLoaded = false;
        el.coachGroup.hidden = true;
        el.modalLabel.hidden = false;
        el.modalInput.hidden = false;
        el.modalOverlay.hidden = true;
        pendingAction = null;
    }

    // Coaches use their own endpoints; students and athletes share one (kind = in | out | absent).
    function buildTimeRequest(record, action, remove, time, pickedCoachId = null) {
        const base = { date: el.dateInput.value, time: (remove || action === 'absent') ? null : time, remove };

        if (activeTab === 'coaches') {
            return {
                url: action === 'absent' ? URLS.coachAbsent : (action === 'in' ? URLS.timeIn : URLS.timeOut),
                body: { coach_id: record.id, ...base }
            };
        }

        const body = { kind: action, [activeTab === 'athletes' ? 'athlete_id' : 'enrollment_id']: record.id, ...base };

        // Only sent when this creates the day's record; becomes "Logged by".
        if (pickedCoachId) body.coach_id = pickedCoachId;

        return { url: URLS.record, body };
    }

    // Absent: marks the selected student or athlete, or removes the mark when it is already set.
    async function toggleAbsent() {
        const record = getSelectedRecord();
        if (!record || busy || isMonthlyView() || record.time_in) return;

        const removing = record.state === 'Absent';

        // No record yet for a student / athlete: ask which coach handled them first.
        if (!removing && needsCoachPick(record, 'absent')) {
            openTimeModal('absent');
            return;
        }

        busy = true;
        updateToolbarState();

        try {
            const request = buildTimeRequest(record, 'absent', removing, null);
            const result = await api(request.url, { method: 'POST', body: request.body });

            if (result.ok) {
                showToast(
                    removing ? `Absent removed for ${record.name}.` : `${record.name} marked as Absent.`,
                    'success'
                );
                await loadRows({ keepSelection: true });
            } else {
                toastServerErrors(result, removing ? 'Unable to remove Absent.' : 'Unable to mark as Absent.');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving. Please try again.', 'danger');
        } finally {
            busy = false;
            updateToolbarState();
        }
    }

    async function saveTimeModal() {
        if (busy) return;

        const record = getSelectedRecord();
        const action = pendingAction;

        if (!record || !action || isMonthlyView()) {
            closeTimeModal();
            return;
        }

        const isAbsent = action === 'absent';
        const remove = !isAbsent && el.modalRemove.checked;
        const time = el.modalInput.value;

        // A new record needs the coach who handled this student / athlete.
        let pickedCoachId = null;

        if (!remove && needsCoachPick(record, action)) {
            pickedCoachId = el.coachPickInput.value || null;

            if (!pickedCoachId) {
                showToast(`Please choose the coach who handled this ${activeTab === 'athletes' ? 'athlete' : 'student'}.`, 'danger');
                return;
            }
        }

        if (isAbsent) {
            /* no time to check */
        } else if (!remove) {
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
            const request = buildTimeRequest(record, action, remove, time, pickedCoachId);
            const result = await api(request.url, { method: 'POST', body: request.body });

            if (result.ok) {
                closeTimeModal();

                showToast(
                    isAbsent
                        ? `${record.name} marked as Absent.`
                        : `${actionLabel(action)} ${remove ? 'removed' : 'saved'} for ${record.name}.`,
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
    el.absentButton.addEventListener('click', toggleAbsent);
    el.coachAbsentButton.addEventListener('click', toggleAbsent);

    el.modalClose.addEventListener('click', () => {
        if (!busy) closeTimeModal();
    });

    el.modalCancel.addEventListener('click', () => {
        if (!busy) closeTimeModal();
    });

    el.modalSave.addEventListener('click', saveTimeModal);

    // Greys out "Current Time" once the admin edits the field.
    // Ticking "Remove" disables the time field.
    el.modalRemove.addEventListener('change', () => {
        el.modalInput.disabled = el.modalRemove.checked;
    });

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

    function renderLogs(list, isLoading, isAthlete = false, withHours = false, summaryText = null) {
        const headers = isAthlete
            ? ['Date', 'Time In', 'Time Out', 'Status', 'Logged By', 'Last Edited By', 'Last Edited At']
            : ['Date', 'Course', 'Time In', 'Time Out', 'Status', 'Logged By', 'Last Edited By', 'Last Edited At'];

        if (withHours) headers.push('Hours');

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
                stateLabel(log.state),
                log.logged_by,
                log.last_edited_by,
                formatDateTimeLabel(log.last_edited_at)
            );

            if (withHours) values.push(log.worked);

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

        el.logsSummary.textContent = summaryText
            || `${list.length} log${list.length === 1 ? '' : 's'} · ${attended} attended`;
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

        const isCoach = activeTab === 'coaches';

        renderLogs([], true, true, isCoach);

        const params = new URLSearchParams({
            [isCoach ? 'coach_id' : 'athlete_id']: String(logsAnchorId)
        });

        // Monthly view: only that month's days, with the month's totals.
        if (isMonthlyView() && el.monthInput.value) params.set('month', el.monthInput.value);

        try {
            const result = await api(`${activeTab === 'coaches' ? URLS.coachLogs : URLS.athleteLogs}?${params}`);

            if (seq !== logsSeq) return;

            if (!result.ok) {
                renderLogs([], false, true, isCoach);
                toastServerErrors(result, 'Unable to load the attendance log.');
                return;
            }

            const t = result.data.summary;
            const summaryText = isMonthlyView() && t
                ? `${t.days} day${t.days === 1 ? '' : 's'} present · ${t.hours_label} (${t.hours} h) · ${t.incomplete} missing Time Out`
                : null;

            renderLogs(result.data.logs || [], false, true, isCoach, summaryText);
        } catch (error) {
            if (seq !== logsSeq) return;

            console.error(error);
            renderLogs([], false, true, isCoach);
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

        if (isMonthlyView() && el.monthInput.value) {
            el.logsStudent.append(document.createTextNode(` · ${monthLabel(el.monthInput.value)}`));
        }

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

    el.viewFilter.addEventListener('dropdown:change', () => {
        monthly = el.viewFilterInput.value === 'monthly';
        selectedId = null;

        updateTabButtons();
        updateToolbarState();
        loadRows();
    });

    function initMonthPicker() {
        const thisMonth = toLocalISODate(new Date()).slice(0, 7);

        el.monthInput.max = thisMonth;
        el.monthInput.value = thisMonth;

        el.monthInput.addEventListener('change', () => {
            if (!el.monthInput.value || el.monthInput.value > el.monthInput.max) {
                el.monthInput.value = el.monthInput.max;
            }

            if (isMonthlyView()) loadRows();
        });
    }
    el.logFilter.addEventListener('dropdown:change', () => {
        if (activeTab === 'students' && !el.logsOverlay.hidden) {
            loadStudentLogs();
        }
    });

    /* =========================================================
       AUTO-REFRESH
       Keeps the list current while the page is open (coaches log
       attendance and time themselves in and out all day). Skipped
       while the tab is hidden, a save is running or a modal is open.
    ========================================================= */

    let refreshing = false;

    async function refreshQuietly() {
        if (document.hidden || busy || refreshing || loading
            || !el.modalOverlay.hidden || !el.logsOverlay.hidden) return;

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
       INIT
    ========================================================= */

    renderCurrentDateLine();
    initDatePicker();
    initMonthPicker();
    updateTabButtons();
    updateToolbarState();
    loadRows();

})();
</script>