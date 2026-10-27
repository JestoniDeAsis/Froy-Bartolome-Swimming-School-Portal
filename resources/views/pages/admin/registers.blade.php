@extends('layouts.toast')

<style>
/* =========================================================
   Registers
========================================================= */

.registersContainer {
    width: 100%;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    /* scrolls when tabs + toolbar + hint + table are taller than the screen (phones) */
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    display: flex;
    flex-direction: column;
}

.registersHeader {
    flex: 0 0 auto;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}


#registersTabs {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1rem;
}

.registersTab {
    min-width: 140px;
    flex-shrink: 0;
    transition: background-color .15s ease, color .15s ease, border-color .15s ease;
}

/* =========================================================
   Toolbar
========================================================= */

.registersToolbar {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .25rem;
    margin-bottom: 1rem;
}

.registersSearchContainer {
    position: relative;
    width: min(300px, 100%);
    flex-shrink: 0;
}

.registersSearchIcon {
    position: absolute;
    top: 50%;
    left: .75rem;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.registersSearchInput {
    height: 40px;
    box-sizing: border-box;
    border-radius: 20px;
    padding-left: 2.25rem;
}

.registersToolbarActions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-left: auto;
}

/* =========================================================
   Scrollable Table Container
========================================================= */

.registersTableContainer {
    --register-row-height: 49px;
    --register-header-height: 49px;
    --register-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;

    max-height: calc(
        var(--register-header-height) +
        (var(--register-row-height) * var(--register-visible-rows))
    );

    width: 100%;
    overflow-y: auto;
    overflow-x: auto;
    opacity: 1;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    box-sizing: border-box;
    transition: flex-basis .18s ease, opacity .18s ease, max-height .18s ease;
}

.registersTableContainer.isCollapsing {
    flex: 0 0 0;
    min-height: 0;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
}

.registersTableContainer::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.registersTableContainer::-webkit-scrollbar-track {
    background: #f1f3f5;
    border-radius: 10px;
}

.registersTableContainer::-webkit-scrollbar-thumb {
    background: #c9ced3;
    border-radius: 10px;
}

.registersTableContainer::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* =========================================================
   Table
========================================================= */

.registersTable {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.registersTable thead {
    background: #f8fafb;
}

.registersTable thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafb;
    color: #667085;
    font-weight: 600;
    border-bottom: 1px solid #e7ebef;
    box-shadow: 0 1px 0 #e7ebef;
}

.registersTable th {
    color: #667085;
    font-weight: 600;
}

.registersTable th,
.registersTable td {
    padding: .75rem 1rem;
    vertical-align: middle;
}

.registersTable th:last-child,
.registersTable td:last-child {
    text-align: center;
}

/* Students tab rows are clickable — selecting one enables the
   "Course update" toolbar button above. */
.studentRow {
    cursor: pointer;
    transition: background-color .1s ease;
}

.studentRow:hover {
    background: #f8fafb;
}

.studentRow.selectedRow,
.studentRow.selectedRow:hover,
.studentRow.selectedRow > td,
.studentRow.selectedRow:hover > td {
    background: #e7f5ff;
}

/* =========================================================
   Action hint (explains why a toolbar button is disabled)
========================================================= */

.registersActionHint {
    flex: 0 0 auto;
    margin: -.25rem 0 1rem;
    font-size: .875rem;
    color: #6c757d;
}

.registersActionHint.isWarning {
    color: #b26a00;
}

/* =========================================================
   Empty State
========================================================= */

.registersEmptyState {
    border-bottom: 0;
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}

/* =========================================================
   Payment Badges
========================================================= */

.paymentBadge {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 12px;
    font-size: .75rem;
    font-weight: 600;
}

.paymentBadge.paid {
    background: #e6f7ec;
    color: #1a7f3c;
}

.paymentBadge.unpaid {
    background: #fdebec;
    color: #c22b3a;
}

.paymentBadge.partial {
    background: #fff4e0;
    color: #b26a00;
}

/* =========================================================
   Notes
========================================================= */

.notesEmpty {
    color: #adb5bd;
}

/* =========================================================
   Shared Modal
========================================================= */

.modalContentBox {
    width: min(500px, 95%);
    padding: 1.5rem 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
}

.modalContentBox h3 {
    margin-bottom: .25rem;
    font-size: 1.25rem;
    font-weight: 700;
}

.modalStudentName {
    margin-bottom: 1.5rem;
    font-size: .875rem;
}

.modalStudentName span {
    color: #495057;
    font-weight: 600;
}

