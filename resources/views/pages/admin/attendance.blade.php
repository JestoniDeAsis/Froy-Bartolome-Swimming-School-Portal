<style>
/* =========================================================
   Attendance
   (mirrors the structure/conventions of the Registers section:
   same header/tabs/toolbar/table skeleton, same shared
   .registerDropdown component from our_theme.css, same
   "trimmed to a few sample records" in-memory mockup pattern.
   Class/element names below are Attendance-specific so they
   don't collide with the Registers page's own classes.)
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

/* =========================================================
   Tabs (Active Students / Active Coaches)
========================================================= */

.attendanceTabsContainer {
    flex: 0 0 auto;
    display: flex;
    gap: .5rem;
    margin-bottom: .75rem;
}

.attendanceTab {
    min-width: 150px;
}

/* =========================================================
   Toolbar
========================================================= */

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

/* Class filter reuses the shared .registerDropdown component
   (see our_theme.css) — only the width is scoped here. */
.attendanceClassFilter .registerDropdownToggle {
    min-width: 160px;
}

/* Date picker: a styled button with a real <input type="date">
   layered underneath so the native picker still opens, while the
   visible label can read "Today - Aug 10, 2026". The hidden input
   has pointer-events disabled so it never steals the click — the
   button is the single click target for the WHOLE control, and
   its JS handler opens the picker via showPicker(). Without this,
   only the browser's own tiny calendar-icon hit-region inside the
   hidden input would open the picker, since clicks on it never
   bubble to the (sibling) button. */
.attendanceDatePicker {
    position: relative;
}

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

.attendanceDateButton i {
    margin-left: auto;
    color: #6c757d;
}

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

/* =========================================================
   Table
========================================================= */

