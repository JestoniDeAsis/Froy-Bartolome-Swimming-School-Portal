<style>
/* =========================================================
   Coaches
========================================================= */

.coachesContainer {
    width: 100%;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.coachesHeader {
    flex: 0 0 auto;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

#addCoachButton {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    flex-shrink: 0;
    white-space: nowrap;
}

/* =========================================================
   Toolbar
========================================================= */

.coachesToolbar {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

.coachesSearchContainer {
    position: relative;
    width: min(300px, 100%);
    flex-shrink: 0;
}

.coachesSearchIcon {
    position: absolute;
    top: 50%;
    left: .75rem;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.coachesSearchInput {
    height: 40px;
    box-sizing: border-box;
    border-radius: 20px;
    padding-left: 2.25rem;
}

/* =========================================================
   Scrollable Table Container
========================================================= */

.coachesTableContainer {
    --coach-row-height: 49px;
    --coach-header-height: 49px;
    --coach-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;

    max-height: calc(
        var(--coach-header-height) +
        (var(--coach-row-height) * var(--coach-visible-rows))
    );

    width: 100%;
    overflow-y: auto;
    overflow-x: auto;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    box-sizing: border-box;
}

.coachesTableContainer::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.coachesTableContainer::-webkit-scrollbar-track {
    background: #f1f3f5;
    border-radius: 10px;
}

.coachesTableContainer::-webkit-scrollbar-thumb {
    background: #c9ced3;
    border-radius: 10px;
}

.coachesTableContainer::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* =========================================================
   Table
========================================================= */

.coachesTable {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.coachesTable thead {
    background: #f8fafb;
}

.coachesTable thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafb;
    color: #667085;
    font-weight: 600;
    border-bottom: 1px solid #e7ebef;
    box-shadow: 0 1px 0 #e7ebef;
}

.coachesTable th,
.coachesTable td {
    padding: .75rem 1rem;
    vertical-align: middle;
}

.coachesTable th:last-child,
.coachesTable td:last-child {
    text-align: center;
}

.coachesTable tbody tr:hover {
    background: #f8fafb;
}

/* =========================================================
   Empty State
========================================================= */

.coachesEmptyState {
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}

/* =========================================================
   Status Badges — Active / Held / Inactive
   (kept consistent between the table and the edit form)
========================================================= */

.statusBadge {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 12px;
    font-size: .75rem;
    font-weight: 600;
}

.statusBadge.active {
    background: #e6f7ec;
    color: #1a7f3c;
}

.statusBadge.held {
    background: #fff4e0;
    color: #b26a00;
}

.statusBadge.inactive {
    background: #fdebec;
    color: #c22b3a;
}

/* =========================================================
   Row Actions
========================================================= */

.coachEditButton {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

/* =========================================================
   Shared Modal
========================================================= */

.modalOverlay[hidden] {
    display: none !important;
}

.modalOverlay {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, .4);
    z-index: 1050;
}

.modalContentBox {
    width: min(500px, 95%);
    padding: 1.5rem 2rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-sizing: border-box;
}

/* Only the modal data area scrolls. The title and action buttons stay fixed. */
.modalDataContainer {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: .5rem;
}

.modalDataContainer::-webkit-scrollbar {
    width: 8px;
}

.modalDataContainer::-webkit-scrollbar-track {
    background: #f1f3f5;
    border-radius: 10px;
}

.modalDataContainer::-webkit-scrollbar-thumb {
    background: #c9ced3;
    border-radius: 10px;
}

.modalDataContainer::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

.modalContentBox h3 {
    margin-bottom: .25rem;
    font-size: 1.25rem;
    font-weight: 700;
}

.modalSubText {
    margin-bottom: 1.5rem;
    font-size: .8125rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: .02em;
}

.modalCoachName {
    margin-bottom: 1.5rem;
    font-size: .875rem;
    color: #6c757d;
}

.modalCoachName span {
    color: #495057;
    font-weight: 600;
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
    flex: 0 0 auto;
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e7ebef;
}

/* =========================================================
   Inline radio / checkbox rows (Sex, Courses to assign)
========================================================= */

.formCheckRow {
    display: flex;
    flex-wrap: wrap;
    gap: 1.25rem;
}

.formCheckItem {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .875rem;
    color: #212529;
    cursor: pointer;
}

.formCheckItem input {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: #045199;
}

/* =========================================================
   Authentication Info (Step 3 of the add-coach modals)
========================================================= */

.coachShowPasswordRow {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: -.75rem 0 1.25rem;
    font-size: .8125rem;
    color: #495057;
    cursor: pointer;
}

.coachShowPasswordRow input {
    width: 15px;
    height: 15px;
    cursor: pointer;
    accent-color: #045199;
}

.coachEmailNotice {
    font-size: .8125rem;
    color: #495057;
    margin-bottom: 0;
}

.coachEmailNotice strong {
    color: #212529;
    font-weight: 600;
}

/* =========================================================
   Reusable Dropdown Component — reference only; the real
   implementation lives in our_theme.css alongside
   .registerDropdown. Reused here as-is for the Status field
   so there's still exactly one copy to maintain.
========================================================= */

.registerDropdown {
    position: relative;
}

/* =========================================================
   Responsive
========================================================= */

@media (max-width: 575.98px) {
    .coachesContainer {
        height: 100vh;
        padding: 1rem;
    }

    .modalContentBox {
        padding: 1.25rem 1.25rem;
    }
}
</style>


<!-- =========================================================
     COACHES SECTION
========================================================= -->

<section class="coachesContainer" id="coachesContainer" aria-labelledby="coachesTitle">

    <header class="coachesHeader">
        <div id="coachesHeaderText">
            <h2 class="mb-1" id="coachesTitle">Coaches</h2>
            <p class="text-muted mb-0" id="coachesDescription">
                Manage coaches.
            </p>
        </div>

        <button class="btn btn-primary" id="addCoachButton" type="button">
            <i class="bi bi-plus-lg" id="addCoachIcon"></i>
            Add New Coach
        </button>
    </header>


    <!-- =====================================================
         TOOLBAR
    ====================================================== -->

    <div class="coachesToolbar">
        <div class="coachesSearchContainer">
            <i class="bi bi-search coachesSearchIcon" aria-hidden="true"></i>
            <input class="form-control coachesSearchInput" id="coachesSearch" type="search" placeholder="Search" autocomplete="off" aria-label="Search coaches">
        </div>
    </div>


    <!-- =====================================================
         TABLE
    ====================================================== -->

    <div class="coachesTableContainer" id="coachesTableContainer">
        <table class="table coachesTable" id="coachesTable">
            <thead id="coachesTableHead"></thead>
            <tbody id="coachesTableBody"></tbody>
        </table>
    </div>

</section>


<!-- =========================================================
     ADD COACH MODALS — three independent modals
     (1: Basic info · 2: Contact info · 3: Generated
     Authentication Info). Each one sizes itself naturally
     to its own content instead of sharing one box.
========================================================= -->

<div class="modalOverlay" id="coachAddStep1Modal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachAddStep1Title">

        <h3 id="coachAddStep1Title">Adding new coach</h3>
        <p class="modalSubText">Basic info</p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachAddStep1FirstName">First name *</label>
                <input class="form-control" id="coachAddStep1FirstName" type="text" placeholder="Juan">
            </div>

            <div class="formGroup">
                <label for="coachAddStep1MiddleName">Middle name *</label>
                <input class="form-control" id="coachAddStep1MiddleName" type="text" placeholder="Martin">
            </div>

            <div class="formGroup">
                <label for="coachAddStep1LastName">Last name *</label>
                <input class="form-control" id="coachAddStep1LastName" type="text" placeholder="Dela Cruz">
            </div>

            <div class="formGroup">
                <label for="coachAddStep1Birthdate">Birthdate *</label>
                <input class="form-control" id="coachAddStep1Birthdate" type="text" placeholder="AUG-10-2001">
            </div>

            <div class="formGroup">
                <label>Sex *</label>
                <div class="formCheckRow" id="coachAddStep1SexRow">
                    <label class="formCheckItem">
                        <input type="radio" name="coachAddStep1Sex" id="coachAddStep1SexMale" value="Male">
                        Male
                    </label>
                    <label class="formCheckItem">
                        <input type="radio" name="coachAddStep1Sex" id="coachAddStep1SexFemale" value="Female">
                        Female
                    </label>
                </div>
            </div>

            <div class="formGroup">
                <label>Courses to assign *</label>
                <div class="formCheckRow" id="coachAddStep1CourseRow">
                    <label class="formCheckItem">
                        <input type="checkbox" class="coachAddStep1CourseCheckbox" value="Beginner">
                        Beginner
                    </label>
                    <label class="formCheckItem">
                        <input type="checkbox" class="coachAddStep1CourseCheckbox" value="Intermediate">
                        Intermediate
                    </label>
                    <label class="formCheckItem">
                        <input type="checkbox" class="coachAddStep1CourseCheckbox" value="Advance">
                        Advance
                    </label>
                    <label class="formCheckItem">
                        <input type="checkbox" class="coachAddStep1CourseCheckbox" value="Survival">
                        Survival
                    </label>
                </div>
            </div>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachAddStep1CancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="coachAddStep1NextButton" type="button">Next</button>
        </div>

    </div>
</div>


<div class="modalOverlay" id="coachAddStep2Modal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachAddStep2Title">

        <h3 id="coachAddStep2Title">Adding new coach</h3>
        <p class="modalSubText">Contact info</p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachAddStep2Email">Email address *</label>
                <input class="form-control" id="coachAddStep2Email" type="email" placeholder="juandelacruz@gmail.com">
            </div>

            <div class="formGroup">
                <label for="coachAddStep2Contact">Contact number *</label>
                <input class="form-control" id="coachAddStep2Contact" type="tel" placeholder="(+63) 9XX-XXX-XXXX">
            </div>

            <div class="formGroup">
                <label for="coachAddStep2EmergencyName">Emergency contact name *</label>
                <input class="form-control" id="coachAddStep2EmergencyName" type="text" placeholder="Marco Dela Cruz">
            </div>

            <div class="formGroup">
                <label for="coachAddStep2EmergencyRelationship">Emergency contact relationship *</label>
                <input class="form-control" id="coachAddStep2EmergencyRelationship" type="text" placeholder="Brother">
            </div>

            <div class="formGroup">
                <label for="coachAddStep2EmergencyContact">Emergency contact number *</label>
                <input class="form-control" id="coachAddStep2EmergencyContact" type="tel" placeholder="(+63) 9XX-XXX-XXXX">
            </div>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachAddStep2BackButton" type="button">Back</button>
            <button class="btn btn-primary" id="coachAddStep2NextButton" type="button">Next</button>
        </div>

    </div>
</div>


<div class="modalOverlay" id="coachAddStep3Modal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachAddStep3Title">

        <h3 id="coachAddStep3Title">Adding new coach</h3>
        <p class="modalSubText">Authentication Info</p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachAddStep3Username">Username *</label>
                <input class="form-control" id="coachAddStep3Username" type="text" placeholder="e.g. jdelacruz" autocomplete="off">
            </div>

            <div class="formGroup">
                <label for="coachAddStep3Password">Password *</label>
                <input class="form-control" id="coachAddStep3Password" type="password" placeholder="Enter a password" autocomplete="new-password">
            </div>

            <label class="coachShowPasswordRow" for="coachAddStep3ShowPasswordCheckbox">
                <input type="checkbox" id="coachAddStep3ShowPasswordCheckbox">
                Show Password
            </label>

            <p class="coachEmailNotice">This account details will be emailed to: <strong id="coachAddStep3EmailTarget"></strong></p>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachAddStep3BackButton" type="button">Back</button>
            <button class="btn btn-primary" id="coachAddStep3ConfirmButton" type="button">Confirm</button>
        </div>

    </div>
</div>


<!-- =========================================================
     EDIT COACH MODAL — Status + Courses to assign only
========================================================= -->

<div class="modalOverlay" id="coachEditModal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachEditModalTitle">

        <h3 id="coachEditModalTitle">Editing coach</h3>
        <p class="modalCoachName">Coach name: <span id="coachEditModalName"></span></p>

        <div class="modalDataContainer">

        <div class="formGroup">
            <label for="coachEditStatusToggle">Status *</label>
            <div class="registerDropdown" data-dropdown>
                <button class="registerDropdownToggle" id="coachEditStatusToggle" type="button" aria-expanded="false" aria-controls="coachEditStatusOptions">
                    <span id="coachEditStatusSelected">Active</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="coachEditStatusOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="Active">Active</button>
                    <button class="registerDropdownOption" type="button" data-value="Held">Held</button>
                    <button class="registerDropdownOption" type="button" data-value="Inactive">Inactive</button>
                </div>

                <input id="coachEditStatusInput" name="status" type="hidden" value="Active">
            </div>
        </div>

        <div class="formGroup">
            <label>Courses to assign *</label>
            <div class="formCheckRow" id="coachEditCourseRow">
                <label class="formCheckItem">
                    <input type="checkbox" class="coachEditCourseCheckbox" value="Beginner">
                    Beginner
                </label>
                <label class="formCheckItem">
                    <input type="checkbox" class="coachEditCourseCheckbox" value="Intermediate">
                    Intermediate
                </label>
                <label class="formCheckItem">
                    <input type="checkbox" class="coachEditCourseCheckbox" value="Advance">
                    Advance
                </label>
                <label class="formCheckItem">
                    <input type="checkbox" class="coachEditCourseCheckbox" value="Survival">
                    Survival
                </label>
            </div>
        </div>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachEditModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="coachEditModalUpdateButton" type="button">Update</button>
        </div>

    </div>
</div>


<!-- =========================================================
     CONFIRM / INFO MODAL

     One-button success/validation message (replaces
     window.alert()).
========================================================= -->

<div class="modalOverlay" id="coachConfirmModal" hidden>
    <div class="modalContentBox" style="max-width:420px" role="alertdialog" aria-modal="true" aria-labelledby="coachConfirmModalTitle">

        <h3 id="coachConfirmModalTitle">Success</h3>

        <p class="text-muted" id="coachConfirmModalMessage" style="margin:0 0 0.5rem;font-size:.9rem;line-height:1.5;"></p>

        <div class="modalActions">
            <button class="btn btn-primary" id="coachConfirmModalOkButton" type="button">OK</button>
        </div>

    </div>
</div>


<script>
(function () {
    'use strict';

    /* =========================================================
       IN-MEMORY DATA (no database — everything lives in this
       array for the lifetime of the page). Every coach record
       gets a stable numeric `id` (internal) plus a display-only
       `registeredId` (e.g. LTS-25-0001), matching the reference
       screenshots' numbering.

       COURSE_OPTIONS and STATUS_OPTIONS are the single source of
       truth for course names / status labels — the table cells,
       the add-coach modals, and the edit modal all read from
       these so the values can never drift apart.
    ========================================================= */

    const COURSE_OPTIONS = ['Beginner', 'Intermediate', 'Advance', 'Survival'];
    const STATUS_OPTIONS = ['Active', 'Held', 'Inactive'];

    let nextCoachId = 1000;
    function coachId() {
        return nextCoachId++;
    }

    let nextRegisteredNumber = 1;
    function nextRegisteredId() {
        const value = `LTS-25-${String(nextRegisteredNumber).padStart(4, '0')}`;
        nextRegisteredNumber += 1;
        return value;
    }

    let coaches = [
        {
            id: coachId(), registeredId: nextRegisteredId(),
            firstName: 'Danica', middleName: '--', lastName: '--',
            birthdate: '', sex: '', courses: ['Beginner'],
            email: '', contact: '+63 9XX-XXX-XXXX',
            emergencyName: '', emergencyRelationship: '', emergencyContact: '',
            username: '', password: '',
            status: 'Active'
        },
        {
            id: coachId(), registeredId: nextRegisteredId(),
            firstName: 'Froy', middleName: '--', lastName: '--',
            birthdate: '', sex: '', courses: ['Beginner', 'Intermediate'],
            email: '', contact: '+63 9XX-XXX-XXXX',
            emergencyName: '', emergencyRelationship: '', emergencyContact: '',
            username: '', password: '',
            status: 'Active'
        },
        {
            id: coachId(), registeredId: nextRegisteredId(),
            firstName: 'Royce', middleName: '--', lastName: '--',
            birthdate: '', sex: '', courses: ['Beginner'],
            email: '', contact: '+63 9XX-XXX-XXXX',
            emergencyName: '', emergencyRelationship: '', emergencyContact: '',
            username: '', password: '',
            status: 'Held'
        },
        {
            id: coachId(), registeredId: nextRegisteredId(),
            firstName: 'Won', middleName: '--', lastName: '--',
            birthdate: '', sex: '', courses: ['Beginner'],
            email: '', contact: '+63 9XX-XXX-XXXX',
            emergencyName: '', emergencyRelationship: '', emergencyContact: '',
            username: '', password: '',
            status: 'Inactive'
        }
    ];

    const columns = [
        { label: 'Registered ID', key: 'registeredId' },
        { label: 'Last name', key: 'lastName' },
        { label: 'First name', key: 'firstName' },
        { label: 'Middle name', key: 'middleName' },
        { label: 'Contact', key: 'contact' },
        { label: 'Status', key: 'status' }
    ];

    /* =========================================================
       ELEMENTS
    ========================================================= */

    const elements = {
        search: document.getElementById('coachesSearch'),
        tableHead: document.getElementById('coachesTableHead'),
        tableBody: document.getElementById('coachesTableBody'),

        addCoachButton: document.getElementById('addCoachButton'),

        /* Add modals (one per step) */
        coachAddStep1Modal: document.getElementById('coachAddStep1Modal'),
        coachAddStep1CancelButton: document.getElementById('coachAddStep1CancelButton'),
        coachAddStep1NextButton: document.getElementById('coachAddStep1NextButton'),

        coachAddStep2Modal: document.getElementById('coachAddStep2Modal'),
        coachAddStep2BackButton: document.getElementById('coachAddStep2BackButton'),
        coachAddStep2NextButton: document.getElementById('coachAddStep2NextButton'),

        coachAddStep3Modal: document.getElementById('coachAddStep3Modal'),
        coachAddStep3BackButton: document.getElementById('coachAddStep3BackButton'),
        coachAddStep3ConfirmButton: document.getElementById('coachAddStep3ConfirmButton'),

        coachAddStep1FirstName: document.getElementById('coachAddStep1FirstName'),
        coachAddStep1MiddleName: document.getElementById('coachAddStep1MiddleName'),
        coachAddStep1LastName: document.getElementById('coachAddStep1LastName'),
        coachAddStep1Birthdate: document.getElementById('coachAddStep1Birthdate'),

        coachAddStep2Email: document.getElementById('coachAddStep2Email'),
        coachAddStep2Contact: document.getElementById('coachAddStep2Contact'),
        coachAddStep2EmergencyName: document.getElementById('coachAddStep2EmergencyName'),
        coachAddStep2EmergencyRelationship: document.getElementById('coachAddStep2EmergencyRelationship'),
        coachAddStep2EmergencyContact: document.getElementById('coachAddStep2EmergencyContact'),

        coachAddStep3Username: document.getElementById('coachAddStep3Username'),
        coachAddStep3Password: document.getElementById('coachAddStep3Password'),
        coachAddStep3ShowPasswordCheckbox: document.getElementById('coachAddStep3ShowPasswordCheckbox'),
        coachAddStep3EmailTarget: document.getElementById('coachAddStep3EmailTarget'),

        /* Edit modal */
        coachEditModal: document.getElementById('coachEditModal'),
        coachEditModalName: document.getElementById('coachEditModalName'),
        coachEditModalCancelButton: document.getElementById('coachEditModalCancelButton'),
        coachEditModalUpdateButton: document.getElementById('coachEditModalUpdateButton'),
        coachEditStatusInput: document.getElementById('coachEditStatusInput'),

        coachConfirmModal: document.getElementById('coachConfirmModal'),
        coachConfirmModalMessage: document.getElementById('coachConfirmModalMessage'),
        coachConfirmModalOkButton: document.getElementById('coachConfirmModalOkButton')
    };

    /* =========================================================
       STATE
    ========================================================= */

    let addContext = null;  // non-null while the add-coach flow (any of the 3 modals) is open
    let editContext = null; // { coachId }

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

    function getCoachName(coach) {
        const given = [coach.firstName, coach.middleName && coach.middleName !== '--' ? coach.middleName : '']
            .filter(Boolean)
            .join(' ');

        return coach.lastName && coach.lastName !== '--' ? `${coach.lastName}, ${given}` : given;
    }

    function openModal(modal) {
        modal.hidden = false;
    }

    function closeModal(modal) {
        modal.hidden = true;
    }

    /* =========================================================
       STATUS BADGE
    ========================================================= */

    function renderStatusBadge(status) {
        return createElement('span', {
            className: `statusBadge ${String(status).toLowerCase()}`,
            text: status
        });
    }

    /* =========================================================
       RENDER CELL
    ========================================================= */

    function renderCell(key, coach) {
        if (key === 'status') return renderStatusBadge(coach.status);

        const value = coach[key] ?? '';
        return document.createTextNode(value || '--');
    }

    /* =========================================================
       ROW ACTIONS
    ========================================================= */

    function renderActionCell(coach, rowIndex) {
        const cell = createElement('td', { id: `coachAction${rowIndex}` });

        const editButton = createElement('button', {
            id: `coachEditButton${rowIndex}`,
            className: 'btn btn-outline-secondary btn-sm coachEditButton',
            attributes: { type: 'button', 'aria-label': `Edit ${getCoachName(coach)}`, title: 'Edit coach' }
        });
        editButton.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i>';

        editButton.addEventListener('click', event => {
            event.stopPropagation();
            openEditCoachModal(coach.id);
        });

        cell.append(editButton);
        return cell;
    }

    /* =========================================================
       RENDER TABLE
    ========================================================= */

    function renderTable() {
        const searchTerm = elements.search.value.trim().toLowerCase();

        const rows = coaches.filter(coach => {
            const haystack = [
                coach.registeredId, coach.lastName, coach.firstName, coach.middleName,
                coach.contact, coach.status, ...(coach.courses || [])
            ].join(' ').toLowerCase();

            return haystack.includes(searchTerm);
        });

        elements.tableHead.replaceChildren();
        elements.tableBody.replaceChildren();

        /* HEADER */

        const headerRow = createElement('tr', { id: 'coachesHeaderRow' });

        columns.forEach((col, index) => {
            headerRow.append(createElement('th', {
                id: `coachColumn${index}`,
                text: col.label,
                attributes: { scope: 'col' }
            }));
        });

        headerRow.append(createElement('th', { id: 'coachActionHeader', text: 'Actions', attributes: { scope: 'col' } }));

        elements.tableHead.append(headerRow);

        /* EMPTY STATE */

        if (!rows.length) {
            const emptyRow = createElement('tr');

            emptyRow.append(createElement('td', {
                id: 'coachesEmptyCell',
                className: 'coachesEmptyState',
                text: 'No matching coaches found.',
                attributes: { colSpan: columns.length + 1 }
            }));

            elements.tableBody.append(emptyRow);
            return;
        }

        /* ROWS */

        rows.forEach((coach, rowIndex) => {
            const tableRow = createElement('tr', { id: `coachRow${rowIndex}` });
            tableRow.dataset.id = coach.id;

            columns.forEach((col, cellIndex) => {
                const tableCell = createElement('td', { id: `coachCell${rowIndex}${cellIndex}` });
                tableCell.append(renderCell(col.key, coach));
                tableRow.append(tableCell);
            });

            tableRow.append(renderActionCell(coach, rowIndex));

            elements.tableBody.append(tableRow);
        });
    }

    /* =========================================================
       SEARCH
    ========================================================= */

    elements.search.addEventListener('input', () => {
        renderTable();
    });

    /* =========================================================
       SHARED FORM HELPERS
    ========================================================= */

    function getCheckedValues(selector) {
        return Array.from(document.querySelectorAll(selector)).map(el => el.value);
    }

    function setCheckedValues(selector, values) {
        document.querySelectorAll(selector).forEach(el => {
            el.checked = (values || []).includes(el.value);
        });
    }

    function getSex() {
        const checked = document.querySelector('input[name="coachAddStep1Sex"]:checked');
        return checked ? checked.value : '';
    }

    function setSex(value) {
        document.querySelectorAll('input[name="coachAddStep1Sex"]').forEach(el => {
            el.checked = el.value === value;
        });
    }

    function setDropdownValue(prefix, value) {
        const selectedLabel = document.getElementById(`${prefix}Selected`);
        const input = document.getElementById(`${prefix}Input`);
        const optionsContainer = document.getElementById(`${prefix}Options`);

        if (selectedLabel) selectedLabel.textContent = value;
        if (input) input.value = value;

        if (optionsContainer) {
            optionsContainer.querySelectorAll('.registerDropdownOption').forEach(option => {
                option.classList.toggle('active', option.dataset.value === value);
            });
        }
    }

    /* =========================================================
       PASSWORD VISIBILITY (Step 3 of the add-coach modals)
    ========================================================= */

    // The admin types the username/password directly now, so this just
    // flips the real input type between 'password' and 'text' — no more
    // generating a value or hand-masking characters.
    elements.coachAddStep3ShowPasswordCheckbox.addEventListener('change', () => {
        elements.coachAddStep3Password.type = elements.coachAddStep3ShowPasswordCheckbox.checked
            ? 'text'
            : 'password';
    });

    /* =========================================================
       ADD COACH MODALS — RESET / NAVIGATION
    ========================================================= */

    function resetAddCoachModals() {
        elements.coachAddStep1FirstName.value = '';
        elements.coachAddStep1MiddleName.value = '';
        elements.coachAddStep1LastName.value = '';
        elements.coachAddStep1Birthdate.value = '';
        setSex('');
        setCheckedValues('.coachAddStep1CourseCheckbox', []);

        elements.coachAddStep2Email.value = '';
        elements.coachAddStep2Contact.value = '';
        elements.coachAddStep2EmergencyName.value = '';
        elements.coachAddStep2EmergencyRelationship.value = '';
        elements.coachAddStep2EmergencyContact.value = '';

        elements.coachAddStep3Username.value = '';
        elements.coachAddStep3Password.value = '';
        elements.coachAddStep3Password.type = 'password';
        elements.coachAddStep3EmailTarget.textContent = '';
        elements.coachAddStep3ShowPasswordCheckbox.checked = false;
    }

    function openAddCoachModal() {
        addContext = {};

        resetAddCoachModals();
        openModal(elements.coachAddStep1Modal);
    }

    elements.addCoachButton.addEventListener('click', openAddCoachModal);

    /* --- Step 1: Basic info --- */

    elements.coachAddStep1CancelButton.addEventListener('click', () => {
        addContext = null;
        closeModal(elements.coachAddStep1Modal);
    });

    elements.coachAddStep1NextButton.addEventListener('click', () => {
        if (!addContext) return;

        const firstName = elements.coachAddStep1FirstName.value.trim();
        const middleName = elements.coachAddStep1MiddleName.value.trim();
        const lastName = elements.coachAddStep1LastName.value.trim();
        const birthdate = elements.coachAddStep1Birthdate.value.trim();
        const sex = getSex();
        const courses = getCheckedValues('.coachAddStep1CourseCheckbox');

        if (!firstName || !middleName || !lastName || !birthdate || !sex || !courses.length) {
            openInfoModal('Please complete all required fields (name, birthdate, sex, and at least one course) before continuing.');
            return;
        }

        closeModal(elements.coachAddStep1Modal);
        openModal(elements.coachAddStep2Modal);
    });

    /* --- Step 2: Contact info --- */

    elements.coachAddStep2BackButton.addEventListener('click', () => {
        closeModal(elements.coachAddStep2Modal);
        openModal(elements.coachAddStep1Modal);
    });

    elements.coachAddStep2NextButton.addEventListener('click', () => {
        if (!addContext) return;

        const email = elements.coachAddStep2Email.value.trim();
        const contact = elements.coachAddStep2Contact.value.trim();
        const emergencyName = elements.coachAddStep2EmergencyName.value.trim();
        const emergencyRelationship = elements.coachAddStep2EmergencyRelationship.value.trim();
        const emergencyContact = elements.coachAddStep2EmergencyContact.value.trim();

        if (!email || !contact || !emergencyName || !emergencyRelationship || !emergencyContact) {
            openInfoModal('Please complete all required contact fields before continuing.');
            return;
        }

        elements.coachAddStep3EmailTarget.textContent = email;

        closeModal(elements.coachAddStep2Modal);
        openModal(elements.coachAddStep3Modal);
    });

    /* --- Step 3: Authentication info (admin-entered) --- */

    elements.coachAddStep3BackButton.addEventListener('click', () => {
        closeModal(elements.coachAddStep3Modal);
        openModal(elements.coachAddStep2Modal);
    });

    elements.coachAddStep3ConfirmButton.addEventListener('click', () => {
        if (!addContext) return;

        const username = elements.coachAddStep3Username.value.trim();
        const password = elements.coachAddStep3Password.value;

        if (!username || !password) {
            openInfoModal('Please enter a username and password before continuing.');
            return;
        }

        const payload = {
            firstName: elements.coachAddStep1FirstName.value.trim() || '--',
            middleName: elements.coachAddStep1MiddleName.value.trim() || '--',
            lastName: elements.coachAddStep1LastName.value.trim() || '--',
            birthdate: elements.coachAddStep1Birthdate.value.trim(),
            sex: getSex(),
            courses: getCheckedValues('.coachAddStep1CourseCheckbox'),
            email: elements.coachAddStep2Email.value.trim(),
            contact: elements.coachAddStep2Contact.value.trim(),
            emergencyName: elements.coachAddStep2EmergencyName.value.trim(),
            emergencyRelationship: elements.coachAddStep2EmergencyRelationship.value.trim(),
            emergencyContact: elements.coachAddStep2EmergencyContact.value.trim(),
            username: username,
            password: password,
            status: 'Active'
        };

        coaches.push({
            id: coachId(),
            registeredId: nextRegisteredId(),
            ...payload
        });

        const email = payload.email;
        addContext = null;
        closeModal(elements.coachAddStep3Modal);
        renderTable();

        openInfoModal(`Successfully added coach ${getCoachName(payload)}. Account details were emailed to ${email}.`);
    });

    /* =========================================================
       EDIT MODAL — Status + Courses to assign only
    ========================================================= */


    function openEditCoachModal(id) {
        const coach = coaches.find(c => c.id === id);
        if (!coach) return;

        editContext = { coachId: id };

        elements.coachEditModalName.textContent = getCoachName(coach);
        setDropdownValue('coachEditStatus', coach.status || 'Active');
        setCheckedValues('.coachEditCourseCheckbox', coach.courses || []);

        openModal(elements.coachEditModal);
    }

    elements.coachEditModalCancelButton.addEventListener('click', () => {
        editContext = null;
        closeModal(elements.coachEditModal);
    });

    elements.coachEditModalUpdateButton.addEventListener('click', () => {
        if (!editContext) {
            closeModal(elements.coachEditModal);
            return;
        }

        const courses = getCheckedValues('.coachEditCourseCheckbox');

        if (!courses.length) {
            openInfoModal('Please assign at least one course before continuing.');
            return;
        }

        const coach = coaches.find(c => c.id === editContext.coachId);

        if (coach) {
            coach.status = elements.coachEditStatusInput.value || 'Active';
            coach.courses = courses;
        }

        editContext = null;
        closeModal(elements.coachEditModal);
        renderTable();

        if (coach) openInfoModal(`Successfully updated coach ${getCoachName(coach)}.`);
    });

    /* =========================================================
       CONFIRM / INFO MODAL
    ========================================================= */

    function openInfoModal(message) {
        elements.coachConfirmModalMessage.textContent = message;
        openModal(elements.coachConfirmModal);
    }

    elements.coachConfirmModalOkButton.addEventListener('click', () => {
        closeModal(elements.coachConfirmModal);
    });

    /* =========================================================
       DROPDOWN (Status field — reuses the same
       .registerDropdown behavior as the Registers page)
    ========================================================= */

    function initializeDropdown(dropdown) {
        const toggle = dropdown.querySelector('.registerDropdownToggle');
        const options = dropdown.querySelector('.registerDropdownOptions');
        const selectedLabel = toggle?.querySelector('span');
        const input = dropdown.querySelector('input[type="hidden"]');

        if (!toggle || !options) return;

        toggle.addEventListener('click', event => {
            event.stopPropagation();
            if (options.hidden) {
                openDropdown(dropdown);
            } else {
                closeDropdown(dropdown);
            }
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
        options.style.width = `${rect.width}px`;
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

    document.querySelectorAll('#coachEditModal [data-dropdown]').forEach(initializeDropdown);

    document.addEventListener('click', event => {
        if (!event.target.closest('[data-dropdown]')) {
            closeAllDropdowns();
        }
    });

    window.addEventListener('resize', () => {
        const open = document.querySelector('[data-dropdown].isOpen');
        if (open) positionDropdownOptions(open);
    });

    /* =========================================================
       MODAL DISMISS (backdrop click / Escape)
    ========================================================= */

    const addCoachModals = [elements.coachAddStep1Modal, elements.coachAddStep2Modal, elements.coachAddStep3Modal];

    [...addCoachModals, elements.coachEditModal, elements.coachConfirmModal].forEach(modal => {
        modal.addEventListener('click', event => {
            if (event.target === modal) {
                if (addCoachModals.includes(modal)) addContext = null;
                if (modal === elements.coachEditModal) editContext = null;
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('.modalOverlay:not([hidden])').forEach(modal => {
            if (addCoachModals.includes(modal)) addContext = null;
            if (modal === elements.coachEditModal) editContext = null;
            closeModal(modal);
        });
        closeAllDropdowns();
    });

    /* =========================================================
       INITIALIZE
    ========================================================= */

    renderTable();

})();
</script>