.requiredMark {
    color: var(--fbss-danger, #DC3545);
    font-weight: 700;
}

.formGroup {
    margin-bottom: 1.25rem;
}

.formGroup > label {
    display: block;
    margin-bottom: .5rem;
    font-size: .875rem;
    font-weight: 600;
}

.modalActions {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    margin-top: 1.5rem;
}

.modalPaymentInfo {
    font-size: .875rem;
    color: #495057;
}

.modalPaymentInfo strong {
    color: #212529;
}

/* =========================================================
   Notes Modal
========================================================= */

.notesModalContent {
    max-width: 420px;
}

.notesFullText {
    margin-top: .5rem;
    color: #495057;
    font-size: .9rem;
    line-height: 1.5;
    white-space: pre-wrap;
}

/* =========================================================
   Confirm / Info Modal
========================================================= */

.confirmModalContent {
    max-width: 420px;
}

.confirmModalMessage {
    margin: 0;
    font-size: .9rem;
    line-height: 1.5;
}

/* Red warning box + type-to-confirm field, used for permanent deletes */
.confirmModalWarning {
    margin-top: 1rem;
    padding: .75rem 1rem;
    border: 1px solid #f1aeb5;
    border-left: 4px solid #dc3545;
    border-radius: 8px;
    background: #fdebec;
    color: #842029;
    font-size: .875rem;
    line-height: 1.5;
    white-space: pre-line;
}

.confirmModalTypeGroup,
.confirmModalReasonGroup {
    margin-top: 1rem;
}

.confirmModalTypeGroup label,
.confirmModalReasonGroup label {
    display: block;
    margin-bottom: .35rem;
    font-size: .85rem;
}

/* =========================================================
   Reusable Dropdown Component — lives in our_theme.css
   (.registerDropdown / .registerDropdownToggle /
   .registerDropdownOptions / .registerDropdownOption).
   Kept out of this page's own <style> block on purpose, so
   there's exactly one copy to edit — don't paste it back in
   here even if a dropdown looks unstyled; that means
   our_theme.css didn't actually deploy/refresh, not that this
   page needs its own copy. Check DevTools → Sources for
   .registerDropdownOptions to confirm what's really loaded.

   Behaviour (open/close, selection, positioning) comes from the
   shared assets/js/fbss-dropdown.js: give the root
   `data-fbss-dropdown` and listen for its `dropdown:change` event.
   The panel is `position: fixed`, which is what keeps the Course
   Filter from being clipped by .registersToolbar's overflow-x: auto.
========================================================= */

/* Invalid state for a shared dropdown (set with .isInvalid on the .registerDropdown root). */
.registerDropdown.isInvalid .registerDropdownToggle { border-color: #dc3545; }
.registerDropdown.isInvalid .registerDropdownToggle:focus { box-shadow: 0 0 0 .2rem rgba(220, 53, 69, .15); }

/* =========================================================
   Toolbar Course Filter
========================================================= */

.registerCourseFilter {
    width: fit-content;
    min-width: 0;
    flex-shrink: 0;
}

.registerCourseFilter .registerDropdownToggle {
    max-width: 180px;
}

/* =========================================================
   Responsive
========================================================= */

/* On phones / short screens the toolbar can eat the whole height, so keep the
   table from being squeezed to nothing (the page scrolls instead). */
@media (max-width: 767.98px), (max-height: 700px) {
    .registersTableContainer:not(.isCollapsing) {
        min-height: 260px;
    }
}

@media (max-width: 575.98px) {
    .registersContainer {
        height: 100vh;
        padding: 1rem;
    }

    .registersTab {
        min-width: 125px;
        flex: 1 1 calc(50% - .25rem);
    }

    .registersSearchContainer {
        width: 100%;
        min-width: 0;
    }

    .registersSearchInput {
        width: 100%;
    }

    .registersToolbar {
        gap: .5rem;
    }

    /* Filters: 2 per row, matching the 2-column alignment of the tab buttons */
    .registerCourseFilter {
        flex: 1 1 calc(50% - .25rem);
        max-width: calc(50% - .25rem);
        min-width: 0;
        --dd-width: 100%;
        --dd-min-width: 0;
    }

    .registerCourseFilter .registerDropdownToggle {
        width: 100%;
        min-width: 0;
        max-width: 100%;
    }

    .registersToolbarActions {
        margin-left: 0;
        width: 100%;
    }

    .registersToolbarActions button {
        flex: 1 1 auto;
    }

    .modalContentBox {
        padding: 1.25rem 1.25rem;
    }
}
</style>


<!-- =========================================================
     REGISTERS SECTION
========================================================= -->

<section class="registersContainer" id="registersContainer" aria-labelledby="registersTitle">

    <header class="registersHeader">
        <div id="registersHeaderText">
            <h2 class="mb-1" id="registersTitle">Student Enrollments</h2>
            <p class="text-muted mb-0" id="registersDescription">
                Manage new registrations, students, and graduates.
            </p>
        </div>

    </header>


    <!-- =====================================================
         TABS
    ====================================================== -->

    <nav class="btn-tab-group" id="registersTabs" aria-label="Register sections">
        <button class="btn btn-outline-primary btn-tab registersTab active" type="button" data-register-tab="registrations" aria-selected="true">
            Applicants
        </button>
        <button class="btn btn-outline-primary btn-tab registersTab" type="button" data-register-tab="students" aria-selected="false">
            Students
        </button>
        <button class="btn btn-outline-primary btn-tab registersTab" type="button" data-register-tab="graduates" aria-selected="false">
            Graduates
        </button>
        <button class="btn btn-outline-primary btn-tab registersTab" type="button" data-register-tab="dropped" aria-selected="false">
            Dropped
        </button>
    </nav>


    <!-- =====================================================
         TOOLBAR
    ====================================================== -->

    <div class="registersToolbar">

        <!-- Search -->
        <div class="registersSearchContainer">
            <i class="bi bi-search registersSearchIcon" aria-hidden="true"></i>
            <input class="form-control registersSearchInput" id="registersSearch" type="search" placeholder="Search" autocomplete="off" aria-label="Search registers">
        </div>

        <!-- Course Filter (Students and Graduates) -->
        <div class="registerDropdown registerCourseFilter" id="courseFilterDropdown" data-fbss-dropdown hidden>
            <button class="registerDropdownToggle" id="courseFilterToggle" type="button" aria-expanded="false" aria-controls="courseFilterOptions">
                <span id="courseFilterSelected">Filter by Course</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>

            <div class="registerDropdownOptions" id="courseFilterOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="">All Courses</button>
            </div>

            <input id="courseFilterInput" name="courseFilter" type="hidden" value="">
        </div>

        <!-- Payment Filter (Students) -->
        <div class="registerDropdown registerCourseFilter" id="paymentFilterDropdown" data-fbss-dropdown hidden>
            <button class="registerDropdownToggle" id="paymentFilterToggle" type="button" aria-expanded="false" aria-controls="paymentFilterOptions">
                <span id="paymentFilterSelected">All Payments</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>

            <div class="registerDropdownOptions" id="paymentFilterOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="">All Payments</button>
                <button class="registerDropdownOption" type="button" data-value="paid">Paid</button>
                <button class="registerDropdownOption" type="button" data-value="partial">Partial</button>
                <button class="registerDropdownOption" type="button" data-value="unpaid">Unpaid</button>
            </div>

            <input id="paymentFilterInput" name="paymentFilter" type="hidden" value="">
        </div>

        <!-- Added (created_at) Filter -->
        <div class="registerDropdown registerCourseFilter" id="addedFilterDropdown" data-fbss-dropdown>
            <button class="registerDropdownToggle" id="addedFilterToggle" type="button" aria-expanded="false" aria-controls="addedFilterOptions">
                <span id="addedFilterSelected">Any Time</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>

            <div class="registerDropdownOptions" id="addedFilterOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="">Any Time</button>
                <button class="registerDropdownOption" type="button" data-value="today">Today</button>
                <button class="registerDropdownOption" type="button" data-value="week">Last 7 Days</button>
                <button class="registerDropdownOption" type="button" data-value="month">This Month</button>
            </div>

            <input id="addedFilterInput" name="addedFilter" type="hidden" value="">
        </div>

        <!-- Sort (created_at) -->
        <div class="registerDropdown registerCourseFilter" id="sortFilterDropdown" data-fbss-dropdown>
            <button class="registerDropdownToggle" id="sortFilterToggle" type="button" aria-expanded="false" aria-controls="sortFilterOptions">
                <span id="sortFilterSelected">Newest First</span>
                <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
            </button>

            <div class="registerDropdownOptions" id="sortFilterOptions" hidden>
                <button class="registerDropdownOption active" type="button" data-value="desc">Newest First</button>
                <button class="registerDropdownOption" type="button" data-value="asc">Oldest First</button>
            </div>

            <input id="sortFilterInput" name="sortFilter" type="hidden" value="desc">
        </div>

        <!-- Actions (enabled once a row is selected) -->
        <div class="registersToolbarActions" id="registersToolbarActions">
            <button class="btn btn-primary" id="enrollButton" type="button" disabled>Enroll</button>
            <button class="btn btn-outline-danger" id="deleteApplicantButton" type="button" disabled>Delete</button>
            <button class="btn btn-primary" id="courseUpdateButton" type="button" disabled>Course update</button>
            <button class="btn btn-outline-danger" id="cancelEnrollmentButton" type="button" disabled>Cancel Enrollment</button>
            <button class="btn btn-outline-danger" id="dropStudentButton" type="button" disabled>Drop Student</button>
            <button class="btn btn-primary" id="reinstateButton" type="button" disabled>Reinstate</button>
        </div>

    </div>

    <!-- Says why an action is off (nothing selected / cannot cancel) -->
    <p class="registersActionHint" id="registersActionHint" role="status" aria-live="polite" hidden></p>


    <!-- =====================================================
         TABLE
    ====================================================== -->

    <div class="registersTableContainer" id="registersTableContainer">
        <table class="table registersTable" id="registersTable">
            <thead id="registersTableHead"></thead>
            <tbody id="registersTableBody"></tbody>
        </table>
    </div>

</section>


<!-- =========================================================
     SHARED STUDENT MODAL
========================================================= -->

<div class="modalOverlay" id="studentModal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="studentModalTitle">

        <h3 id="studentModalTitle">Enrolling to course</h3>

        <p class="text-muted modalStudentName">
            Student name: <span id="modalStudentName"></span>
        </p>

        <!-- Course -->
        <div class="formGroup" id="modalCourseGroup">
            <label for="courseToggle">Course <span class="requiredMark">*</span></label>
            <div class="registerDropdown" id="courseDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="courseToggle" type="button" aria-expanded="false" aria-controls="courseOptions">
                    <span id="courseSelected">{{ $courses[0]['name'] ?? 'Select a course' }}</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="courseOptions" hidden>
                    @foreach ($courses as $index => $course)
                        <button class="registerDropdownOption @if ($index === 0) active @endif" type="button" data-value="{{ $course['name'] }}">{{ $course['name'] }}</button>
                    @endforeach
                </div>

                <input id="courseInput" name="course" type="hidden" value="{{ $courses[0]['name'] ?? '' }}">
            </div>
        </div>

        <!-- Course price change (course update only) -->
        <div class="formGroup" id="modalPriceChangeGroup" hidden>
            <div class="modalPaymentInfo">
                <strong>Course price:</strong>
                <span id="modalPriceChange"></span>
            </div>
        </div>

        <!-- Schedule -->
        <div class="formGroup" id="modalScheduleGroup">
            <label for="scheduleToggle">Schedule Time <span class="requiredMark">*</span></label>
            <div class="registerDropdown" id="scheduleDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="scheduleToggle" type="button" aria-expanded="false" aria-controls="scheduleOptions">
                    <span id="scheduleSelected">Morning</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="scheduleOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="Morning">Morning</button>
                    <button class="registerDropdownOption" type="button" data-value="Afternoon">Afternoon</button>
                </div>

                <input id="scheduleInput" name="scheduleTime" type="hidden" value="Morning">
            </div>
        </div>

        <!-- Assigned Days -->
        <div class="formGroup" id="modalDaysGroup">
            <label>Assigned day/s <span class="requiredMark">*</span></label>
            <div class="daysToggle" id="daysToggle">
                <button type="button" class="dayButton">MON</button>
                <button type="button" class="dayButton">TUE</button>
                <button type="button" class="dayButton">WED</button>
                <button type="button" class="dayButton">THU</button>
                <button type="button" class="dayButton">FRI</button>
                <button type="button" class="dayButton">SAT</button>
                <button type="button" class="dayButton">SUN</button>
            </div>
        </div>

        <!-- Notes -->
        <div class="formGroup" id="modalNotesGroup">
            <label for="studentNotes">Notes</label>
            <textarea class="form-control" id="studentNotes" name="notes" rows="3" placeholder="Add any additional details, e.g. schedule preferences or special requests"></textarea>
        </div>

        <!-- Actions -->
        <div class="modalActions">
            <button class="btn btn-outline-danger" id="modalCancelButton" type="button">Cancel</button>
            <button class="btn btn-success" id="modalSubmitButton" type="button">Enroll</button>
        </div>

    </div>
</div>


<!-- =========================================================
     NOTES MODAL
========================================================= -->

<div class="modalOverlay" id="notesModal" hidden>
    <div class="modalContentBox notesModalContent" role="dialog" aria-modal="true" aria-labelledby="notesModalTitle">

        <h3 id="notesModalTitle">Notes</h3>

        <p class="text-muted modalStudentName">
            Student name: <span id="notesModalStudentName"></span>
        </p>

        <p class="notesFullText" id="notesModalText"></p>

        <div class="modalActions">
            <button class="btn btn-primary" id="notesModalCloseButton" type="button">Close</button>
        </div>

    </div>
</div>


<!-- =========================================================
     CONFIRM / INFO MODAL

     Reused for both yes/no confirmations (Delete, Revoke) and
     one-button success messages (replaces window.confirm/alert).
========================================================= -->

<div class="modalOverlay" id="confirmModal" hidden>
    <div class="modalContentBox confirmModalContent" role="alertdialog" aria-modal="true" aria-labelledby="confirmModalTitle">

        <h3 id="confirmModalTitle">Are you sure?</h3>

        <p class="text-muted confirmModalMessage" id="confirmModalMessage"></p>

        <div class="confirmModalWarning" id="confirmModalWarning" role="alert" hidden></div>

        <div class="confirmModalTypeGroup" id="confirmModalTypeGroup" hidden>
            <label for="confirmModalTypeInput" id="confirmModalTypeLabel"></label>
            <input class="form-control" id="confirmModalTypeInput" type="text" autocomplete="off" spellcheck="false">
        </div>

        <div class="confirmModalReasonGroup" id="confirmModalReasonGroup" hidden>
            <label for="confirmModalReasonInput">Reason (optional)</label>
            <input class="form-control" id="confirmModalReasonInput" type="text" maxlength="255" autocomplete="off" placeholder="e.g. moved away, non-payment, student request">
        </div>

        <div class="modalActions">
            <button class="btn btn-outline-danger" id="confirmModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="confirmModalConfirmButton" type="button">Confirm</button>
        </div>

    </div>
</div>


<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    /* =========================================================
       DATA — every list comes from RegistersController::data():
       applicants, current students, and graduates who are
       not enrolled right now. People move between lists as the admin
       acts, and the server is always asked first.
    ========================================================= */

    let registrations = @json($registrations);

    // Real courses from tbl_courses. Dropdowns use the course NAME as their
    // value; this map converts name -> id when a request is sent.
    const COURSES = @json($courses);
    const COURSE_NAME_TO_ID = Object.fromEntries(COURSES.map(c => [c.name, c.id]));

    let students = @json($students);
    let graduates = @json($graduates);
    let dropped = @json($dropped);


    /* =========================================================
       TAB CONFIG — maps each tab to its live data, its columns
       (label shown + key read off each person object), and its
       row-level actions.
    ========================================================= */

    const tabConfigs = {
        registrations: {
            getRows: () => registrations,
            dateKey: 'createdAt',            // used by the date filter and the sort
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Email Address', key: 'email' },
                { label: 'Birthdate', key: 'birthdate' },
                { label: 'Age', key: 'age' },
                { label: 'Date Added', key: 'createdAt' }
            ]
        },
        students: {
            getRows: () => students,
            dateKey: 'enrolledAt',
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Current Course', key: 'course' },
                { label: 'Schedule', key: 'schedule' },
                { label: 'Payment Status', key: 'paymentStatus' },
                { label: 'Balance', key: 'balance' },
                { label: 'Sessions', key: 'sessions' },
                { label: 'Graduation', key: 'graduation' },
                { label: 'Notes', key: 'notes' },
                { label: 'Enrolled On', key: 'enrolledAt' }
            ]
        },
        dropped: {
            getRows: () => dropped,
            dateKey: 'droppedAt',
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Last Course', key: 'course' },
                { label: 'Payment Status', key: 'paymentStatus' },
                { label: 'Balance', key: 'balance' },
                { label: 'Sessions', key: 'sessions' },
                { label: 'Reason', key: 'dropReason' },
                { label: 'Enrolled On', key: 'enrolledAt' },
                { label: 'Dropped On', key: 'droppedAt' }
            ]
        },
        graduates: {
            getRows: () => graduates,
            dateKey: 'graduatedAt',
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Email Address', key: 'email' },
                { label: 'Last Course', key: 'lastCourse' },
                { label: 'Courses Finished', key: 'coursesFinished' },
                { label: 'Enrolled On', key: 'enrolledAt' },
                { label: 'Graduated On', key: 'graduatedAt' },
                { label: 'Took', key: 'duration' },
                { label: 'Birthdate', key: 'birthdate' },
                { label: 'Age', key: 'age' }
            ]
        }
    };

    /* =========================================================
       ELEMENTS
    ========================================================= */

    const elements = {
        tabs: document.getElementById('registersTabs'),
        search: document.getElementById('registersSearch'),
        tableContainer: document.getElementById('registersTableContainer'),
        tableHead: document.getElementById('registersTableHead'),
        tableBody: document.getElementById('registersTableBody'),
        courseFilterDropdown: document.getElementById('courseFilterDropdown'),
        courseFilterInput: document.getElementById('courseFilterInput'),
        paymentFilterDropdown: document.getElementById('paymentFilterDropdown'),
        paymentFilterInput: document.getElementById('paymentFilterInput'),
        addedFilterDropdown: document.getElementById('addedFilterDropdown'),
        addedFilterInput: document.getElementById('addedFilterInput'),
        sortFilterDropdown: document.getElementById('sortFilterDropdown'),
        sortFilterInput: document.getElementById('sortFilterInput'),

        studentModal: document.getElementById('studentModal'),
        modalTitle: document.getElementById('studentModalTitle'),
        modalStudentName: document.getElementById('modalStudentName'),
        modalCancelButton: document.getElementById('modalCancelButton'),
        modalSubmitButton: document.getElementById('modalSubmitButton'),
        modalCourseGroup: document.getElementById('modalCourseGroup'),
        modalScheduleGroup: document.getElementById('modalScheduleGroup'),
        modalDaysGroup: document.getElementById('modalDaysGroup'),
        modalNotesGroup: document.getElementById('modalNotesGroup'),
        modalPriceChangeGroup: document.getElementById('modalPriceChangeGroup'),
        modalPriceChange: document.getElementById('modalPriceChange'),
        studentNotes: document.getElementById('studentNotes'),
        courseInput: document.getElementById('courseInput'),
        scheduleInput: document.getElementById('scheduleInput'),

        enrollButton: document.getElementById('enrollButton'),
        deleteApplicantButton: document.getElementById('deleteApplicantButton'),
        courseUpdateButton: document.getElementById('courseUpdateButton'),
        cancelEnrollmentButton: document.getElementById('cancelEnrollmentButton'),
        dropStudentButton: document.getElementById('dropStudentButton'),
        reinstateButton: document.getElementById('reinstateButton'),
        actionHint: document.getElementById('registersActionHint'),

        notesModal: document.getElementById('notesModal'),
        notesModalStudentName: document.getElementById('notesModalStudentName'),
        notesModalText: document.getElementById('notesModalText'),
        notesModalCloseButton: document.getElementById('notesModalCloseButton'),

        confirmModal: document.getElementById('confirmModal'),
        confirmModalTitle: document.getElementById('confirmModalTitle'),
        confirmModalMessage: document.getElementById('confirmModalMessage'),
        confirmModalCancelButton: document.getElementById('confirmModalCancelButton'),
        confirmModalConfirmButton: document.getElementById('confirmModalConfirmButton'),
        confirmModalWarning: document.getElementById('confirmModalWarning'),
        confirmModalTypeGroup: document.getElementById('confirmModalTypeGroup'),
        confirmModalTypeLabel: document.getElementById('confirmModalTypeLabel'),
        confirmModalTypeInput: document.getElementById('confirmModalTypeInput'),
        confirmModalReasonGroup: document.getElementById('confirmModalReasonGroup'),
        confirmModalReasonInput: document.getElementById('confirmModalReasonInput'),

    };

    /* =========================================================
       STATE
    ========================================================= */

    let activeTab = 'registrations';
    let selectedId = null;          // currently selected row on the active tab
    let modalContext = null;        // { mode: 'enroll' | 'course-update', ... }
    let pendingConfirmAction = null; // callback fired by the confirm modal's Confirm/OK button
    let confirmRequiredText = '';    // word that must be typed before Confirm turns on (permanent deletes)

    /* =========================================================
       SMALL HELPERS
    ========================================================= */

    function createElement(tag, options = {}) {
        const element = document.createElement(tag);

        if (options.className) element.className = options.className;
        if (options.id) element.id = options.id;
        if (options.text !== undefined) element.textContent = options.text;

        if (options.attributes) {
            Object.entries(options.attributes).forEach(([name, value]) => {
                element.setAttribute(name, value);
            });
        }

        return element;
    }

    function capitalize(value) {
        if (!value) return '';
        return value.charAt(0).toUpperCase() + value.slice(1);
    }

    function getPersonName(person) {
        const given = [person.firstName, person.middleName && person.middleName !== '--' ? person.middleName : '']
            .filter(Boolean)
            .join(' ');

        return `${person.lastName}, ${given}`;
    }

    function stripYear(value) {
        return String(value || '').replace(/\s*\d{4}$/, '').trim();
    }

    function formatCurrency(amount) {
        return `₱${Number(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    }

    function parseSchedule(schedule) {
        if (!schedule) return { scheduleTime: 'Morning', days: [] };

        const [prefix, dayPart] = schedule.split(' - ');
        const scheduleTime = prefix === 'PM' ? 'Afternoon' : 'Morning';
        const days = dayPart ? dayPart.split('/') : [];

        return { scheduleTime, days };
    }

    function buildScheduleString(scheduleTime, days) {
        const prefix = scheduleTime === 'Afternoon' ? 'PM' : 'AM';
        return days.length ? `${prefix} - ${days.join('/')}` : prefix;
    }

    // Select an option on one of the modal's shared dropdowns (silent: no change event).
    function setDropdownValue(prefix, value) {
        const root = document.getElementById(`${prefix}Toggle`)?.closest('[data-fbss-dropdown]');
        if (root) window.FBSSDropdown.set(root, value);
    }

    function setActiveDays(days) {
        document.querySelectorAll('#daysToggle .dayButton').forEach(button => {
            button.classList.toggle('active', days.includes(button.textContent.trim()));
        });
    }

    function getActiveDays() {
        return Array.from(document.querySelectorAll('#daysToggle .dayButton.active'))
            .map(button => button.textContent.trim());
    }

    /* =========================================================
       MODAL
    ========================================================= */

    function openModal(modal) {
        modal.hidden = false;
    }

    function closeModal(modal) {
        modal.hidden = true;
    }

    /* =========================================================
       PAYMENT STATUS BADGE
    ========================================================= */

    function renderPaymentStatus(value) {
        const status = String(value).toLowerCase();

        return createElement('span', {
            className: `paymentBadge ${status}`,
            text: value
        });
    }

    /* =========================================================
       NOTES CELL / MODAL
    ========================================================= */

    function renderNotesCell(person, value) {
        if (!value) {
            return createElement('span', { className: 'notesEmpty', text: '—' });
        }

        const button = createElement('button', {
            id: `notesButtonStudents${person.id}`,
            className: 'notesLink btn btn-outline-primary btn-icon btn-icon-square btn-icon-sm',
            attributes: {
                type: 'button',
                'aria-label': `View notes for ${getPersonName(person)}`,
                title: 'View notes'
            }
        });

        button.innerHTML = '<i class="bi bi-file-earmark-text" aria-hidden="true"></i>';

        button.addEventListener('click', event => {
            event.stopPropagation();
            openNotesModal(getPersonName(person), value);
        });

        return button;
    }

    function openNotesModal(studentName, note) {
        elements.notesModalStudentName.textContent = studentName;
        elements.notesModalText.textContent = note;
        openModal(elements.notesModal);
    }

    elements.notesModalCloseButton.addEventListener('click', () => {
        closeModal(elements.notesModal);
    });

    /* =========================================================
       CONFIRM / INFO MODAL

       openConfirmModal(...) drives a yes/no confirmation — Cancel
       dismisses, Confirm runs the given callback then dismisses.
       openInfoModal(...) is the one-button variant used for the
       "Successfully ___" messages that used to be window.alert().
    ========================================================= */

    function openConfirmModal({
        title, message, confirmLabel = 'Confirm', cancelLabel = 'Cancel', onConfirm,
        danger = false,      // red Confirm button, neutral Cancel
        warning = '',        // red warning box under the message
        requireText = '',    // Confirm stays off until this exact text is typed
        askReason = false    // shows an optional reason box; its text is passed to onConfirm
    }) {
        elements.confirmModalTitle.textContent = title;
        elements.confirmModalMessage.textContent = message;
        elements.confirmModalConfirmButton.textContent = confirmLabel;
        elements.confirmModalCancelButton.textContent = cancelLabel;
        elements.confirmModalCancelButton.hidden = false;
        elements.confirmModalConfirmButton.className = danger ? 'btn btn-danger' : 'btn btn-primary';
        elements.confirmModalCancelButton.className = danger ? 'btn btn-outline-secondary' : 'btn btn-outline-danger';

        elements.confirmModalWarning.textContent = warning;
        elements.confirmModalWarning.hidden = !warning;

        confirmRequiredText = requireText;
        elements.confirmModalTypeInput.value = '';
        elements.confirmModalTypeGroup.hidden = !requireText;
        elements.confirmModalTypeLabel.textContent = requireText ? `Type ${requireText} to confirm` : '';
        elements.confirmModalConfirmButton.disabled = !!requireText;

        elements.confirmModalReasonInput.value = '';
        elements.confirmModalReasonGroup.hidden = !askReason;

        pendingConfirmAction = typeof onConfirm === 'function' ? onConfirm : null;

        openModal(elements.confirmModal);

        if (requireText) elements.confirmModalTypeInput.focus();
    }

    function openInfoModal({ title, message, okLabel = 'OK' }) {
        elements.confirmModalTitle.textContent = title;
        elements.confirmModalMessage.textContent = message;
        elements.confirmModalConfirmButton.textContent = okLabel;
        elements.confirmModalConfirmButton.className = 'btn btn-primary';
        elements.confirmModalConfirmButton.disabled = false;
        elements.confirmModalCancelButton.hidden = true;
        elements.confirmModalWarning.hidden = true;
        elements.confirmModalTypeGroup.hidden = true;
        elements.confirmModalReasonGroup.hidden = true;
        confirmRequiredText = '';

        pendingConfirmAction = null;

        openModal(elements.confirmModal);
    }

    // Permanent deletes: Confirm only turns on when the exact word is typed.
    elements.confirmModalTypeInput.addEventListener('input', () => {
        elements.confirmModalConfirmButton.disabled =
            elements.confirmModalTypeInput.value.trim() !== confirmRequiredText;
    });

    function closeConfirmModal() {
        pendingConfirmAction = null;
        closeModal(elements.confirmModal);
    }

    elements.confirmModalCancelButton.addEventListener('click', () => {
        closeConfirmModal();
    });

    elements.confirmModalConfirmButton.addEventListener('click', () => {
        const action = pendingConfirmAction;
        const reason = elements.confirmModalReasonInput.value.trim();
        pendingConfirmAction = null;
        closeModal(elements.confirmModal);
        if (action) action(reason);
    });

    /* =========================================================
       RENDER CELL
    ========================================================= */

    // Keys that hold a date. Date-and-time for enrollments, date only for the rest.
    const DATE_TIME_KEYS = ['enrolledAt'];
    const DATE_KEYS = ['createdAt', 'graduatedAt', 'droppedAt'];

    // What a cell shows as plain text (also what the search box matches against).
    function getCellText(key, person) {
        const value = person[key] ?? '';

        if (DATE_TIME_KEYS.includes(key)) return formatDateTime(value);
        if (DATE_KEYS.includes(key)) return formatAddedDate(value);
        if (key === 'balance') return value === '' ? '' : formatCurrency(value);
        if (key === 'sessions') return person.sessionTotal ? `${person.attendanceCount} / ${person.sessionTotal}` : String(person.attendanceCount ?? 0);
        if (key === 'graduation') return person.clearedAt ? 'Cleared' : '';

        return String(value);
    }

    function renderCell(key, person) {
        const value = person[key] ?? '';

        if (key === 'paymentStatus') return renderPaymentStatus(value);
        if (key === 'notes') return renderNotesCell(person, value);

        if (key === 'graduation') {
            if (!person.clearedAt) return createElement('span', { className: 'notesEmpty', text: '—' });

            return createElement('span', {
                className: 'paymentBadge paid',
                text: 'Cleared',
                attributes: { title: `Cleared for graduation on ${formatAddedDate(person.clearedAt)}` }
            });
        }

        return document.createTextNode(getCellText(key, person));
    }

    /* =========================================================
       ROW SELECTION + TOOLBAR BUTTONS
       Every tab is selectable. The toolbar buttons turn on once a
       row is selected (same idea as the Attendance page).
    ========================================================= */

    function formatAddedDate(value) {
        if (!value) return '';
        return new Date(value).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    // e.g. "Oct 22, 2026, 3:45 PM"
    function formatDateTime(value) {
        if (!value) return '';
        return new Date(value).toLocaleString('en-US', {
            month: 'short', day: '2-digit', year: 'numeric',
            hour: 'numeric', minute: '2-digit'
        });
    }

    // Start of the chosen "Added" range, or null for any time.
    function getAddedCutoff(range) {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        if (range === 'today') return start;
        if (range === 'week') { start.setDate(start.getDate() - 6); return start; }
        if (range === 'month') return new Date(now.getFullYear(), now.getMonth(), 1);

        return null;
    }

    function getSelectedPerson() {
        if (selectedId === null) return null;
        return tabConfigs[activeTab].getRows().find(p => p.id === selectedId) || null;
    }

    function selectRow(personId) {
        selectedId = (selectedId === personId) ? null : personId;
        updateToolbarState();
        renderTable(activeTab);
    }

    function updateToolbarState() {
        const person = getSelectedPerson();
        const hasSelection = person !== null;

        // Which buttons show on which tab.
        elements.enrollButton.hidden = activeTab !== 'registrations' && activeTab !== 'graduates';
        elements.deleteApplicantButton.hidden = activeTab !== 'registrations';
        elements.courseUpdateButton.hidden = activeTab !== 'students';
        elements.cancelEnrollmentButton.hidden = activeTab !== 'students';
        elements.dropStudentButton.hidden = activeTab !== 'students';
        elements.reinstateButton.hidden = activeTab !== 'dropped';

        // Enabled only when a row is selected.
        elements.enrollButton.disabled = !hasSelection;
        elements.deleteApplicantButton.disabled = !hasSelection;
        elements.courseUpdateButton.disabled = !hasSelection;
        elements.reinstateButton.disabled = !hasSelection;

        // Drop Student is off when the student is cleared for graduation (move them back to Ready first).
        const canDrop = hasSelection && !person.clearedAt;
        elements.dropStudentButton.disabled = !canDrop;
        elements.dropStudentButton.title = hasSelection && !canDrop
            ? 'This student is cleared for graduation. Move them back to Ready on the Graduation page before dropping.'
            : 'Drop this student (they can be reinstated later)';

        // Cancel Enrollment is also off when the enrollment has payments or attendance.
        const canCancel = hasSelection && person.canCancel !== false;
        elements.cancelEnrollmentButton.disabled = !canCancel;
        elements.cancelEnrollmentButton.title = hasSelection && !canCancel
            ? (person.cancelBlockReason || 'This enrollment has active payments or attendance records, so it cannot be cancelled.')
            : 'Cancel this enrollment';

        updateActionHint(person);
    }

    // The line under the toolbar. Always visible: says what to do when nothing is
    // selected, what the selected row can do, or why an action is unavailable.
    function updateActionHint(person) {
        let text = '';
        let warning = false;

        if (!person) {
            text = activeTab === 'registrations'
                ? 'Select an applicant to enroll them or permanently delete their account.'
                : activeTab === 'students'
                    ? 'Select a student to update their course, drop them, or cancel their enrollment.'
                    : activeTab === 'dropped'
                        ? 'Select a dropped student to reinstate them.'
                        : 'Select a graduate to enroll them in another course.';
        } else {
            const name = getPersonName(person);

            if (activeTab === 'dropped') {
                text = `${name}: dropped on ${formatAddedDate(person.droppedAt)}${person.dropReason ? ` (${person.dropReason})` : ''}. Payments and attendance are kept. They can be reinstated.`;
            } else if (activeTab === 'students') {
                text = person.canCancel === false
                    ? `${name}: course can be updated, but the enrollment can't be cancelled. ${(person.cancelBlockReason || 'It has active payments or attendance records.').replace(/^Cannot cancel:\s*/, 'Reason: ')}`
                    : `${name}: course can be updated, and the enrollment can still be cancelled (no active payments or attendance yet).`;
                warning = person.canCancel === false;
            } else if (availableCoursesFor(person).length === 0) {
                text = `${name} has already graduated from every available course, so they can't be enrolled again.`;
                warning = true;
            } else if (activeTab === 'registrations') {
                text = `${name}: can be enrolled in a course, or permanently deleted.`;
            } else {
                text = `${name}: can be enrolled in another course (courses already graduated from are hidden).`;
            }
        }

        elements.actionHint.textContent = text;
        elements.actionHint.hidden = false;
        elements.actionHint.classList.toggle('isWarning', warning);
    }

    /* =========================================================
       RENDER TABLE
    ========================================================= */

    function renderTable(tabName) {
        const config = tabConfigs[tabName];
        if (!config) return;

        const searchTerm = elements.search.value.trim().toLowerCase();
        const courseFilter = elements.courseFilterInput.value.trim().toLowerCase();
        const paymentFilter = elements.paymentFilterInput.value.trim().toLowerCase();
        const addedCutoff = getAddedCutoff(elements.addedFilterInput.value);
        const direction = elements.sortFilterInput.value === 'asc' ? 1 : -1;
        const dateKey = config.dateKey || 'createdAt';   // enrolled / graduated / registered date

        const rows = config.getRows().filter(person => {
            const haystack = config.columns.map(col => getCellText(col.key, person)).join(' ').toLowerCase();
            if (!haystack.includes(searchTerm)) return false;

            if (courseFilter) {
                // Graduates: any course they graduated from. Students: their current course.
                const courseNames = tabName === 'graduates'
                    ? (person.graduatedCourses || []).map(name => String(name).toLowerCase())
                    : [String(person.course || '').toLowerCase()];

                if (!courseNames.includes(courseFilter)) return false;
            }

            if (tabName === 'students' && paymentFilter
                && String(person.paymentStatus || '').toLowerCase() !== paymentFilter) {
                return false;
            }

            if (addedCutoff && !(person[dateKey] && new Date(person[dateKey]) >= addedCutoff)) {
                return false;
            }

            return true;
        }).sort((a, b) => direction * (new Date(a[dateKey] || 0) - new Date(b[dateKey] || 0)));

        // A selected row that got filtered out or removed is no longer selected.
        if (selectedId !== null && !rows.some(p => p.id === selectedId)) {
            selectedId = null;
            updateToolbarState();
        }

        elements.tableHead.replaceChildren();
        elements.tableBody.replaceChildren();

        /* HEADER */

        const headerRow = createElement('tr', { id: 'registersHeaderRow' });

        config.columns.forEach((col, index) => {
            headerRow.append(createElement('th', {
                id: `registerColumn${index}`,
                text: col.label,
                attributes: { scope: 'col' }
            }));
        });

        elements.tableHead.append(headerRow);

        /* EMPTY STATE */

        if (!rows.length) {
            const emptyRow = createElement('tr');

            emptyRow.append(createElement('td', {
                id: 'registersEmptyCell',
                className: 'registersEmptyState',
                text: 'No matching records found.',
                attributes: { colSpan: config.columns.length }
            }));

            elements.tableBody.append(emptyRow);
            return;
        }

        /* ROWS */

        rows.forEach((person, rowIndex) => {
            const tableRow = createElement('tr', {
                id: `registerRow${capitalize(tabName)}${rowIndex}`
            });
            tableRow.dataset.id = person.id;

            tableRow.classList.add('studentRow');   // existing hover / selected styling
            if (selectedId === person.id) tableRow.classList.add('selectedRow');

            tableRow.addEventListener('click', event => {
                if (event.target.closest('.notesLink')) return;
                selectRow(person.id);
            });

            config.columns.forEach((col, cellIndex) => {
                const tableCell = createElement('td', {
                    id: `registerCell${capitalize(tabName)}${rowIndex}${cellIndex}`
                });

                tableCell.append(renderCell(col.key, person));
                tableRow.append(tableCell);
            });

            elements.tableBody.append(tableRow);
        });
    }

    /* =========================================================
       UPDATE TAB BUTTONS / TOOLBAR / HEADER BUTTON
    ========================================================= */

    function updateTabButtons(tabName) {
        elements.tabs.querySelectorAll('[data-register-tab]').forEach(button => {
            const isSelected = button.dataset.registerTab === tabName;

            button.classList.toggle('active', isSelected);
            button.setAttribute('aria-selected', String(isSelected));
        });
    }

    function updateToolbar(tabName) {
        elements.courseFilterDropdown.hidden = tabName === 'registrations';
        elements.paymentFilterDropdown.hidden = tabName !== 'students';
        updateToolbarState();
    }

    /* =========================================================
       SWITCH TAB
    ========================================================= */

    function switchTab(tabName) {
        if (!tabConfigs[tabName] || tabName === activeTab) return;

        elements.tableContainer.classList.add('isCollapsing');

        window.setTimeout(() => {
            activeTab = tabName;
            selectedId = null;

            window.FBSSDropdown.set(elements.courseFilterDropdown, '');
            window.FBSSDropdown.set(elements.paymentFilterDropdown, '');
            // back to the placeholder wording until a course is chosen
            const filterLabel = elements.courseFilterDropdown.querySelector('.registerDropdownToggle span');
            if (filterLabel) filterLabel.textContent = 'Filter by Course';

            updateTabButtons(activeTab);
            updateToolbar(activeTab);
            renderTable(activeTab);

            elements.tableContainer.classList.remove('isCollapsing');
        }, 180);
    }

    elements.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-register-tab]');
        if (!button) return;

        switchTab(button.dataset.registerTab);
    });

    /* =========================================================
       SEARCH
    ========================================================= */

    elements.search.addEventListener('input', () => {
        renderTable(activeTab);
    });

    /* =========================================================
       STUDENT MODAL — RESET / OPEN (enroll, course-update)
    ========================================================= */

    function resetStudentModal() {
        elements.modalCourseGroup.hidden = false;
        elements.modalScheduleGroup.hidden = false;
        elements.modalDaysGroup.hidden = false;
        elements.modalNotesGroup.hidden = false;
        elements.modalPriceChangeGroup.hidden = true;

        elements.studentNotes.value = '';

        setActiveDays([]);
    }

    // Courses a person can still take: every course except the ones they already graduated from.
    function availableCoursesFor(person) {
        const done = new Set(person.graduatedCourseIds || []);
        return COURSES.filter(course => !done.has(course.id));
    }

    // Rebuild the modal's course dropdown with only the given courses.
    function setCourseOptions(courses, selectedName) {
        const root = document.getElementById('courseDropdown');
        window.FBSSDropdown.setOptions(root, courses.map(c => ({ value: c.name, label: c.name })), selectedName);
    }

    function openEnrollModal(sourceTab, targetPersonId) {
        const source = sourceTab === 'registrations' ? registrations : graduates;
        const person = source.find(p => p.id === targetPersonId);
        if (!person) return;

        const available = availableCoursesFor(person);

        if (!available.length) {
            showToast(`${getPersonName(person)} has already graduated from every available course.`, 'danger');
            return;
        }

        modalContext = { mode: 'enroll', sourceTab, personId: targetPersonId };

        resetStudentModal();
        elements.modalTitle.textContent = 'Enrolling to course';
        elements.modalSubmitButton.textContent = 'Enroll';
        elements.modalStudentName.textContent = getPersonName(person);

        setCourseOptions(available, available[0].name);
        setDropdownValue('schedule', 'Morning');

        openModal(elements.studentModal);
    }

    // Shows "current price -> new price" in the course-update modal whenever
    // the selected course changes.
    function updatePriceChange() {
        if (!modalContext || modalContext.mode !== 'course-update') return;

        const student = students.find(s => s.id === modalContext.studentId);
        if (!student) return;

        const currentPrice = Number(student.price ?? student.balance) || 0;
        const newCourse = COURSES.find(c => c.name === elements.courseInput.value);

        if (!newCourse || newCourse.name === student.course) {
            elements.modalPriceChange.textContent = `${formatCurrency(currentPrice)} (no change)`;
            return;
        }

        elements.modalPriceChange.textContent =
            `${formatCurrency(currentPrice)} \u2192 ${formatCurrency(newCourse.price)}`;
    }

    function openCourseUpdateModal(studentId) {
        const student = students.find(s => s.id === studentId);
        if (!student) return;

        modalContext = { mode: 'course-update', studentId };

        resetStudentModal();
        elements.modalTitle.textContent = 'Updating student course';
        elements.modalSubmitButton.textContent = 'Update';
        elements.modalStudentName.textContent = getPersonName(student);

        const available = availableCoursesFor(student);
        const courseOption = available.find(c => c.name === student.course)?.name || (available[0]?.name || '');
        setCourseOptions(available, courseOption);

        elements.modalPriceChangeGroup.hidden = false;
        updatePriceChange();

        const { scheduleTime, days } = parseSchedule(student.schedule);
        setDropdownValue('schedule', scheduleTime);
        setActiveDays(days);

        elements.studentNotes.value = student.notes || '';

        openModal(elements.studentModal);
    }

    elements.courseUpdateButton.addEventListener('click', () => {
        if (activeTab !== 'students' || selectedId === null) return;
        openCourseUpdateModal(selectedId);
    });

    elements.enrollButton.addEventListener('click', () => {
        const person = getSelectedPerson();
        if (!person || (activeTab !== 'registrations' && activeTab !== 'graduates')) return;
        openEnrollModal(activeTab, person.id);   // 'registrations' or 'graduates'
    });

    elements.deleteApplicantButton.addEventListener('click', () => {
        const person = getSelectedPerson();
        if (!person || activeTab !== 'registrations') return;

        openConfirmModal({
            title: 'Permanently delete this account?',
            message: `You are about to delete the applicant account of ${getPersonName(person)} (${person.email}).`,
            warning:
                'THIS CANNOT BE UNDONE.\n' +
                'The account, profile and verification codes are erased for good. It is NOT archived or soft-deleted, and it cannot be restored.\n' +
                'They will have to register again from scratch to come back.',
            confirmLabel: 'Delete permanently',
            cancelLabel: 'Keep account',
            danger: true,
            requireText: 'DELETE',
            onConfirm: () => submitDeleteApplicant(person)
        });
    });

    async function submitDeleteApplicant(person) {
        try {
            const response = await fetch(`{{ url('/registers/applicants') }}/${person.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                registrations = registrations.filter(p => p.id !== person.id);
                selectedId = null;
                updateToolbarState();
                renderTable(activeTab);
                showToast(`Permanently deleted the account of ${getPersonName(person)}.`, 'success');
            } else {
                showToast(data.message || 'Something went wrong while deleting the account.', 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while deleting the account.', 'danger');
        }
    }

    elements.dropStudentButton.addEventListener('click', () => {
        const student = getSelectedPerson();
        if (!student || activeTab !== 'students' || student.clearedAt) return;

        openConfirmModal({
            title: 'Drop student?',
            message: `Drop ${getPersonName(student)} from ${student.course}? They will move to the Dropped tab. Payments and attendance are kept, and you can reinstate them later.`,
            confirmLabel: 'Drop Student',
            cancelLabel: 'Keep',
            danger: true,
            askReason: true,
            onConfirm: reason => submitDropStudent(student, reason)
        });
    });

    async function submitDropStudent(student, reason) {
        try {
            const response = await fetch(`{{ url('/registers/students') }}/${student.id}/drop`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ drop_reason: reason || null })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                students = students.filter(s => s.id !== student.id);
                dropped.push(data.dropped);

                selectedId = null;
                updateToolbarState();
                renderTable(activeTab);
                showToast(`Dropped ${getPersonName(student)}.`, 'success');
            } else {
                showToast(data.message || 'Something went wrong while dropping the student.', 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while dropping the student.', 'danger');
        }
    }

    elements.reinstateButton.addEventListener('click', () => {
        const student = getSelectedPerson();
        if (!student || activeTab !== 'dropped') return;

        openConfirmModal({
            title: 'Reinstate student?',
            message: `Reinstate ${getPersonName(student)} in ${student.course}? They will return to the Students tab.`,
            confirmLabel: 'Reinstate',
            cancelLabel: 'Cancel',
            onConfirm: () => submitReinstateStudent(student)
        });
    });

    async function submitReinstateStudent(student) {
        try {
            const response = await fetch(`{{ url('/registers/students') }}/${student.id}/reinstate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                dropped = dropped.filter(s => s.id !== student.id);
                students.push(data.student);

                selectedId = null;
                updateToolbarState();
                renderTable(activeTab);
                showToast(`Reinstated ${getPersonName(student)}.`, 'success');
            } else {
                showToast(data.message || 'Something went wrong while reinstating the student.', 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while reinstating the student.', 'danger');
        }
    }

    elements.cancelEnrollmentButton.addEventListener('click', () => {
        const student = getSelectedPerson();
        if (!student || activeTab !== 'students' || student.canCancel === false) return;

        openConfirmModal({
            title: 'Cancel enrollment?',
            message: `Cancel ${getPersonName(student)}'s enrollment in ${student.course}? This deletes the enrollment and cannot be undone.`,
            warning: student.archivedPaymentCount > 0
                ? `This enrollment has ${student.archivedPaymentCount} archived payment ${student.archivedPaymentCount === 1 ? 'record' : 'records'}. ${student.archivedPaymentCount === 1 ? 'It' : 'They'} will be permanently deleted together with the enrollment.`
                : '',
            confirmLabel: 'Cancel Enrollment',
            cancelLabel: 'Keep',
            onConfirm: () => submitCancelEnrollment(student)
        });
    });

    async function submitCancelEnrollment(student) {
        try {
            const response = await fetch(`{{ url('/registers/students') }}/${student.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (response.ok && data.success) {
                students = students.filter(s => s.id !== student.id);

                // The server says where the person landed (applicant, graduate, or neither).
                if (data.applicant) registrations.push(data.applicant);
                if (data.graduate) graduates.push(data.graduate);

                selectedId = null;
                updateToolbarState();
                renderTable(activeTab);
                showToast(`Cancelled the enrollment of ${getPersonName(student)}.`, 'success');
            } else {
                showToast(data.message || 'Something went wrong while cancelling the enrollment.', 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while cancelling the enrollment.', 'danger');
        }
    }

    elements.modalCancelButton.addEventListener('click', () => {
        modalContext = null;
        closeModal(elements.studentModal);
    });

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    async function submitRegistrationEnroll(person, sourceTab) {
        const courseId = COURSE_NAME_TO_ID[elements.courseInput.value];

        if (!courseId) {
            showToast('Please select a valid course.', 'danger');
            return;
        }

        elements.modalSubmitButton.disabled = true;
        elements.modalSubmitButton.textContent = 'Enrolling...';

        try {
            const response = await fetch('{{ url('/registers/enroll') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    user_id: person.id,
                    course_id: courseId,
                    scheduled_time: buildScheduleString(elements.scheduleInput.value, getActiveDays()),
                    notes: elements.studentNotes.value.trim()
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                if (sourceTab === 'graduates') {
                    graduates = graduates.filter(p => p.id !== person.id);
                } else {
                    registrations = registrations.filter(p => p.id !== person.id);
                }

                // The server sends the complete row (names, enrolled date/time, balance...),
                // so the new student shows correctly right away.
                students.push(data.student);

                modalContext = null;
                closeModal(elements.studentModal);
                renderTable(activeTab);
                showToast(`Successfully enrolled ${getPersonName(person)}.`, 'success');
            } else {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                showToast(firstError || data.message || 'Something went wrong while enrolling.', 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while enrolling.', 'danger');
        } finally {
            elements.modalSubmitButton.disabled = false;
            elements.modalSubmitButton.textContent = 'Enroll';
        }
    }

    async function submitCourseUpdate(student) {
        const courseId = COURSE_NAME_TO_ID[elements.courseInput.value];

        if (!courseId) {
            showToast('Please select a valid course.', 'danger');
            return;
        }

        elements.modalSubmitButton.disabled = true;
        elements.modalSubmitButton.textContent = 'Updating...';

        try {
            const response = await fetch(`{{ url('/registers/students') }}/${student.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    course_id: courseId,
                    scheduled_time: buildScheduleString(elements.scheduleInput.value, getActiveDays()),
                    notes: elements.studentNotes.value.trim()
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                student.course = elements.courseInput.value;
                student.price = data.price;
                student.balance = data.balance;
                student.paymentStatus = data.paymentStatus;
                student.schedule = buildScheduleString(elements.scheduleInput.value, getActiveDays());
                student.notes = elements.studentNotes.value.trim();

                modalContext = null;
                closeModal(elements.studentModal);
                renderTable(activeTab);
                showToast(`Successfully updated the course for ${getPersonName(student)}.`, 'success');
            } else {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                showToast(firstError || data.message || 'Something went wrong while updating the course.', 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while updating the course.', 'danger');
        } finally {
            elements.modalSubmitButton.disabled = false;
            elements.modalSubmitButton.textContent = 'Update';
        }
    }

    elements.modalSubmitButton.addEventListener('click', async () => {
        if (!modalContext) {
            closeModal(elements.studentModal);
            return;
        }

        // Enrolling (applicants and graduates) and course-update
        // save real rows via RegistersController.
        if (modalContext.mode === 'enroll') {
            const source = modalContext.sourceTab === 'graduates' ? graduates : registrations;
            const person = source.find(p => p.id === modalContext.personId);
            if (person) {
                await submitRegistrationEnroll(person, modalContext.sourceTab);
            } else {
                modalContext = null;
                closeModal(elements.studentModal);
            }
            return;
        }

        if (modalContext.mode === 'course-update') {
            const student = students.find(s => s.id === modalContext.studentId);
            if (student) {
                await submitCourseUpdate(student);
            } else {
                modalContext = null;
                closeModal(elements.studentModal);
            }
            return;
        }

        modalContext = null;
        closeModal(elements.studentModal);
    });

    /* =========================================================
       DAY BUTTONS
    ========================================================= */

    document.querySelectorAll('.dayButton').forEach(button => {
        button.addEventListener('click', () => {
            button.classList.toggle('active');
        });
    });

    /* =========================================================
       DROPDOWNS — behaviour lives in assets/js/fbss-dropdown.js
       (open/close, selection, positioning). This page only reacts
       to the `dropdown:change` event the shared script fires.
    ========================================================= */

    elements.courseFilterDropdown.addEventListener('dropdown:change', () => renderTable(activeTab));

    [elements.paymentFilterDropdown, elements.addedFilterDropdown, elements.sortFilterDropdown]
        .forEach(dropdown => dropdown.addEventListener('dropdown:change', () => renderTable(activeTab)));
    document.getElementById('courseDropdown').addEventListener('dropdown:change', updatePriceChange);

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('.modalOverlay:not([hidden])').forEach(modal => {
            if (modal === elements.studentModal) modalContext = null;
            if (modal === elements.confirmModal) pendingConfirmAction = null;
            closeModal(modal);
        });
        // open dropdowns are closed by the shared script on Escape
    });

    /* =========================================================
       PREVENT PAGE-LEVEL SCROLL / FIT CONTAINER
    ========================================================= */

    document.documentElement.style.height = '100%';
    document.documentElement.style.overflow = 'hidden';
    document.body.style.height = '100%';
    document.body.style.margin = '0';
    document.body.style.overflow = 'hidden';

    const registersContainerEl = document.getElementById('registersContainer');

    function fitRegistersContainer() {
        if (!registersContainerEl) return;

        const top = registersContainerEl.getBoundingClientRect().top + window.scrollY;
        const available = window.innerHeight - top;

        registersContainerEl.style.height = `${Math.max(available, 320)}px`;
    }

    window.addEventListener('resize', fitRegistersContainer);
    window.addEventListener('load', fitRegistersContainer);

    /* =========================================================
       GRADUATES COURSE FILTER — options come from the real courses
    ========================================================= */

    function buildCourseFilterOptions() {
        window.FBSSDropdown.setOptions(
            elements.courseFilterDropdown,
            [{ value: '', label: 'All Courses' }].concat(COURSES.map(c => ({ value: c.name, label: c.name }))),
            ''
        );

        const label = elements.courseFilterDropdown.querySelector('.registerDropdownToggle span');
        if (label) label.textContent = 'Filter by Course';
    }

    /* =========================================================
       INITIALIZE
    ========================================================= */

    buildCourseFilterOptions();

    updateTabButtons(activeTab);
    updateToolbar(activeTab);
    renderTable(activeTab);
    fitRegistersContainer();

})();
</script>