.attendanceTableContainer {
    --attendance-row-height: 49px;
    --attendance-header-height: 49px;
    --attendance-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;

    max-height: calc(
        var(--attendance-header-height) +
        (var(--attendance-row-height) * var(--attendance-visible-rows))
    );

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
.attendanceTable td {
    padding: .75rem 1rem;
    vertical-align: middle;
}

/* Rows are clickable — selecting one enables the "Time In" /
   "Time Out" toolbar buttons above, same pattern as the
   Registers page's clickable .studentRow. */
.attendanceRow {
    cursor: pointer;
    transition: background-color .1s ease;
}

.attendanceRow:hover {
    background: #f8fafb;
}

.attendanceRow.selectedRow,
.attendanceRow.selectedRow:hover,
.attendanceRow.selectedRow > td {
    background: #e7f5ff;
}

.attendanceEmptyState {
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}

/* Name cell: status dot + name */
.attendanceNameCell {
    display: inline-flex;
    align-items: center;
    gap: .55rem;
}

.attendanceStatusDot {
    flex-shrink: 0;
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.attendanceStatusDot.present {
    background: #2f9e44;
}

.attendanceStatusDot.absent {
    background: #e03131;
}

.attendanceEmptyValue {
    color: #adb5bd;
}

@media (max-width: 575.98px) {
    .attendanceContainer {
        padding: 1rem;
    }

    .attendanceToolbarActions {
        margin-left: 0;
        width: 100%;
    }

    .attendanceToolbarActions button {
        flex: 1 1 auto;
    }
}

/* =========================================================
   Time Entry Modal (opened by Time In / Time Out)
========================================================= */

.attendanceModalOverlay {
    position: fixed;
    inset: 0;
    z-index: 1000;
    background: rgba(16, 24, 40, .45);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.attendanceModalOverlay[hidden] {
    display: none;
}

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

.attendanceModalHeader h5 {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}

.attendanceModalClose {
    border: none;
    background: transparent;
    font-size: 1.25rem;
    line-height: 1;
    color: #667085;
    cursor: pointer;
    padding: 0;
}

.attendanceModalBody {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

.attendanceModalName {
    margin: 0 0 .5rem;
    font-size: .875rem;
    color: #344054;
}

.attendanceModalName strong {
    color: #101828;
}

.attendanceModalLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #101828;
    transition: color .15s ease;
}

.attendanceModalLabel.isDimmed {
    color: #adb5bd;
}

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
</style>


<!-- =========================================================
     ATTENDANCE SECTION
========================================================= -->

<section class="attendanceContainer" id="attendanceContainer" aria-labelledby="attendanceTitle">

    <header class="attendanceHeader">
        <h2 class="mb-1" id="attendanceTitle">Attendance</h2>
        <p class="text-muted mb-0" id="attendanceDescription"></p>
    </header>


    <!-- =====================================================
         TABS
    ====================================================== -->

    <nav class="attendanceTabsContainer" id="attendanceTabs" aria-label="Attendance sections">
        <button class="btn btn-primary attendanceTab active" type="button" id="activeStudentsTabButton" data-attendance-tab="students" aria-selected="true">
            Active Students
        </button>
        <button class="btn btn-outline-primary attendanceTab" type="button" id="activeCoachesTabButton" data-attendance-tab="coaches" aria-selected="false">
            Active Coaches
        </button>
    </nav>


    <!-- =====================================================
         TOOLBAR
    ====================================================== -->

    <div class="attendanceToolbar">

        <!-- Select Class -->
        <div class="attendanceFilterGroup" id="classFilterGroup">
            <label class="attendanceFilterLabel" id="classFilterLabel">Select Class</label>

            <div class="registerDropdown attendanceClassFilter" id="classFilterDropdown" data-dropdown>
                <button class="registerDropdownToggle" id="classFilterToggle" type="button" aria-expanded="false" aria-controls="classFilterOptions">
                    <span id="classFilterSelected">Beginner</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="classFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="Beginner">Beginner</button>
                    <button class="registerDropdownOption" type="button" data-value="Intermediate">Intermediate</button>
                    <button class="registerDropdownOption" type="button" data-value="Advanced">Advanced</button>
                </div>

                <input id="classFilterInput" name="classFilter" type="hidden" value="Beginner">
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

        <!-- Time Actions (enabled once a row is selected; hidden entirely on the Active Students tab) -->
        <div class="attendanceToolbarActions" id="attendanceToolbarActions">
            <button class="btn btn-primary" id="recordTimeInButton" type="button" disabled>Time In</button>
            <button class="btn btn-primary" id="recordTimeOutButton" type="button" disabled>Time Out</button>
        </div>

    </div>


    <!-- =====================================================
         TABLE
    ====================================================== -->

    <div class="attendanceTableContainer" id="attendanceTableContainer">
        <table class="table attendanceTable" id="attendanceTable">
            <thead id="attendanceTableHead"></thead>
            <tbody id="attendanceTableBody"></tbody>
        </table>
    </div>

</section>


<!-- =========================================================
     TIME ENTRY MODAL (used for both Time In and Time Out)
========================================================= -->

<div class="attendanceModalOverlay" id="timeModalOverlay" hidden>
    <div class="attendanceModal" id="timeModal" role="dialog" aria-modal="true" aria-labelledby="timeModalTitle">

        <div class="attendanceModalHeader">
            <h5 id="timeModalTitle">Record Time In</h5>
            <button class="attendanceModalClose" id="timeModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="attendanceModalBody">
            <p class="attendanceModalName" id="timeModalRecordName"></p>

            <label class="attendanceModalLabel" id="timeModalLabel" for="timeModalInput">Current Time</label>
            <input class="attendanceModalInput" id="timeModalInput" type="time">
        </div>

        <div class="attendanceModalFooter">
            <button class="btn btn-outline-secondary" id="timeModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="timeModalSaveButton" type="button">Save</button>
        </div>

    </div>
</div>


<script>
(function () {
    'use strict';

    /* =========================================================
       IN-MEMORY DATA — same "no database, everything lives in
       these arrays" approach as the Registers page. Trimmed to a
       handful of sample records per list for mockup purposes.
    ========================================================= */

    const CLASS_OPTIONS = ['Beginner', 'Intermediate', 'Advanced'];

    let activeStudents = [
        { id: 1, name: 'John Doe',        coach: 'Danica', courseLevel: 'Beginner', sessionsCompleted: '3/8', timeIn: '8:00 AM', timeOut: '' },
        { id: 2, name: 'Paquito Santos',  coach: 'Danica', courseLevel: 'Beginner', sessionsCompleted: '5/8', timeIn: '8:02 AM', timeOut: '' },
        { id: 3, name: 'Maria Clara',     coach: '',       courseLevel: 'Beginner', sessionsCompleted: '2/8', timeIn: '',        timeOut: '' },
        { id: 4, name: 'Alfonso Reyes',   coach: 'Froy',   courseLevel: 'Beginner', sessionsCompleted: '4/8', timeIn: '8:05 AM', timeOut: '' },
        { id: 5, name: 'Dina Santos',     coach: '',       courseLevel: 'Beginner', sessionsCompleted: '6/8', timeIn: '',        timeOut: '' }
    ];

    let activeCoaches = [
        { id: 501, name: 'Danica Reyes',   assignedClass: 'Beginner',     courseLevel: 'Beginner',     timeIn: '7:45 AM', timeOut: '' },
        { id: 502, name: 'Froilan Bautista', assignedClass: 'Beginner',   courseLevel: 'Beginner',     timeIn: '7:50 AM', timeOut: '' },
        { id: 503, name: 'Ana Lim',        assignedClass: 'Intermediate', courseLevel: 'Intermediate', timeIn: '',         timeOut: '' }
    ];

    /* =========================================================
       TAB CONFIG — maps each tab to its live data and columns.
    ========================================================= */

    const attendanceTabConfigs = {
        students: {
            getRows: () => activeStudents,
            columns: [
                { label: 'Student Name', key: 'name', isNameColumn: true },
                { label: 'Coach', key: 'coach' },
                { label: 'Course Level', key: 'courseLevel' },
                { label: 'Sessions Completed', key: 'sessionsCompleted' },
                { label: 'Time In', key: 'timeIn' },
                { label: 'Time Out', key: 'timeOut' }
            ]
        },
        coaches: {
            getRows: () => activeCoaches,
            columns: [
                { label: 'Coach Name', key: 'name', isNameColumn: true },
                { label: 'Assigned Class', key: 'assignedClass' },
                { label: 'Course Level', key: 'courseLevel' },
                { label: 'Time In', key: 'timeIn' },
                { label: 'Time Out', key: 'timeOut' }
            ]
        }
    };

    /* =========================================================
       ELEMENTS
    ========================================================= */

    const elements = {
        description: document.getElementById('attendanceDescription'),
        tabs: document.getElementById('attendanceTabs'),

        tableContainer: document.getElementById('attendanceTableContainer'),
        tableHead: document.getElementById('attendanceTableHead'),
        tableBody: document.getElementById('attendanceTableBody'),

        classFilterInput: document.getElementById('classFilterInput'),

        dateButton: document.getElementById('attendanceDateButton'),
        dateSelected: document.getElementById('attendanceDateSelected'),
        dateInput: document.getElementById('attendanceDateInput'),

        timeActionsContainer: document.getElementById('attendanceToolbarActions'),
        recordTimeInButton: document.getElementById('recordTimeInButton'),
        recordTimeOutButton: document.getElementById('recordTimeOutButton'),

        // Time entry modal
        timeModalOverlay: document.getElementById('timeModalOverlay'),
        timeModalTitle: document.getElementById('timeModalTitle'),
        timeModalRecordName: document.getElementById('timeModalRecordName'),
        timeModalLabel: document.getElementById('timeModalLabel'),
        timeModalInput: document.getElementById('timeModalInput'),
        timeModalCloseButton: document.getElementById('timeModalCloseButton'),
        timeModalCancelButton: document.getElementById('timeModalCancelButton'),
        timeModalSaveButton: document.getElementById('timeModalSaveButton')
    };

    /* =========================================================
       STATE
    ========================================================= */

    let activeAttendanceTab = 'students'; // 'students' | 'coaches'
    let selectedRecordId = null;          // id of the currently selected row, within activeAttendanceTab
    let pendingModalAction = null;        // 'in' | 'out' — which field the open modal is writing to

    /* =========================================================
       HELPERS
    ========================================================= */

    function createElement(tag, options = {}) {
        const element = document.createElement(tag);
        if (options.className) element.className = options.className;
        if (options.id) element.id = options.id;
        if (options.text !== undefined) element.textContent = options.text;
        if (options.attributes) {
            Object.entries(options.attributes).forEach(([name, value]) => element.setAttribute(name, value));
        }
        return element;
    }

    function capitalize(value) {
        if (!value) return '';
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function formatLongDate(date) {
        return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }

    function formatShortDate(date) {
        return date.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function isSameDay(a, b) {
        return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function currentTimeLabel() {
        return new Date().toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    // "HH:MM" (24h) — the format <input type="time"> expects/returns.
    function currentTimeInputValue() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    // Converts an <input type="time"> value ("HH:MM", 24h) into a
    // display label like "8:05 AM".
    function formatTimeInputValue(value) {
        if (!value) return '';
        const [hoursStr, minutesStr] = value.split(':');
        const reference = new Date();
        reference.setHours(Number(hoursStr), Number(minutesStr), 0, 0);
        return reference.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    /* =========================================================
       HEADER DATE LINE
    ========================================================= */

    function renderCurrentDateLine() {
        elements.description.textContent = `Current date: ${formatLongDate(new Date())}`;
    }

    /* =========================================================
       ATTENDANCE DATE PICKER
    ========================================================= */

    function initAttendanceDatePicker() {
        const today = new Date();
        elements.dateInput.value = today.toISOString().slice(0, 10);
        updateAttendanceDateLabel();

        // The hidden <input type="date"> has pointer-events: none (see CSS),
        // so the visible button is the only click target for the WHOLE
        // control — text, padding, and icon alike — not just wherever the
        // browser happens to render its own internal calendar-icon hotspot.
        elements.dateButton.addEventListener('click', () => {
            if (typeof elements.dateInput.showPicker === 'function') {
                elements.dateInput.showPicker();
            } else {
                elements.dateInput.focus();
                elements.dateInput.click();
            }
        });

        elements.dateInput.addEventListener('change', () => {
            updateAttendanceDateLabel();
            // A live app would re-fetch attendance for the chosen date;
            // this mockup keeps a single in-memory dataset regardless.
        });
    }

    function updateAttendanceDateLabel() {
        if (!elements.dateInput.value) {
            elements.dateSelected.textContent = 'Select date';
            return;
        }

        const [year, month, day] = elements.dateInput.value.split('-').map(Number);
        const picked = new Date(year, month - 1, day);
        const today = new Date();

        elements.dateSelected.textContent = isSameDay(picked, today)
            ? `Today - ${formatShortDate(picked)}`
            : formatShortDate(picked);
    }

    /* =========================================================
       STATUS DOT / NAME CELL
    ========================================================= */

    function renderNameCell(record) {
        const wrapper = createElement('span', { className: 'attendanceNameCell' });

        const isPresent = Boolean(record.timeIn);

        wrapper.append(
            createElement('span', { className: `attendanceStatusDot ${isPresent ? 'present' : 'absent'}` }),
            document.createTextNode(record.name)
        );

        return wrapper;
    }

    function renderTimeValue(value) {
        return value
            ? document.createTextNode(value)
            : createElement('span', { className: 'attendanceEmptyValue', text: '-' });
    }

    function renderPlainValue(value) {
        return value
            ? document.createTextNode(value)
            : createElement('span', { className: 'attendanceEmptyValue', text: '--' });
    }

    function renderCell(column, record) {
        const value = record[column.key] ?? '';

        if (column.isNameColumn) return renderNameCell(record);
        if (column.key === 'timeIn' || column.key === 'timeOut') return renderTimeValue(value);

        return renderPlainValue(value);
    }

    /* =========================================================
       ROW SELECTION
    ========================================================= */

    function selectAttendanceRow(recordId) {
        selectedRecordId = (selectedRecordId === recordId) ? null : recordId;
        updateToolbarActionState();
        renderAttendanceTable();
    }

    function getSelectedRecord() {
        if (selectedRecordId === null) return null;
        return attendanceTabConfigs[activeAttendanceTab].getRows().find(record => record.id === selectedRecordId) || null;
    }

    function updateToolbarActionState() {
        const record = getSelectedRecord();

        elements.recordTimeInButton.disabled = !record || Boolean(record.timeIn);
        elements.recordTimeOutButton.disabled = !record || !record.timeIn || Boolean(record.timeOut);
    }

    // Hides the Time In / Time Out toolbar actions on the Active Students
    // tab; they remain visible for Active Coaches.
    function updateTimeActionsVisibility() {
        const isStudentsTab = activeAttendanceTab === 'students';
        elements.timeActionsContainer.style.display = isStudentsTab ? 'none' : 'flex';
    }

    /* =========================================================
       TIME ENTRY MODAL
    ========================================================= */

    function openTimeModal(action) {
        const record = getSelectedRecord();
        if (!record) return;

        pendingModalAction = action; // 'in' | 'out'

        elements.timeModalTitle.textContent = action === 'in' ? 'Record Time In' : 'Record Time Out';
        elements.timeModalRecordName.innerHTML = '';
        elements.timeModalRecordName.append(
            document.createTextNode('Recording for '),
            createElement('strong', { text: record.name })
        );

        // Default to the current time, and reset the label to its
        // normal (non-dimmed) state each time the modal opens.
        elements.timeModalInput.value = currentTimeInputValue();
        elements.timeModalLabel.textContent = 'Current Time';
        elements.timeModalLabel.classList.remove('isDimmed');

        elements.timeModalOverlay.hidden = false;
        elements.timeModalInput.focus();
    }

    function closeTimeModal() {
        elements.timeModalOverlay.hidden = true;
        pendingModalAction = null;
    }

    function saveTimeModal() {
        const record = getSelectedRecord();
        if (!record || !pendingModalAction) {
            closeTimeModal();
            return;
        }

        const formattedTime = formatTimeInputValue(elements.timeModalInput.value);
        if (!formattedTime) return; // no time chosen — do nothing

        if (pendingModalAction === 'in') {
            record.timeIn = formattedTime;
        } else {
            record.timeOut = formattedTime;
        }

        closeTimeModal();
        updateToolbarActionState();
        renderAttendanceTable();
    }

    elements.recordTimeInButton.addEventListener('click', () => openTimeModal('in'));
    elements.recordTimeOutButton.addEventListener('click', () => openTimeModal('out'));

    elements.timeModalCloseButton.addEventListener('click', closeTimeModal);
    elements.timeModalCancelButton.addEventListener('click', closeTimeModal);
    elements.timeModalSaveButton.addEventListener('click', saveTimeModal);

    // Clicking the dimmed backdrop also closes the modal.
    elements.timeModalOverlay.addEventListener('click', event => {
        if (event.target === elements.timeModalOverlay) closeTimeModal();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !elements.timeModalOverlay.hidden) closeTimeModal();
    });

    // As soon as the admin edits the time field, grey out the
    // "Current Time" label to show it's no longer the live default.
    elements.timeModalInput.addEventListener('input', () => {
        elements.timeModalLabel.classList.add('isDimmed');
    });

    /* =========================================================
       RENDER TABLE
    ========================================================= */

    function renderAttendanceTable() {
        const config = attendanceTabConfigs[activeAttendanceTab];
        const classFilter = elements.classFilterInput.value.trim().toLowerCase();

        const rows = config.getRows().filter(record => {
            const courseValue = String(record.courseLevel || '').toLowerCase();
            return !classFilter || courseValue === classFilter;
        });

        elements.tableHead.replaceChildren();
        elements.tableBody.replaceChildren();

        /* HEADER */

        const headerRow = createElement('tr');
        headerRow.append(createElement('th', { text: 'No.', attributes: { scope: 'col' } }));

        config.columns.forEach(column => {
            headerRow.append(createElement('th', { text: column.label, attributes: { scope: 'col' } }));
        });

        elements.tableHead.append(headerRow);

        /* EMPTY STATE */

        if (!rows.length) {
            const emptyRow = createElement('tr');
            emptyRow.append(createElement('td', {
                className: 'attendanceEmptyState',
                text: 'No matching records found.',
                attributes: { colSpan: config.columns.length + 1 }
            }));
            elements.tableBody.append(emptyRow);
            return;
        }

        /* ROWS */

        rows.forEach((record, rowIndex) => {
            const tableRow = createElement('tr', {
                id: `attendanceRow${capitalize(activeAttendanceTab)}${rowIndex}`,
                className: 'attendanceRow'
            });

            if (selectedRecordId === record.id) tableRow.classList.add('selectedRow');

            tableRow.addEventListener('click', () => selectAttendanceRow(record.id));

            tableRow.append(createElement('td', { text: String(rowIndex + 1) }));

            config.columns.forEach(column => {
                const cell = createElement('td');
                cell.append(renderCell(column, record));
                tableRow.append(cell);
            });

            elements.tableBody.append(tableRow);
        });
    }

    /* =========================================================
       TAB SWITCHING
    ========================================================= */

    function updateTabButtons() {
        elements.tabs.querySelectorAll('[data-attendance-tab]').forEach(button => {
            const isSelected = button.dataset.attendanceTab === activeAttendanceTab;
            button.classList.toggle('btn-primary', isSelected);
            button.classList.toggle('btn-outline-primary', !isSelected);
            button.classList.toggle('active', isSelected);
            button.setAttribute('aria-selected', String(isSelected));
        });
    }

    function switchAttendanceTab(tabName) {
        if (!attendanceTabConfigs[tabName] || tabName === activeAttendanceTab) return;

        activeAttendanceTab = tabName;
        selectedRecordId = null;

        updateTabButtons();
        updateToolbarActionState();
        updateTimeActionsVisibility();
        renderAttendanceTable();
    }

    elements.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-attendance-tab]');
        if (!button) return;
        switchAttendanceTab(button.dataset.attendanceTab);
    });

    /* =========================================================
       DROPDOWN (shared .registerDropdown component — behavior
       kept local to this page, same as the Registers script)
    ========================================================= */

    function initializeDropdown(dropdown) {
        const toggle = dropdown.querySelector('.registerDropdownToggle');
        const options = dropdown.querySelector('.registerDropdownOptions');
        const selectedLabel = toggle?.querySelector('span');
        const input = dropdown.querySelector('input[type="hidden"]');

        if (!toggle || !options) return;

        toggle.addEventListener('click', event => {
            event.stopPropagation();
            options.hidden ? openDropdown(dropdown) : closeDropdown(dropdown);
        });

        options.addEventListener('click', event => {
            const option = event.target.closest('.registerDropdownOption');
            if (!option) return;

            const value = option.dataset.value;
            if (selectedLabel) selectedLabel.textContent = value;
            if (input) input.value = value;

            options.querySelectorAll('.registerDropdownOption').forEach(item => {
                item.classList.toggle('active', item === option);
            });

            closeDropdown(dropdown);

            if (input && input.id === 'classFilterInput') {
                renderAttendanceTable();
            }
        });

        dropdown.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeDropdown(dropdown);
        });
    }

    function positionDropdownOptions(dropdown) {
        const options = dropdown.querySelector('.registerDropdownOptions');
        const toggle = dropdown.querySelector('.registerDropdownToggle');
        if (!options || !toggle) return;

        const rect = toggle.getBoundingClientRect();
        options.style.top = `${rect.bottom + 8}px`;
        options.style.left = `${rect.left}px`;
        options.style.width = `${rect.width * 1.3}px`;
    }

    function openDropdown(dropdown) {
        closeAllDropdowns(dropdown);

        const options = dropdown.querySelector('.registerDropdownOptions');
        const toggle = dropdown.querySelector('.registerDropdownToggle');

        dropdown.classList.add('isOpen');
        options.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        positionDropdownOptions(dropdown);
    }

    function closeDropdown(dropdown) {
        const options = dropdown.querySelector('.registerDropdownOptions');
        const toggle = dropdown.querySelector('.registerDropdownToggle');

        dropdown.classList.remove('isOpen');
        options.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    function closeAllDropdowns(except = null) {
        document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
            if (dropdown !== except) closeDropdown(dropdown);
        });
    }

    document.querySelectorAll('[data-dropdown]').forEach(initializeDropdown);

    document.addEventListener('click', event => {
        if (!event.target.closest('[data-dropdown]')) closeAllDropdowns();
    });

    window.addEventListener('resize', () => {
        const open = document.querySelector('[data-dropdown].isOpen');
        if (open) positionDropdownOptions(open);
    });

    document.addEventListener('scroll', event => {
        const open = document.querySelector('[data-dropdown].isOpen');
        if (!open) return;
        if (event.target === document || event.target.contains(open)) positionDropdownOptions(open);
    }, true);

    /* =========================================================
       INITIALIZE
    ========================================================= */

    renderCurrentDateLine();
    initAttendanceDatePicker();
    updateTabButtons();
    updateToolbarActionState();
    updateTimeActionsVisibility();
    renderAttendanceTable();

})();
</script>