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
    overflow: hidden;
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

#addAthleteButton {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    flex-shrink: 0;
    white-space: nowrap;
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
   Empty State
========================================================= */

.registersEmptyState {
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

/* =========================================================
   Add Athlete Modal
========================================================= */

.athleteModalContent {
    width: min(480px, 95%);
}

.athleteModalSearchContainer {
    width: 100%;
    margin-bottom: 1rem;
}

.athleteModalSelectAllRow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding-bottom: .75rem;
    margin-bottom: .5rem;
    border-bottom: 1px solid #e7ebef;
}

.athleteModalSelectAllLabel {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 0;
    color: #212529;
    font-size: .875rem;
    font-weight: 600;
    cursor: pointer;
}

.athleteModalSelectAllLabel input {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.athleteModalSelectionCount {
    color: #6c757d;
    font-size: .8125rem;
}

.athleteModalCandidateList {
    display: grid;
    gap: .25rem;
    max-height: 320px;
    overflow-y: auto;
}

.athleteCandidateRow {
    display: grid;
    grid-template-columns: auto auto 1fr auto;
    align-items: center;
    gap: .75rem;
    padding: .6rem .5rem;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color .15s ease;
}

.athleteCandidateRow:hover {
    background: #f8fafb;
}

.athleteCandidateRow input {
    width: 16px;
    height: 16px;
    cursor: pointer;
}

.athleteCandidateAvatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: .75rem;
    font-weight: 700;
    color: #045199;
    background: #d6e6ff;
}

.athleteCandidateAvatar.avatarColor1 {
    color: #0f6d5c;
    background: #d3f2ea;
}

.athleteCandidateAvatar.avatarColor2 {
    color: #7a3fb0;
    background: #ecdcfb;
}

.athleteCandidateAvatar.avatarColor3 {
    color: #b26a00;
    background: #ffe8c9;
}

.athleteCandidateAvatar.avatarColor4 {
    color: #c2255c;
    background: #ffd9e6;
}

.athleteCandidateInfo {
    display: grid;
    gap: .1rem;
    min-width: 0;
}

