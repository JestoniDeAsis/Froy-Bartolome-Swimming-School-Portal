@extends('layouts.toast')

<style>
/* =========================================================
   Attendance (student/athlete, view only)
   Same look as the admin / coach pages. Dropdowns use the shared
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

.attendanceHeader { flex: 0 0 auto; margin-bottom: 1rem; }

/* Summary cards */
.attendanceSummary {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: .75rem;
    margin-bottom: 1rem;
}

.attendanceCard {
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    padding: .85rem 1rem;
    min-width: 0;
}

.attendanceCardLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #667085;
    margin-bottom: .25rem;
}

.attendanceCardValue { font-size: 1.05rem; font-weight: 600; color: #101828; overflow-wrap: anywhere; }

.attendanceProgress {
    height: 6px;
    margin-top: .5rem;
    background: #e9ecef;
    border-radius: 99px;
    overflow: hidden;
}
.attendanceProgressFill { height: 100%; width: 0; background: var(--fbss-blue, #0d6efd); border-radius: 99px; transition: width .2s ease; }

/* Toolbar */
.attendanceToolbar {
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

.attendanceFilterGroup { display: flex; flex-direction: column; gap: .35rem; }

.attendanceFilterLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}

/* Sizes come from the shared dropdown variables (our_theme.css). */
.attendanceCourseFilter { --dd-min-width: 300px; }
.attendanceMonthFilter  { --dd-min-width: 180px; }

.attendanceResultCount { margin-left: auto; font-size: .8125rem; color: #667085; white-space: nowrap; }

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

.attendanceRow:hover { background: #f8fafb; }

.attendanceEmptyState { padding: 2rem !important; color: #6c757d; text-align: center; }

.attendanceNameCell { display: inline-flex; align-items: center; gap: .55rem; }

.attendanceStatusDot { flex-shrink: 0; width: 8px; height: 8px; border-radius: 50%; }
.attendanceStatusDot.present { background: #2f9e44; }
.attendanceStatusDot.absent  { background: #e03131; }

.attendanceEmptyValue { color: #adb5bd; }

@media (max-width: 575.98px) {
    .attendanceContainer { padding: 1rem; }
    .attendanceResultCount { margin-left: 0; width: 100%; }
}
</style>


<section class="attendanceContainer" id="attendanceContainer" aria-labelledby="attendanceTitle">

    <header class="attendanceHeader">
        <h2 class="mb-1" id="attendanceTitle">My Attendance</h2>
        <p class="text-muted mb-0" id="attendanceDescription"></p>
        <p class="text-muted small mb-0">View only. Your coach records your Time In and Time Out for each session.</p>
    </header>

    <!-- SUMMARY (Hidden automatically if user is an athlete) -->
    <div class="attendanceSummary" id="attendanceSummary">
        <div class="attendanceCard">
            <div class="attendanceCardLabel">Course</div>
            <div class="attendanceCardValue" id="summaryCourse">--</div>
        </div>
        <div class="attendanceCard">
            <div class="attendanceCardLabel">Schedule</div>
            <div class="attendanceCardValue" id="summarySchedule">--</div>
        </div>
        <div class="attendanceCard">
            <div class="attendanceCardLabel">Sessions Completed</div>
            <div class="attendanceCardValue" id="summarySessions">--</div>
            <div class="attendanceProgress" id="summaryProgress" hidden>
                <div class="attendanceProgressFill" id="summaryProgressFill"></div>
            </div>
        </div>
        <div class="attendanceCard">
            <div class="attendanceCardLabel">Last Attended</div>
            <div class="attendanceCardValue" id="summaryLast">--</div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="attendanceToolbar">

        <!-- Course Filter (Hidden automatically if user is an athlete) -->
        <div class="attendanceFilterGroup" id="courseFilterGroupWrapper">
            <label class="attendanceFilterLabel" id="courseFilterLabelText">Course</label>

            <div class="registerDropdown attendanceCourseFilter" id="courseFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="courseFilterToggle" type="button" aria-expanded="false" aria-controls="courseFilterOptions" aria-label="Filter by course">
                    <span>Loading...</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="courseFilterOptions" hidden></div>

                <input id="courseFilterInput" name="courseFilter" type="hidden" value="">
            </div>
        </div>

        <div class="attendanceFilterGroup">
            <label class="attendanceFilterLabel">Month</label>

            <div class="registerDropdown attendanceMonthFilter" id="monthFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="monthFilterToggle" type="button" aria-expanded="false" aria-controls="monthFilterOptions" aria-label="Filter by month">
                    <span>All Dates</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="monthFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All Dates</button>
                </div>

                <input id="monthFilterInput" name="monthFilter" type="hidden" value="">
            </div>
        </div>

        <span class="attendanceResultCount" id="attendanceResultCount"></span>

    </div>

    <!-- TABLE -->
    <div class="attendanceTableContainer" id="attendanceTableContainer">
        <table class="table attendanceTable" id="attendanceTable">
            <thead id="attendanceTableHead"></thead>
            <tbody id="attendanceTableBody"></tbody>
        </table>
    </div>

</section>


<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    const URL_DATA = '/student/attendance/data';
    const HEADERS = ['Date', 'Course', 'Time In', 'Time Out', 'Status', 'Logged By', 'Last Edited By', 'Last Edited At'];

    const $ = id => document.getElementById(id);

    const el = {
        description: $('attendanceDescription'),
        summaryContainer: $('attendanceSummary'),
        courseFilterGroup: $('courseFilterGroupWrapper'),
        courseFilter: $('courseFilterDropdown'),
        monthFilter: $('monthFilterDropdown'),
        count: $('attendanceResultCount'),
        tableHead: $('attendanceTableHead'),
        tableBody: $('attendanceTableBody'),
        sCourse: $('summaryCourse'),
        sSchedule: $('summarySchedule'),
        sSessions: $('summarySessions'),
        sProgress: $('summaryProgress'),
        sProgressFill: $('summaryProgressFill'),
        sLast: $('summaryLast')
    };

    let enrollments = [];
    let logs = [];
    let loading = true;
    let isAthlete = false;

    /* ---------- helpers ---------- */

    function make(tag, { className, text, attributes } = {}) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        if (attributes) Object.entries(attributes).forEach(([k, v]) => node.setAttribute(k, v));
        return node;
    }

    function parseISODate(iso) {
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d);
    }

    const formatLongDate = d => d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    const formatShortDate = d => d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

    // "2026-10-24 16:30:00" -> "Oct 24, 2026 4:30 PM"
    function formatDateTimeLabel(value) {
        if (!value) return '';

        const d = new Date(String(value).replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return String(value);

        return `${formatShortDate(d)} ${d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}`;
    }

    function formatTimeLabel(value) {
        if (!value) return '';
        const [h, m] = value.split(':').map(Number);
        const ref = new Date();
        ref.setHours(h, m, 0, 0);
        return ref.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    const dropdownValue = root => window.FBSSDropdown.get(root);

    function cell(value, dash = '--') {
        return value
            ? document.createTextNode(value)
            : make('span', { className: 'attendanceEmptyValue', text: dash });
    }

    /* ---------- filters ---------- */

    function logsForCourse() {
        if (isAthlete) return logs; // Athletes don't filter by course dropdown
        const course = dropdownValue(el.courseFilter);
        return course === 'all' || course === ''
            ? logs
            : logs.filter(l => String(l.enrollment_id) === course);
    }

    function visibleLogs() {
        const month = dropdownValue(el.monthFilter);
        const list = logsForCourse();
        return month ? list.filter(l => l.date.startsWith(month)) : list;
    }

    function buildMonthOptions() {
        const months = [...new Set(logsForCourse().map(l => l.date.slice(0, 7)))].sort().reverse();

        const items = [{ value: '', label: 'All Dates' }].concat(months.map(value => {
            const [y, m] = value.split('-').map(Number);
            return { value, label: new Date(y, m - 1, 1).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) };
        }));

        window.FBSSDropdown.setOptions(el.monthFilter, items, dropdownValue(el.monthFilter));
    }

    function buildCourseOptions(defaultId) {
        if (isAthlete) return;
        const items = [{ value: 'all', label: 'All Courses' }]
            .concat(enrollments.map(e => ({ value: String(e.id), label: e.label })));

        window.FBSSDropdown.setOptions(el.courseFilter, items, defaultId ? String(defaultId) : 'all');
    }

    /* ---------- render ---------- */

    function renderSummary() {
        if (isAthlete) return;

        const course = dropdownValue(el.courseFilter);
        const enrollment = enrollments.find(e => String(e.id) === course);

        if (enrollment) {
            el.sCourse.textContent = enrollment.course || 'Course not assigned';
            el.sSchedule.textContent = enrollment.schedule || '--';
            el.sSessions.textContent = enrollment.required > 0
                ? `${enrollment.done} / ${enrollment.required}`
                : String(enrollment.done);
            el.sLast.textContent = enrollment.last_attended
                ? formatShortDate(parseISODate(enrollment.last_attended))
                : '--';

            el.sProgress.hidden = enrollment.required <= 0;
            el.sProgressFill.style.width = enrollment.required > 0
                ? `${Math.min(100, Math.round((enrollment.done / enrollment.required) * 100))}%`
                : '0';
            return;
        }

        const attended = logs.filter(l => l.time_in);
        const last = attended.map(l => l.date).sort().pop();

        el.sCourse.textContent = enrollments.length ? 'All courses' : '--';
        el.sSchedule.textContent = '--';
        el.sSessions.textContent = String(attended.length);
        el.sLast.textContent = last ? formatShortDate(parseISODate(last)) : '--';
        el.sProgress.hidden = true;
    }

    function renderTable() {
        const list = visibleLogs();

        el.tableHead.replaceChildren();
        el.tableBody.replaceChildren();

        const headerRow = make('tr');
        headerRow.append(make('th', { text: 'No.', attributes: { scope: 'col' } }));
        HEADERS.forEach(h => headerRow.append(make('th', { text: h, attributes: { scope: 'col' } })));
        el.tableHead.append(headerRow);

        if (loading || !list.length) {
            let emptyMessage = 'You are not enrolled in a course yet.';
            if (loading) {
                emptyMessage = 'Loading...';
            } else if (!isAthlete && (enrollments.length || logs.length)) {
                emptyMessage = 'No attendance has been logged for this selection.';
            } else if (isAthlete && logs.length) {
                emptyMessage = 'No attendance has been logged for this selection.';
            } else if (isAthlete) {
                emptyMessage = 'No logged attendance yet.';
            }

            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'attendanceEmptyState',
                text: emptyMessage,
                attributes: { colSpan: HEADERS.length + 1 }
            }));
            el.tableBody.append(emptyRow);
            el.count.textContent = '';
            return;
        }

        list.forEach((log, index) => {
            const row = make('tr', { className: 'attendanceRow' });
            row.append(make('td', { text: String(index + 1) }));

            const dateCell = make('td');
            const wrapper = make('span', { className: 'attendanceNameCell' });
            wrapper.append(
                make('span', { className: `attendanceStatusDot ${log.time_in ? 'present' : 'absent'}` }),
                document.createTextNode(formatShortDate(parseISODate(log.date)))
            );
            dateCell.append(wrapper);
            row.append(dateCell);

            [
                cell(log.course),
                cell(formatTimeLabel(log.time_in), '-'),
                cell(formatTimeLabel(log.time_out), '-'),
                cell(log.state),
                cell(log.logged_by),
                cell(log.last_edited_by),
                cell(formatDateTimeLabel(log.last_edited_at))
            ].forEach(content => {
                const td = make('td');
                td.append(content);
                row.append(td);
            });

            el.tableBody.append(row);
        });

        const attended = list.filter(l => l.time_in).length;
        el.count.textContent = `${list.length} log${list.length === 1 ? '' : 's'} · ${attended} attended`;
    }

    function render() {
        renderSummary();
        renderTable();
    }

    /* ---------- load ---------- */

    async function load() {
        renderTable();

        try {
            const response = await fetch(URL_DATA, { headers: { 'Accept': 'application/json' } });
            let data = {};
            try { data = await response.json(); } catch (e) {}

            loading = false;

            if (!response.ok) {
                showToast(data.message || 'Unable to load your attendance.', 'danger');
                render();
                return;
            }

            isAthlete = !!data.is_athlete;

            // Remove summary cards & course filter wrapper entirely for athletes
            if (isAthlete) {
                if (el.summaryContainer) el.summaryContainer.remove();
                if (el.courseFilterGroup) el.courseFilterGroup.remove();
            }

            enrollments = data.enrollments || [];
            logs = data.logs || [];

            if (!isAthlete) {
                buildCourseOptions(data.default_enrollment_id);
            }
            buildMonthOptions();
        } catch (error) {
            console.error(error);
            loading = false;
            showToast('Something went wrong while loading your attendance.', 'danger');
        }

        render();
    }

    if (el.courseFilterGroup && el.courseFilter) {
        el.courseFilter.addEventListener('dropdown:change', () => { buildMonthOptions(); render(); });
    }
    el.monthFilter.addEventListener('dropdown:change', render);

    el.description.textContent = `Current date: ${formatLongDate(new Date())}`;
    load();

})();
</script>