.athleteCandidateName {
    color: #212529;
    font-size: .875rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.athleteCandidateMiddle {
    color: #6c757d;
    font-weight: 400;
}

.athleteCandidateEmail,
.athleteCandidateDate {
    color: #6c757d;
    font-size: .75rem;
}

.athleteCandidateAge {
    color: #495057;
    font-size: .875rem;
    font-weight: 600;
}

.athleteCandidateEmpty {
    padding: 1.5rem;
    color: #6c757d;
    font-size: .875rem;
    text-align: center;
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

@media (max-width: 575.98px) {
    .registersContainer {
        height: 100vh;
        padding: 1rem;
    }

    .registersTab {
        min-width: 125px;
    }

    .registerCourseFilter .registerDropdownToggle {
        max-width: 140px;
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
            <h2 class="mb-1" id="registersTitle">Registers</h2>
            <p class="text-muted mb-0" id="registersDescription">
                Manage new registrations, students, athletes, and graduates.
            </p>
        </div>

        <button class="btn btn-primary registersTab" id="addAthleteButton" type="button" hidden>
            <i class="bi bi-plus-lg" id="addAthleteIcon"></i>
            Add New Athlete
        </button>
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
        <button class="btn btn-outline-primary btn-tab registersTab" type="button" data-register-tab="athletes" aria-selected="false">
            Athletes
        </button>
        <button class="btn btn-outline-primary btn-tab registersTab" type="button" data-register-tab="graduates" aria-selected="false">
            Graduates
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

        <!-- Course Filter -->
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

        <!-- Student Actions (enabled once a student row is selected) -->
        <div class="registersToolbarActions" id="registersToolbarActions" hidden>
            <button class="btn btn-primary" id="courseUpdateButton" type="button" disabled>Course update</button>
        </div>

    </div>


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

        <div class="modalActions">
            <button class="btn btn-outline-danger" id="confirmModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="confirmModalConfirmButton" type="button">Confirm</button>
        </div>

    </div>
</div>


<!-- =========================================================
     ADD ATHLETE MODAL
========================================================= -->

<div class="modalOverlay" id="athleteModal" hidden>
    <div class="modalContentBox athleteModalContent" role="dialog" aria-modal="true" aria-labelledby="athleteModalTitle">

        <h3 id="athleteModalTitle">Adding New Athlete</h3>

        <div class="registersSearchContainer athleteModalSearchContainer">
            <i class="bi bi-search registersSearchIcon" aria-hidden="true"></i>
            <input class="form-control registersSearchInput" id="athleteModalSearch" type="search" placeholder="Search" autocomplete="off" aria-label="Search pending registrants">
        </div>

        <div class="athleteModalSelectAllRow">
            <label class="athleteModalSelectAllLabel" for="athleteModalSelectAllCheckbox">
                <input type="checkbox" id="athleteModalSelectAllCheckbox">
                Select all
            </label>
            <span class="athleteModalSelectionCount" id="athleteModalSelectionCount">0 selected · 0 pending</span>
        </div>

        <div class="athleteModalCandidateList" id="athleteModalCandidateList"></div>

        <div class="modalActions">
            <button class="btn btn-outline-danger" id="athleteModalBackButton" type="button">Back</button>
            <button class="btn btn-success" id="athleteModalConfirmButton" type="button">Confirm</button>
        </div>

    </div>
</div>


<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    /* =========================================================
       DATA — every list comes from RegistersController::data():
       applicants, current students, athletes, and graduates who are
       not enrolled right now. People move between lists as the admin
       acts, and the server is always asked first.
    ========================================================= */

    let registrations = @json($registrations);

    // Real courses from tbl_courses. Dropdowns use the course NAME as their
    // value; this map converts name -> id when a request is sent.
    const COURSES = @json($courses);
    const COURSE_NAME_TO_ID = Object.fromEntries(COURSES.map(c => [c.name, c.id]));

    let students = @json($students);
    let athletes = @json($athletes);
    let graduates = @json($graduates);

    const selectedAthleteCandidateIds = new Set();

    /* =========================================================
       TAB CONFIG — maps each tab to its live data, its columns
       (label shown + key read off each person object), and its
       row-level actions.
    ========================================================= */

    const tabConfigs = {
        registrations: {
            getRows: () => registrations,
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Email Address', key: 'email' },
                { label: 'Birthdate', key: 'birthdate' },
                { label: 'Age', key: 'age' }
            ],
            actions: ['Enroll', 'Delete']
        },
        students: {
            getRows: () => students,
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Current Course', key: 'course' },
                { label: 'Schedule', key: 'schedule' },
                { label: 'Payment Status', key: 'paymentStatus' },
                { label: 'Notes', key: 'notes' }
            ],
            actions: []
        },
        athletes: {
            getRows: () => athletes,
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Email Address', key: 'email' },
                { label: 'Birthdate', key: 'birthdate' },
                { label: 'Age', key: 'age' }
            ],
            actions: ['Revoke']
        },
        graduates: {
            getRows: () => graduates,
            columns: [
                { label: 'Last Name', key: 'lastName' },
                { label: 'First Name', key: 'firstName' },
                { label: 'Middle Name', key: 'middleName' },
                { label: 'Email Address', key: 'email' },
                { label: 'Last Course', key: 'lastCourse' },
                { label: 'Graduated On', key: 'graduatedAt' },
                { label: 'Birthdate', key: 'birthdate' },
                { label: 'Age', key: 'age' }
            ],
            actions: ['Enroll']
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
        toolbarActions: document.getElementById('registersToolbarActions'),
        courseFilterDropdown: document.getElementById('courseFilterDropdown'),
        courseFilterInput: document.getElementById('courseFilterInput'),

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

        courseUpdateButton: document.getElementById('courseUpdateButton'),

        notesModal: document.getElementById('notesModal'),
        notesModalStudentName: document.getElementById('notesModalStudentName'),
        notesModalText: document.getElementById('notesModalText'),
        notesModalCloseButton: document.getElementById('notesModalCloseButton'),

        confirmModal: document.getElementById('confirmModal'),
        confirmModalTitle: document.getElementById('confirmModalTitle'),
        confirmModalMessage: document.getElementById('confirmModalMessage'),
        confirmModalCancelButton: document.getElementById('confirmModalCancelButton'),
        confirmModalConfirmButton: document.getElementById('confirmModalConfirmButton'),

        addAthleteButton: document.getElementById('addAthleteButton'),
        athleteModal: document.getElementById('athleteModal'),
        athleteModalSearch: document.getElementById('athleteModalSearch'),
        athleteModalSelectAllCheckbox: document.getElementById('athleteModalSelectAllCheckbox'),
        athleteModalSelectionCount: document.getElementById('athleteModalSelectionCount'),
        athleteModalCandidateList: document.getElementById('athleteModalCandidateList'),
        athleteModalBackButton: document.getElementById('athleteModalBackButton'),
        athleteModalConfirmButton: document.getElementById('athleteModalConfirmButton')
    };

    /* =========================================================
       STATE
    ========================================================= */

    let activeTab = 'registrations';
    let selectedStudentId = null;   // currently selected row on the Students tab
    let modalContext = null;        // { mode: 'enroll' | 'course-update', ... }
    let pendingConfirmAction = null; // callback fired by the confirm modal's Confirm/OK button

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

    function openConfirmModal({ title, message, confirmLabel = 'Confirm', cancelLabel = 'Cancel', onConfirm }) {
        elements.confirmModalTitle.textContent = title;
        elements.confirmModalMessage.textContent = message;
        elements.confirmModalConfirmButton.textContent = confirmLabel;
        elements.confirmModalCancelButton.textContent = cancelLabel;
        elements.confirmModalCancelButton.hidden = false;
        elements.confirmModalConfirmButton.className = 'btn btn-primary';

        pendingConfirmAction = typeof onConfirm === 'function' ? onConfirm : null;

        openModal(elements.confirmModal);
    }

    function openInfoModal({ title, message, okLabel = 'OK' }) {
        elements.confirmModalTitle.textContent = title;
        elements.confirmModalMessage.textContent = message;
        elements.confirmModalConfirmButton.textContent = okLabel;
        elements.confirmModalCancelButton.hidden = true;

        pendingConfirmAction = null;

        openModal(elements.confirmModal);
    }

    function closeConfirmModal() {
        pendingConfirmAction = null;
        closeModal(elements.confirmModal);
    }

    elements.confirmModalCancelButton.addEventListener('click', () => {
        closeConfirmModal();
    });

    elements.confirmModalConfirmButton.addEventListener('click', () => {
        const action = pendingConfirmAction;
        pendingConfirmAction = null;
        closeModal(elements.confirmModal);
        if (action) action();
    });

    /* =========================================================
       RENDER CELL
    ========================================================= */

    function renderCell(key, person) {
        const value = person[key] ?? '';

        if (key === 'paymentStatus') return renderPaymentStatus(value);
        if (key === 'notes') return renderNotesCell(person, value);

        return document.createTextNode(value);
    }

    /* =========================================================
       TABLE ACTIONS
    ========================================================= */

    function handleTableAction(tabName, action, personId) {
        if (tabName === 'registrations' && action === 'Enroll') {
            openEnrollModal('registrations', personId);
            return;
        }

        if (tabName === 'registrations' && action === 'Delete') {
            const person = registrations.find(p => p.id === personId);
            if (!person) return;

            openConfirmModal({
                title: 'Remove registrant?',
                message: `Remove ${getPersonName(person)} from New Registers?`,
                confirmLabel: 'Remove',
                onConfirm: () => {
                    registrations = registrations.filter(p => p.id !== personId);
                    renderTable(activeTab);
                }
            });
            return;
        }

        if (tabName === 'athletes' && action === 'Revoke') {
            const person = athletes.find(p => p.id === personId);
            if (!person) return;

            openConfirmModal({
                title: 'Revoke athlete status?',
                message: `Revoke ${getPersonName(person)}'s athlete status? They go back to Applicants, or to Graduates if they graduated before.`,
                confirmLabel: 'Revoke',
                onConfirm: () => revokeAthlete(person)
            });
            return;
        }

        if (tabName === 'graduates' && action === 'Enroll') {
            openEnrollModal('graduates', personId);
            return;
        }
    }

    function renderActionCell(tabName, actions, person, rowIndex) {
        const cell = createElement('td', {
            id: `registerAction${capitalize(tabName)}${rowIndex}`
        });

        actions.forEach(action => {
            const buttonClass = (action === 'Delete' || action === 'Revoke')
                ? 'btn btn-outline-danger btn-sm me-1'
                : 'btn btn-success btn-sm me-1';

            const button = createElement('button', {
                id: `registerActionButton${capitalize(tabName)}${rowIndex}${capitalize(action)}`,
                className: buttonClass,
                text: action,
                attributes: { type: 'button' }
            });

            button.addEventListener('click', event => {
                event.stopPropagation();
                handleTableAction(tabName, action, person.id);
            });

            cell.append(button);
        });

        return cell;
    }

    /* =========================================================
       STUDENT ROW SELECTION
    ========================================================= */

    function selectStudentRow(personId) {
        selectedStudentId = (selectedStudentId === personId) ? null : personId;
        updateStudentToolbarState();
        renderTable('students');
    }

    function updateStudentToolbarState() {
        const hasSelection = selectedStudentId !== null;
        elements.courseUpdateButton.disabled = !hasSelection;
    }

    /* =========================================================
       RENDER TABLE
    ========================================================= */

    function renderTable(tabName) {
        const config = tabConfigs[tabName];
        if (!config) return;

        const searchTerm = elements.search.value.trim().toLowerCase();
        const courseFilter = elements.courseFilterInput.value.trim().toLowerCase();

        const rows = config.getRows().filter(person => {
            const haystack = config.columns.map(col => person[col.key] ?? '').join(' ').toLowerCase();
            const matchesSearch = haystack.includes(searchTerm);

            // On Graduates the filter matches any course the person graduated from.
            const graduatedCourses = tabName === 'graduates'
                ? (person.graduatedCourses || []).map(name => String(name).toLowerCase())
                : [];
            const matchesCourse = !courseFilter || graduatedCourses.includes(courseFilter);

            return matchesSearch && matchesCourse;
        });

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

        if (config.actions.length) {
            headerRow.append(createElement('th', {
                id: 'registerActionHeader',
                text: 'Action',
                attributes: { scope: 'col' }
            }));
        }

        elements.tableHead.append(headerRow);

        /* EMPTY STATE */

        if (!rows.length) {
            const emptyRow = createElement('tr');

            emptyRow.append(createElement('td', {
                id: 'registersEmptyCell',
                className: 'registersEmptyState',
                text: 'No matching records found.',
                attributes: {
                    colSpan: config.columns.length + (config.actions.length ? 1 : 0)
                }
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

            if (tabName === 'students') {
                tableRow.classList.add('studentRow');
                if (selectedStudentId === person.id) tableRow.classList.add('selectedRow');

                tableRow.addEventListener('click', event => {
                    if (event.target.closest('.notesLink')) return;
                    selectStudentRow(person.id);
                });
            }

            config.columns.forEach((col, cellIndex) => {
                const tableCell = createElement('td', {
                    id: `registerCell${capitalize(tabName)}${rowIndex}${cellIndex}`
                });

                tableCell.append(renderCell(col.key, person));
                tableRow.append(tableCell);
            });

            if (config.actions.length) {
                tableRow.append(renderActionCell(tabName, config.actions, person, rowIndex));
            }

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
        elements.toolbarActions.hidden = tabName !== 'students';
        elements.courseFilterDropdown.hidden = tabName !== 'graduates';
    }

    function updateHeaderButton(tabName) {
        elements.addAthleteButton.hidden = tabName !== 'athletes';
    }

    /* =========================================================
       SWITCH TAB
    ========================================================= */

    function switchTab(tabName) {
        if (!tabConfigs[tabName] || tabName === activeTab) return;

        elements.tableContainer.classList.add('isCollapsing');

        window.setTimeout(() => {
            activeTab = tabName;
            selectedStudentId = null;
            updateStudentToolbarState();

            window.FBSSDropdown.set(elements.courseFilterDropdown, '');
            // back to the placeholder wording until a course is chosen
            const filterLabel = elements.courseFilterDropdown.querySelector('.registerDropdownToggle span');
            if (filterLabel) filterLabel.textContent = 'Filter by Course';

            updateTabButtons(activeTab);
            updateToolbar(activeTab);
            updateHeaderButton(activeTab);
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
        if (selectedStudentId === null) return;
        openCourseUpdateModal(selectedStudentId);
    });

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

                students.push({
                    id: data.student.id,
                    lastName: person.lastName,
                    firstName: person.firstName,
                    middleName: person.middleName,
                    course: data.student.course,
                    schedule: data.student.schedule,
                    paymentStatus: data.student.paymentStatus,
                    notes: data.student.notes,
                    balance: data.student.balance,
                    price: data.student.price,
                    graduatedCourseIds: person.graduatedCourseIds || []
                });

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
       SERVER CALLS FOR ATHLETES
    ========================================================= */

    async function sendJson(url, method, body) {
        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json'
            },
            body: body === undefined ? undefined : JSON.stringify(body)
        });

        let data = {};
        try { data = await response.json(); } catch (e) { /* non-JSON error page */ }

        return { ok: response.ok && data.success !== false, data };
    }

    function firstError(data, fallback) {
        const error = data.errors ? Object.values(data.errors)[0][0] : null;
        return error || data.message || fallback;
    }

    // The server sends back fresh Applicants / Graduates / Athletes lists.
    function applyLists(data) {
        registrations = data.registrations || [];
        graduates = data.graduates || [];
        athletes = data.athletes || [];
    }

    async function revokeAthlete(person) {
        try {
            const result = await sendJson(`{{ url('/registers/athletes') }}/${person.id}/revoke`, 'POST');

            if (result.ok) {
                applyLists(result.data);
                renderTable(activeTab);
                showToast(`Revoked ${getPersonName(person)}'s athlete status.`, 'success');
            } else {
                showToast(firstError(result.data, 'Something went wrong while revoking athlete status.'), 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while revoking athlete status.', 'danger');
        }
    }

    /* =========================================================
       ADD ATHLETE — candidates are applicants plus graduates who
       are not enrolled right now (the server checks this again).
    ========================================================= */

    function athleteCandidates() {
        return [
            ...registrations.map(person => ({ ...person, source: 'Applicant' })),
            ...graduates.map(person => ({ ...person, source: 'Graduate' }))
        ];
    }

    function getCandidateAvatarInitials(candidate) {
        return `${candidate.firstName.charAt(0)}${candidate.lastName.charAt(0)}`.toUpperCase();
    }

    function renderAthleteCandidates() {
        const searchTerm = elements.athleteModalSearch.value.trim().toLowerCase();
        const candidates = athleteCandidates();

        const visible = candidates.filter(person => {
            const haystack = `${person.lastName} ${person.firstName} ${person.middleName} ${person.email} ${person.source}`.toLowerCase();
            return haystack.includes(searchTerm);
        });

        elements.athleteModalCandidateList.replaceChildren();

        if (!visible.length) {
            elements.athleteModalCandidateList.append(createElement('p', {
                className: 'athleteCandidateEmpty',
                text: candidates.length
                    ? 'No matching applicants or graduates.'
                    : 'No applicants or graduates to add.'
            }));
        } else {
            visible.forEach((candidate, index) => {
                const row = createElement('label', {
                    id: `athleteCandidateRow${candidate.id}`,
                    className: 'athleteCandidateRow'
                });

                const checkbox = createElement('input', {
                    id: `athleteCandidateCheckbox${candidate.id}`,
                    attributes: { type: 'checkbox' }
                });
                checkbox.checked = selectedAthleteCandidateIds.has(candidate.id);
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        selectedAthleteCandidateIds.add(candidate.id);
                    } else {
                        selectedAthleteCandidateIds.delete(candidate.id);
                    }
                    updateAthleteSelectionSummary();
                });

                const avatar = createElement('span', {
                    id: `athleteCandidateAvatar${candidate.id}`,
                    className: `athleteCandidateAvatar avatarColor${(index % 4) + 1}`,
                    text: getCandidateAvatarInitials(candidate)
                });

                const info = createElement('span', { className: 'athleteCandidateInfo' });

                const name = createElement('span', { className: 'athleteCandidateName' });
                name.append(
                    document.createTextNode(`${candidate.lastName}, ${candidate.firstName} `),
                    createElement('span', {
                        className: 'athleteCandidateMiddle',
                        text: candidate.middleName && candidate.middleName !== '--' ? candidate.middleName : ''
                    })
                );

                const email = createElement('span', { className: 'athleteCandidateEmail', text: candidate.email });
                const date = createElement('span', {
                    className: 'athleteCandidateDate',
                    text: candidate.birthdate ? `${candidate.source} · ${candidate.birthdate}` : candidate.source
                });

                info.append(name, email, date);

                const age = createElement('span', { className: 'athleteCandidateAge', text: candidate.age });

                row.append(checkbox, avatar, info, age);
                elements.athleteModalCandidateList.append(row);
            });
        }

        updateAthleteSelectionSummary();
    }

    function updateAthleteSelectionSummary() {
        const total = athleteCandidates().length;
        const selected = selectedAthleteCandidateIds.size;

        elements.athleteModalSelectionCount.textContent = `${selected} selected · ${total} eligible`;

        elements.athleteModalSelectAllCheckbox.checked = total > 0 && selected === total;
        elements.athleteModalSelectAllCheckbox.indeterminate = selected > 0 && selected < total;
    }

    function openAthleteModal() {
        elements.athleteModalSearch.value = '';
        selectedAthleteCandidateIds.clear();
        renderAthleteCandidates();
        openModal(elements.athleteModal);
    }

    function closeAthleteModal() {
        closeModal(elements.athleteModal);
    }

    elements.addAthleteButton.addEventListener('click', openAthleteModal);
    elements.athleteModalBackButton.addEventListener('click', closeAthleteModal);
    elements.athleteModalSearch.addEventListener('input', renderAthleteCandidates);

    elements.athleteModalSelectAllCheckbox.addEventListener('change', () => {
        if (elements.athleteModalSelectAllCheckbox.checked) {
            athleteCandidates().forEach(person => selectedAthleteCandidateIds.add(person.id));
        } else {
            selectedAthleteCandidateIds.clear();
        }
        renderAthleteCandidates();
    });

    /* Confirming asks the server to make every selected person an athlete.
       If anyone is not allowed (e.g. enrolled right now), nobody changes. */
    elements.athleteModalConfirmButton.addEventListener('click', async () => {
        if (!selectedAthleteCandidateIds.size) {
            closeAthleteModal();
            return;
        }

        const ids = Array.from(selectedAthleteCandidateIds);

        elements.athleteModalConfirmButton.disabled = true;

        try {
            const result = await sendJson('{{ url('/registers/athletes') }}', 'POST', { user_ids: ids });

            if (result.ok) {
                applyLists(result.data);
                selectedAthleteCandidateIds.clear();
                closeAthleteModal();
                renderTable(activeTab);
                showToast(`Successfully added ${ids.length} athlete${ids.length === 1 ? '' : 's'}.`, 'success');
            } else {
                showToast(firstError(result.data, 'Something went wrong while adding athletes.'), 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while adding athletes.', 'danger');
        } finally {
            elements.athleteModalConfirmButton.disabled = false;
        }
    });

    /* =========================================================
       DROPDOWNS — behaviour lives in assets/js/fbss-dropdown.js
       (open/close, selection, positioning). This page only reacts
       to the `dropdown:change` event the shared script fires.
    ========================================================= */

    elements.courseFilterDropdown.addEventListener('dropdown:change', () => renderTable(activeTab));
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
    updateHeaderButton(activeTab);
    updateStudentToolbarState();
    renderTable(activeTab);
    fitRegistersContainer();

})();
</script>