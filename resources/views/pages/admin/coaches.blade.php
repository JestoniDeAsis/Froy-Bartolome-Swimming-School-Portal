@extends('layouts.toast')

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
    /* scrolls when tabs + toolbar + hint + table are taller than the screen (phones) */
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
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

.coachesToolbar {
    flex: 0 0 auto;
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

/* Tabs (same look as Attendance) */
.coachesTabs {
    flex: 0 0 auto;
    margin-bottom: .75rem;
}
.coachesTabs .btn-tab,
.coachesTab { min-width: 150px; }
.coachesTab[hidden],
#addCoachButton[hidden] { display: none !important; }

/* Filter label + control, stacked */
.coachesFilterGroup { display: flex; flex-direction: column; gap: .35rem; }
.coachesFilterGroup[hidden] { display: none !important; }
.coachesFilterLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}
.coachesStatusFilter { --dd-min-width: 170px; }

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

.coachesTableContainer {
    --coach-row-height: 49px;
    --coach-header-height: 49px;
    --coach-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;
    max-height: calc(var(--coach-header-height) + (var(--coach-row-height) * var(--coach-visible-rows)));
    width: 100%;
    overflow: auto;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    box-sizing: border-box;
}

.coachesTableContainer::-webkit-scrollbar,
.modalDataContainer::-webkit-scrollbar { width: 8px; height: 8px; }
.coachesTableContainer::-webkit-scrollbar-track,
.modalDataContainer::-webkit-scrollbar-track { background: #f1f3f5; border-radius: 10px; }
.coachesTableContainer::-webkit-scrollbar-thumb,
.modalDataContainer::-webkit-scrollbar-thumb { background: #c9ced3; border-radius: 10px; }
.coachesTableContainer::-webkit-scrollbar-thumb:hover,
.modalDataContainer::-webkit-scrollbar-thumb:hover { background: #adb5bd; }

.coachesTable {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
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
.coachesTable td { padding: .75rem 1rem; vertical-align: middle; }
.coachesTable tbody tr { transition: background-color .1s ease; }
.coachesTable tbody tr:hover { background: #f8fafb; }

/* Selectable rows (same as Attendance) */
.coachesRow.isSelectable { cursor: pointer; }
.coachesRow.selectedRow,
.coachesRow.selectedRow:hover,
.coachesRow.selectedRow > td { background: #e7f5ff; }
.coachesRow:focus-visible { outline: 2px solid #4dabf7; outline-offset: -2px; }

/* Toolbar buttons (same as Attendance) */
.coachesToolbarActions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-left: auto;
}

.coachesEmptyState {
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}

/* Status badges — Active / On hold / Deactivated */
.statusBadge {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 12px;
    font-size: .75rem;
    font-weight: 600;
}
.statusBadge.active      { background: #e6f7ec; color: #1a7f3c; }
.statusBadge.on-hold  { background: #fff4e0; color: #b26a00; }
.statusBadge.deactivated { background: #fdebec; color: #c22b3a; }

/* Enrollment badges (Students tab) */
.statusBadge.enrolled     { background: #e7f5ff; color: #1971c2; }
.statusBadge.dropped      { background: #f3ecff; color: #6f42c1; }
.statusBadge.graduated,
.statusBadge.not-enrolled { background: #f1f3f5; color: #495057; }

/* Shared modal (backdrop = .modalOverlay in our_theme.css) */

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

/* Only the data area scrolls; title and actions stay fixed. */
.modalDataContainer {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    padding-right: .5rem;
}

.modalContentBox h3 { margin-bottom: .25rem; font-size: 1.25rem; font-weight: 700; }

.modalSubText {
    margin-bottom: 1.5rem;
    font-size: .8125rem;
    font-weight: 600;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: .02em;
}

.modalCoachName { margin-bottom: 1.5rem; font-size: .875rem; color: #6c757d; }
.modalCoachName span { color: #495057; font-weight: 600; }

.formGroup { margin-bottom: 1.25rem; }
.formGroup > label { display: block; margin-bottom: .5rem; font-size: .875rem; font-weight: 600; }
.formHint { margin-top: .4rem; font-size: .75rem; color: #6c757d; }

.modalActions {
    flex: 0 0 auto;
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e7ebef;
}

.formCheckRow { display: flex; flex-wrap: wrap; gap: 1.25rem; }

.formCheckItem {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    font-size: .875rem;
    color: #212529;
    cursor: pointer;
}
.formCheckItem input { width: 16px; height: 16px; cursor: pointer; accent-color: #045199; }

.coachShowPasswordRow {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: -.25rem 0 1.25rem;
    font-size: .8125rem;
    color: #495057;
    cursor: pointer;
}
.coachShowPasswordRow input { width: 15px; height: 15px; cursor: pointer; accent-color: #045199; }

.coachCopyButton { padding-left: .9rem; padding-right: .9rem; }

.coachEmailNotice { font-size: .8125rem; color: #495057; margin-bottom: 0; }
.coachEmailNotice strong { color: #212529; font-weight: 600; }

/* User information popup (look of the Attendance modals) */
.userInfoModal {
    width: 100%;
    max-width: 520px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(16, 24, 40, .18);
    overflow: hidden;
}

.userInfoModalHeader {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e7ebef;
}
.userInfoModalHeader h5 { margin: 0; font-size: 1rem; font-weight: 700; }

.userInfoModalClose {
    border: none;
    background: transparent;
    font-size: 1.25rem;
    line-height: 1;
    color: #667085;
    cursor: pointer;
    padding: 0;
}

.userInfoModalBody {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    padding: 1.25rem;
}

.userInfoModalName { margin: 0 0 1rem; font-size: .875rem; color: #344054; }
.userInfoModalName strong { color: #101828; }

.userInfoModalFooter {
    flex: 0 0 auto;
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    padding: 1rem 1.25rem;
    border-top: 1px solid #e7ebef;
}

.userInfoSection { margin-bottom: 1.25rem; }
.userInfoSection:last-child { margin-bottom: 0; }

.userInfoSectionTitle {
    margin: 0 0 .5rem;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}

.userInfoList {
    display: grid;
    grid-template-columns: 9rem 1fr;
    gap: .4rem 1rem;
    margin: 0;
    font-size: .875rem;
}
.userInfoList dt { color: #6c757d; font-weight: 600; overflow-wrap: anywhere; }
.userInfoList dd { margin: 0; color: #212529; overflow-wrap: anywhere; }
.userInfoList a { font-weight: 600; text-decoration: none; }
.userInfoEmpty { color: #adb5bd; }

/* On phones / short screens the toolbar can eat the whole height, so keep the
   table from being squeezed to nothing (the page scrolls instead). */
@media (max-width: 767.98px), (max-height: 700px) {
    .coachesTableContainer {
        min-height: 260px;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 575.98px) {
    .coachesContainer { padding: 1rem; }
    .coachesHeader {
        flex-direction: column;
        align-items: stretch;
    }
    #addCoachButton {
        width: 100%;
        justify-content: center;
    }
    .coachesTabs {
        display: grid !important;
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: .5rem !important;
        width: 100% !important;
        margin-bottom: 1rem !important;
    }
    .coachesTabs .btn-tab,
    .coachesTab {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        padding: .45rem .4rem !important;
        font-size: .8125rem !important;
    }
    .modalContentBox { padding: 1.25rem; }
    .coachesToolbarActions { margin-left: 0; width: 100%; }
    .coachesToolbarActions button { flex: 1 1 auto; }
    .userInfoList { grid-template-columns: 1fr; gap: 0; }
    .userInfoList dd { margin-bottom: .5rem; }
}
</style>

<section class="coachesContainer" id="coachesContainer" aria-labelledby="coachesTitle">

    <header class="coachesHeader">
        <div>
            <h2 class="mb-1" id="coachesTitle">User Management</h2>
            <p class="text-muted mb-0">Update account statuses and register new coaches.</p>
            <p class="text-muted small mb-0" id="coachesHint"></p>
        </div>

        <button class="btn btn-primary btn-icon-label" id="addCoachButton" type="button">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Add New Coach
        </button>
    </header>

    <!-- TABS -->
    <nav class="btn-tab-group coachesTabs" id="coachesTabs" aria-label="User groups">
        <button class="btn btn-outline-primary btn-tab coachesTab active" type="button" data-users-tab="coaches" aria-selected="true">Coaches</button>
        <button class="btn btn-outline-primary btn-tab coachesTab" type="button" data-users-tab="students" aria-selected="false">Students</button>
        <button class="btn btn-outline-primary btn-tab coachesTab" type="button" data-users-tab="athletes" aria-selected="false">Athletes</button>
        <button class="btn btn-outline-primary btn-tab coachesTab" type="button" data-users-tab="applicants" aria-selected="false">Applicants</button>
    </nav>

    <!-- TOOLBAR -->
    <div class="coachesToolbar">
        <div class="coachesFilterGroup">
            <label class="coachesFilterLabel" id="statusFilterLabel">Status</label>

            <div class="registerDropdown coachesStatusFilter" id="statusFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="statusFilterToggle" type="button" aria-expanded="false" aria-controls="statusFilterOptions">
                    <span id="statusFilterSelected">All Statuses</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="statusFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All Statuses</button>
                </div>

                <input id="statusFilterInput" name="statusFilter" type="hidden" value="">
            </div>
        </div>

        <!-- Second filter: Enrollment (students) / Type (athletes). Hidden on the other tabs. -->
        <div class="coachesFilterGroup" id="groupFilterGroup" hidden>
            <label class="coachesFilterLabel" id="groupFilterLabel" for="groupFilterToggle">Enrollment</label>

            <div class="registerDropdown coachesStatusFilter" id="groupFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="groupFilterToggle" type="button" aria-expanded="false" aria-controls="groupFilterOptions">
                    <span id="groupFilterSelected">All</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>

                <div class="registerDropdownOptions" id="groupFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All</button>
                </div>

                <input id="groupFilterInput" name="groupFilter" type="hidden" value="">
            </div>
        </div>

        <div class="coachesFilterGroup">
            <label class="coachesFilterLabel" for="coachesSearch">Search</label>

            <div class="coachesSearchContainer">
                <i class="bi bi-search coachesSearchIcon" aria-hidden="true"></i>
                <input class="form-control coachesSearchInput" id="coachesSearch" type="search"
                       placeholder="Search" autocomplete="off" aria-label="Search users">
            </div>
        </div>

        <!-- Row actions (select a row first, same as Attendance) -->
        <div class="coachesToolbarActions">
            <button class="btn btn-primary" id="viewInfoButton" type="button" disabled>View Info</button>
            <button class="btn btn-primary" id="editUserButton" type="button" disabled>Edit</button>
        </div>
    </div>

    <div class="coachesTableContainer" id="coachesTableContainer">
        <table class="table coachesTable" id="coachesTable">
            <thead id="coachesTableHead"></thead>
            <tbody id="coachesTableBody"></tbody>
        </table>
    </div>

</section>


<!-- ================= ADD COACH — STEP 1: BASIC INFO ================= -->
<div class="modalOverlay" id="coachAddStep1Modal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachAddStep1Title">

        <h3 id="coachAddStep1Title">Adding new coach</h3>
        <p class="modalSubText">Basic info</p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachAddFirstName">First name *</label>
                <input class="form-control" id="coachAddFirstName" type="text" maxlength="50" placeholder="Juan" data-counter data-name>
            </div>

            <div class="formGroup">
                <label for="coachAddMiddleName">Middle name</label>
                <input class="form-control" id="coachAddMiddleName" type="text" maxlength="50" placeholder="Martin" data-counter data-name>
            </div>

            <div class="formGroup">
                <label for="coachAddLastName">Last name *</label>
                <input class="form-control" id="coachAddLastName" type="text" maxlength="50" placeholder="Dela Cruz" data-counter data-name>
            </div>

            <div class="formGroup">
                <label for="coachAddSuffixToggle">Name suffix</label>
                <div class="registerDropdown ddBlock" id="coachAddSuffixDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" id="coachAddSuffixToggle" type="button" aria-expanded="false" aria-controls="coachAddSuffixOptions">
                        <span>None</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>

                    <div class="registerDropdownOptions" id="coachAddSuffixOptions" hidden>
                        <button class="registerDropdownOption active" type="button" data-value="">None</button>
                        <button class="registerDropdownOption" type="button" data-value="Jr.">Jr.</button>
                        <button class="registerDropdownOption" type="button" data-value="Sr.">Sr.</button>
                        <button class="registerDropdownOption" type="button" data-value="II">II</button>
                        <button class="registerDropdownOption" type="button" data-value="III">III</button>
                        <button class="registerDropdownOption" type="button" data-value="IV">IV</button>
                        <button class="registerDropdownOption" type="button" data-value="V">V</button>
                    </div>

                    <input id="coachAddSuffix" name="name_suffix" type="hidden" value="">
                </div>
            </div>

            <div class="formGroup">
                <label for="coachAddBirthdate">Birthdate *</label>
                <input class="form-control" id="coachAddBirthdate" type="date">
                <div class="formHint">Coaches must be at least 18 years old.</div>
            </div>

            <div class="formGroup">
                <label>Sex *</label>
                <div class="formCheckRow">
                    <label class="formCheckItem">
                        <input type="radio" name="coachAddSex" value="Male"> Male
                    </label>
                    <label class="formCheckItem">
                        <input type="radio" name="coachAddSex" value="Female"> Female
                    </label>
                </div>
            </div>

            <div class="formGroup">
                <label>Courses to assign *</label>
                <div class="formCheckRow" id="coachAddCourseRow"></div>
            </div>

            <div class="formGroup">
                <label>Athletes</label>
                <div class="formCheckRow">
                    <label class="formCheckItem">
                        <input type="checkbox" id="coachAddHandlesAthletes"> This coach also handles athletes
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


<!-- ================= ADD COACH — STEP 2: CONTACT INFO ================= -->
<div class="modalOverlay" id="coachAddStep2Modal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachAddStep2Title">

        <h3 id="coachAddStep2Title">Adding new coach</h3>
        <p class="modalSubText">Contact info</p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachAddEmail">Email address *</label>
                <input class="form-control" id="coachAddEmail" type="email" placeholder="juandelacruz@example.com" maxlength="100" autocomplete="off" data-counter>
            </div>

            <div class="formGroup">
                <label for="coachAddPhone">Mobile number *</label>
                <input class="form-control" id="coachAddPhone" data-counter type="tel" placeholder="09XXXXXXXXX"
                       maxlength="11" inputmode="numeric" autocomplete="off">
            </div>

            <div class="formGroup">
                <label for="coachAddAddress">Address *</label>
                <textarea class="form-control" id="coachAddAddress" rows="2" maxlength="255"
                          placeholder="House no., street, barangay, city, province" data-counter></textarea>
            </div>

            <div class="formGroup">
                <label for="coachAddEmergencyName">Emergency contact name *</label>
                <input class="form-control" id="coachAddEmergencyName" type="text" maxlength="100" placeholder="Marco Dela Cruz" data-counter data-name>
            </div>

            <div class="formGroup">
                <label for="coachAddEmergencyRelationshipToggle">Emergency contact relationship *</label>
                <div class="registerDropdown ddBlock" id="coachAddEmergencyRelationshipDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" id="coachAddEmergencyRelationshipToggle" type="button" aria-expanded="false" aria-controls="coachAddEmergencyRelationshipOptions">
                        <span>Select relationship</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>

                    <div class="registerDropdownOptions" id="coachAddEmergencyRelationshipOptions" hidden>
                        <button class="registerDropdownOption active" type="button" data-value="">Select relationship</button>
                        <button class="registerDropdownOption" type="button" data-value="Mother">Mother</button>
                        <button class="registerDropdownOption" type="button" data-value="Father">Father</button>
                        <button class="registerDropdownOption" type="button" data-value="Wife">Wife</button>
                        <button class="registerDropdownOption" type="button" data-value="Husband">Husband</button>
                        <button class="registerDropdownOption" type="button" data-value="Partner">Partner</button>
                        <button class="registerDropdownOption" type="button" data-value="Sibling">Sibling</button>
                        <button class="registerDropdownOption" type="button" data-value="Grandmother">Grandmother</button>
                        <button class="registerDropdownOption" type="button" data-value="Grandfather">Grandfather</button>
                        <button class="registerDropdownOption" type="button" data-value="Aunt">Aunt</button>
                        <button class="registerDropdownOption" type="button" data-value="Uncle">Uncle</button>
                        <button class="registerDropdownOption" type="button" data-value="Legal Guardian">Legal Guardian</button>
                        <button class="registerDropdownOption" type="button" data-value="Friend">Friend</button>
                        <button class="registerDropdownOption" type="button" data-value="Other">Other</button>
                    </div>

                    <input id="coachAddEmergencyRelationship" name="emergency_relationship" type="hidden" value="">
                </div>
            </div>

            <div class="formGroup">
                <label for="coachAddEmergencyPhone">Emergency contact number *</label>
                <input class="form-control" id="coachAddEmergencyPhone" data-counter type="tel" placeholder="09XXXXXXXXX"
                       maxlength="11" inputmode="numeric" autocomplete="off">
            </div>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachAddStep2BackButton" type="button">Back</button>
            <button class="btn btn-primary" id="coachAddStep2NextButton" type="button">Next</button>
        </div>

    </div>
</div>


<!-- ================= ADD COACH — STEP 3: AUTHENTICATION INFO ================= -->
<div class="modalOverlay" id="coachAddStep3Modal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachAddStep3Title">

        <h3 id="coachAddStep3Title">Adding new coach</h3>
        <p class="modalSubText">Authentication info</p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachAddUsername">Username</label>
                <div class="input-group">
                    <input class="form-control" id="coachAddUsername" type="text" readonly autocomplete="off">
                    <button class="btn btn-outline-secondary coachCopyButton" id="coachAddCopyUsername" type="button"
                            aria-label="Copy username" title="Copy username">
                        <i class="bi bi-clipboard" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="formHint">Generated from the coach's name.</div>
            </div>

            <div class="formGroup">
                <label for="coachAddPassword">Password</label>
                <div class="input-group">
                    <input class="form-control" id="coachAddPassword" type="password" readonly autocomplete="off">
                    <button class="btn btn-outline-secondary coachCopyButton" id="coachAddCopyPassword" type="button"
                            aria-label="Copy password" title="Copy password">
                        <i class="bi bi-clipboard" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="formHint">Randomly generated. Use Show Password if you need to read it out.</div>
            </div>

            <label class="coachShowPasswordRow" for="coachAddShowPassword">
                <input type="checkbox" id="coachAddShowPassword">
                Show Password
            </label>

            <p class="coachEmailNotice">
                The username and password will be emailed to: <strong id="coachAddEmailTarget"></strong>
            </p>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachAddStep3BackButton" type="button">Back</button>
            <button class="btn btn-primary" id="coachAddStep3ConfirmButton" type="button">Confirm</button>
        </div>

    </div>
</div>


<!-- ================= EDIT COACH — STATUS + COURSES ================= -->
<div class="modalOverlay" id="coachEditModal" hidden>
    <div class="modalContentBox" role="dialog" aria-modal="true" aria-labelledby="coachEditModalTitle">

        <h3 id="coachEditModalTitle">Editing user</h3>
        <p class="modalCoachName">Name: <span id="coachEditModalName"></span></p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachEditStatusToggle">Status *</label>
                <div class="registerDropdown ddBlock" id="coachEditStatusDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" id="coachEditStatusToggle" type="button"
                            aria-expanded="false" aria-controls="coachEditStatusOptions">
                        <span id="coachEditStatusSelected">Active</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>

                    <div class="registerDropdownOptions" id="coachEditStatusOptions" hidden>
                        <button class="registerDropdownOption active" type="button" data-value="Active">Active</button>
                    </div>

                    <input id="coachEditStatusInput" name="status" type="hidden" value="Active">
                </div>
                <div class="formHint">Accounts that are On hold or Deactivated cannot log in.</div>
            </div>

            <!-- Coaches only -->
            <div id="coachEditCoachFields">

            <div class="formGroup">
                <label>Courses to assign *</label>
                <div class="formCheckRow" id="coachEditCourseRow"></div>
            </div>

            <div class="formGroup">
                <label>Athletes</label>
                <div class="formCheckRow">
                    <label class="formCheckItem">
                        <input type="checkbox" id="coachEditHandlesAthletes"> This coach also handles athletes
                    </label>
                </div>
            </div>
            </div>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachEditModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="coachEditModalUpdateButton" type="button">Update</button>
        </div>

    </div>
</div>
<!-- ================= USER INFORMATION (view only) ================= -->
<div class="modalOverlay" id="userInfoModalOverlay" hidden>
    <div class="userInfoModal" role="dialog" aria-modal="true" aria-labelledby="userInfoModalTitle">

        <div class="userInfoModalHeader">
            <h5 id="userInfoModalTitle">User Information</h5>
            <button class="userInfoModalClose" id="userInfoModalCloseButton" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="userInfoModalBody">
            <p class="userInfoModalName">Name: <strong id="userInfoModalName"></strong></p>
            <div id="userInfoBody"></div>
        </div>

        <div class="userInfoModalFooter">
            <button class="btn btn-outline-primary" id="userInfoModalDoneButton" type="button">Close</button>
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
        data: '/coaches/data',
        store: '/coaches',
        preview: '/coaches/account-preview',
        update: id => `/coaches/${id}`,
        info: id => `/coaches/${id}/profile`
    };

    const MIN_COACH_AGE = 18;
    const ROLE_COACH = 2;
    const ROLE_ATHLETE = 4;
    const ROLE_FORMER_ATHLETE = 6;
    const USERNAME_RE = /^[A-Za-z0-9_]{3,30}$/;
    const PASSWORD_RE = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
    const PHONE_RE = /^09\d{9}$/;

    // Which add-modal each server field belongs to, so a 422 can send the
    // admin back to the step that needs fixing.
    const FIELD_STEP = {
        first_name: 1, middle_name: 1, last_name: 1, name_suffix: 1,
        birthdate: 1, sex: 1, courses: 1, handles_athletes: 1,
        email_address: 2, phone_number: 2, address: 2, parent_guardian_fullname: 2,
        parent_guardian_relationship: 2, parent_guardian_phone_number: 2,
        username: 3, password: 3
    };

    /* =========================================================
       STATE + ELEMENTS
    ========================================================= */

    let users = [];          // rows of the tab being shown
    let courses = [];
    let statuses = [];       // names from tbl_status
    let tabCounts = {};
    let activeTab = 'coaches';
    let loading = false;
    let loadSeq = 0;
    let statusFilterBuilt = false;
    let editId = null;
    let busy = false;
    let selectedId = null;   // row picked in the table (View Info / Edit act on it)
    let infoSeq = 0;

    const $ = id => document.getElementById(id);

    const el = {
        search: $('coachesSearch'),
        tabs: $('coachesTabs'),
        hint: $('coachesHint'),
        statusFilter: $('statusFilterDropdown'),
        statusFilterInput: $('statusFilterInput'),
        groupFilterGroup: $('groupFilterGroup'),
        groupFilterLabel: $('groupFilterLabel'),
        groupFilter: $('groupFilterDropdown'),
        groupFilterInput: $('groupFilterInput'),
        tableHead: $('coachesTableHead'),
        tableBody: $('coachesTableBody'),
        addButton: $('addCoachButton'),
        viewInfoButton: $('viewInfoButton'),
        editButton: $('editUserButton'),

        infoOverlay: $('userInfoModalOverlay'),
        infoName: $('userInfoModalName'),
        infoBody: $('userInfoBody'),
        infoClose: $('userInfoModalCloseButton'),
        infoDone: $('userInfoModalDoneButton'),

        step1: $('coachAddStep1Modal'),
        step2: $('coachAddStep2Modal'),
        step3: $('coachAddStep3Modal'),
        editModal: $('coachEditModal'),

        firstName: $('coachAddFirstName'),
        middleName: $('coachAddMiddleName'),
        lastName: $('coachAddLastName'),
        suffix: $('coachAddSuffix'),
        birthdate: $('coachAddBirthdate'),
        addCourseRow: $('coachAddCourseRow'),
        addHandlesAthletes: $('coachAddHandlesAthletes'),

        email: $('coachAddEmail'),
        phone: $('coachAddPhone'),
        address: $('coachAddAddress'),
        emergencyName: $('coachAddEmergencyName'),
        emergencyRelationship: $('coachAddEmergencyRelationship'),
        emergencyPhone: $('coachAddEmergencyPhone'),

        username: $('coachAddUsername'),
        password: $('coachAddPassword'),
        showPassword: $('coachAddShowPassword'),
        emailTarget: $('coachAddEmailTarget'),
        confirmButton: $('coachAddStep3ConfirmButton'),
        copyUsername: $('coachAddCopyUsername'),
        copyPassword: $('coachAddCopyPassword'),

        editTitle: $('coachEditModalTitle'),
        editName: $('coachEditModalName'),
        editStatusDropdown: $('coachEditStatusDropdown'),
        editCoachFields: $('coachEditCoachFields'),
        editCourseRow: $('coachEditCourseRow'),
        editHandlesAthletes: $('coachEditHandlesAthletes'),
        editStatusInput: $('coachEditStatusInput'),
        editUpdateButton: $('coachEditModalUpdateButton')
    };

    const TAB_LABELS = { coaches: 'Coaches', students: 'Students', athletes: 'Athletes', applicants: 'Applicants' };

    const nameColumns = [
        { label: 'Username',    value: u => u.username },
        { label: 'Last name',   value: u => u.last_name },
        { label: 'First name',  value: u => u.first_name },
        { label: 'Middle name', value: u => u.middle_name },
        { label: 'Contact',     value: u => u.phone_number }
    ];
    const statusColumn = { label: 'Status', value: u => u.status, badge: true };

    // Columns and wording per tab.
    const TAB_CONFIGS = {
        coaches: {
            singular: 'coach', plural: 'coaches',
            hint: 'Register coaches, assign their courses and change their account status.',
            columns: [
                ...nameColumns,
                { label: 'Assigned Classes', value: u => classesLabel(u) },
                statusColumn
            ]
        },
        students: {
            singular: 'student', plural: 'students',
            hint: 'Change a student\'s account status. Use the Enrollment filter to see dropped students.',
            columns: [
                ...nameColumns,
                { label: 'Course', value: u => u.course_names },
                { label: 'Enrollment', value: u => u.enrollment_status, badge: true },
                statusColumn
            ],
            groupFilter: {
                label: 'Enrollment',
                allLabel: 'All students',
                options: [
                    { value: 'Enrolled', label: 'Enrolled' },
                    { value: 'Dropped', label: 'Dropped' },
                    { value: 'Graduated', label: 'Graduated' }
                ],
                match: (u, value) => u.enrollment_status === value
            }
        },
        athletes: {
            singular: 'athlete', plural: 'athletes',
            hint: 'Change an athlete\'s account status. Use the Type filter to see former (retired) athletes.',
            columns: [...nameColumns, { label: 'Type', value: u => u.role_name }, statusColumn],
            groupFilter: {
                label: 'Type',
                allLabel: 'Athletes and former',
                options: [
                    { value: 'current', label: 'Athletes' },
                    { value: 'former', label: 'Former athletes' }
                ],
                match: (u, value) => value === 'former'
                    ? u.role_id === ROLE_FORMER_ATHLETE
                    : u.role_id === ROLE_ATHLETE
            }
        },
        applicants: {
            singular: 'applicant', plural: 'applicants',
            hint: 'Accounts that registered but are not enrolled yet. Change their account status.',
            columns: [...nameColumns, statusColumn]
        }
    };

    function tabConfig() { return TAB_CONFIGS[activeTab]; }

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

    function openModal(modal) { modal.hidden = false; }
    function closeModal(modal) { modal.hidden = true; }

    function personName(c) {
        const given = [c.first_name, c.middle_name].filter(Boolean).join(' ');
        const base = c.last_name ? `${c.last_name}, ${given}` : given;
        return c.name_suffix ? `${base} ${c.name_suffix}` : base;
    }

    function courseNames(ids) {
        return (ids || [])
            .map(id => courses.find(c => Number(c.id) === Number(id))?.name)
            .filter(Boolean);
    }

    // Same wording as the Attendance page: every course + athletes -> "Handles all",
    // every course -> "All classes", otherwise the course names (+ "Athletes").
    function classesLabel(user) {
        const names = [...new Set(courseNames(user.course_ids))];
        const hasAllCourses = courses.length > 0 && names.length >= courses.length;

        if (hasAllCourses) {
            return user.handles_athletes ? 'Handles all' : 'All classes';
        }

        if (user.handles_athletes) names.push('Athletes');

        return names.join(', ');
    }

    function selectedUser() {
        return selectedId === null ? null : (users.find(u => u.id === selectedId) || null);
    }

    function updateToolbarState() {
        const hasSelection = Boolean(selectedUser());
        el.viewInfoButton.disabled = !hasSelection;
        el.editButton.disabled = !hasSelection;
    }

    function ageFrom(iso) {
        const [y, m, d] = iso.split('-').map(Number);
        const t = new Date();
        let age = t.getFullYear() - y;
        if (t.getMonth() + 1 < m || (t.getMonth() + 1 === m && t.getDate() < d)) age--;
        return age;
    }

    function digitsOnly(input) {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 11);
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
        try { data = await response.json(); } catch (e) { /* non-JSON error page */ }

        return { ok: response.ok, status: response.status, data };
    }

    function toastServerErrors(result, fallback) {
        const errors = result.data.errors;

        if (errors) {
            const messages = [...new Set(Object.values(errors).flat())];
            messages.slice(0, 3).forEach(m => showToast(m, 'danger'));
            return Object.keys(errors);
        }

        showToast(result.data.message || fallback, 'danger');
        return [];
    }

    /* =========================================================
       COURSE CHECKBOXES (built from the database list)
    ========================================================= */

    function renderCourseChecks(container, className, selectedIds = []) {
        container.replaceChildren();

        if (!courses.length) {
            container.append(make('span', { className: 'text-muted', text: 'No courses available.' }));
            return;
        }

        courses.forEach(course => {
            const label = make('label', { className: 'formCheckItem' });
            const box = make('input', { className, attributes: { type: 'checkbox', value: course.id } });
            box.checked = selectedIds.map(Number).includes(Number(course.id));
            label.append(box, document.createTextNode(' ' + course.name));
            container.append(label);
        });
    }

    function checkedCourseIds(className) {
        return Array.from(document.querySelectorAll(`.${className}:checked`)).map(box => Number(box.value));
    }

    /* =========================================================
       TABLE
    ========================================================= */

    function renderTabs() {
        el.tabs.querySelectorAll('[data-users-tab]').forEach(button => {
            const name = button.dataset.usersTab;
            const selected = name === activeTab;
            const count = tabCounts[name];

            button.classList.toggle('active', selected);
            button.setAttribute('aria-selected', String(selected));
            button.textContent = count === undefined ? TAB_LABELS[name] : `${TAB_LABELS[name]} (${count})`;
        });

        el.hint.textContent = tabConfig().hint;

        // Registration is for coaches only.
        el.addButton.hidden = activeTab !== 'coaches';
    }

    function renderTable() {
        const cfg = tabConfig();
        const term = el.search.value.trim().toLowerCase();
        const statusFilter = el.statusFilterInput.value;
        const groupFilter = el.groupFilterInput.value;
        const groupConfig = cfg.groupFilter;

        const rows = users.filter(u => {
            if (statusFilter && u.status !== statusFilter) return false;
            if (groupFilter && groupConfig && !groupConfig.match(u, groupFilter)) return false;

            return [u.username, u.email_address, u.last_name, u.first_name, u.middle_name,
                    u.phone_number, u.status, u.role_name, u.course_names, u.enrollment_status,
                    u.handles_athletes ? 'athletes' : '', classesLabel(u), ...courseNames(u.course_ids)]
                .join(' ').toLowerCase().includes(term);
        });

        // A filtered-out or reloaded row can no longer be selected.
        if (selectedId !== null && !rows.some(u => u.id === selectedId)) {
            selectedId = null;
        }

        updateToolbarState();

        el.tableHead.replaceChildren();
        el.tableBody.replaceChildren();

        const headerRow = make('tr');
        cfg.columns.forEach(col => headerRow.append(make('th', { text: col.label, attributes: { scope: 'col' } })));
        el.tableHead.append(headerRow);

        if (!rows.length) {
            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'coachesEmptyState',
                text: loading && !users.length
                    ? 'Loading...'
                    : (users.length ? `No matching ${cfg.plural} found.` : `No ${cfg.plural} yet.`),
                attributes: { colSpan: cfg.columns.length }
            }));
            el.tableBody.append(emptyRow);
            return;
        }

        rows.forEach(user => {
            const row = make('tr', { className: 'coachesRow isSelectable' });
            row.dataset.id = user.id;
            row.tabIndex = 0;
            row.setAttribute('aria-selected', String(selectedId === user.id));

            if (selectedId === user.id) row.classList.add('selectedRow');

            cfg.columns.forEach(col => {
                const cell = make('td');
                const value = col.value(user);

                if (col.badge) {
                    cell.append(make('span', {
                        className: `statusBadge ${String(value).toLowerCase().replace(/\s+/g, '-')}`,
                        text: value
                    }));
                } else {
                    cell.textContent = value || '--';
                }

                row.append(cell);
            });

            el.tableBody.append(row);
        });
    }

    function selectRow(id) {
        selectedId = id;

        el.tableBody.querySelectorAll('tr[data-id]').forEach(row => {
            const isSelected = Number(row.dataset.id) === id;
            row.classList.toggle('selectedRow', isSelected);
            row.setAttribute('aria-selected', String(isSelected));
        });

        updateToolbarState();
    }

    el.tableBody.addEventListener('click', event => {
        const row = event.target.closest('tr[data-id]');
        if (row && el.tableBody.contains(row)) selectRow(Number(row.dataset.id));
    });

    el.tableBody.addEventListener('keydown', event => {
        if (event.key !== 'Enter' && event.key !== ' ') return;

        const row = event.target.closest('tr[data-id]');
        if (!row) return;

        event.preventDefault();
        selectRow(Number(row.dataset.id));
    });

    el.viewInfoButton.addEventListener('click', () => {
        if (selectedId !== null) openInfoModal(selectedId);
    });

    el.editButton.addEventListener('click', () => {
        if (selectedId !== null) openEditModal(selectedId);
    });

    el.search.addEventListener('input', renderTable);

    // Status names come from tbl_status, so a renamed status shows up here without a code change.
    function buildStatusFilter() {
        if (statusFilterBuilt || !statuses.length) return;

        window.FBSSDropdown.setOptions(
            el.statusFilter,
            [{ value: '', label: 'All Statuses' }].concat(statuses.map(name => ({ value: name, label: name }))),
            el.statusFilterInput.value
        );

        statusFilterBuilt = true;
    }

    // Second filter (Enrollment on Students, Type on Athletes). Rebuilt on every tab switch.
    function setupGroupFilter() {
        const cfg = tabConfig().groupFilter;

        el.groupFilterGroup.hidden = !cfg;
        el.groupFilterInput.value = '';

        if (!cfg) return;

        el.groupFilterLabel.textContent = cfg.label;

        window.FBSSDropdown.setOptions(
            el.groupFilter,
            [{ value: '', label: cfg.allLabel }].concat(cfg.options),
            ''
        );
        window.FBSSDropdown.set(el.groupFilter, '');
    }

    async function loadUsers() {
        const seq = ++loadSeq;
        loading = true;
        renderTable();

        const result = await api(`${URLS.data}?tab=${encodeURIComponent(activeTab)}`);

        // A newer request (tab switch) took over; ignore this answer.
        if (seq !== loadSeq) return;

        loading = false;

        if (!result.ok) {
            renderTable();
            toastServerErrors(result, 'Unable to load users.');
            return;
        }

        users = result.data.users || [];
        courses = result.data.courses || [];
        statuses = result.data.statuses || [];
        tabCounts = result.data.counts || {};

        buildStatusFilter();
        renderTabs();
        renderTable();
    }

    function switchTab(name) {
        if (!TAB_CONFIGS[name] || name === activeTab) return;

        activeTab = name;
        users = [];
        selectedId = null;
        setupGroupFilter();
        updateToolbarState();

        renderTabs();
        loadUsers();
    }

    el.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-users-tab]');
        if (button) switchTab(button.dataset.usersTab);
    });

    el.statusFilter.addEventListener('dropdown:change', renderTable);
    el.groupFilter.addEventListener('dropdown:change', renderTable);

    /* =========================================================
       ADD COACH — STEP NAVIGATION
    ========================================================= */

    function resetAddForm() {
        [el.firstName, el.middleName, el.lastName, el.birthdate, el.email, el.phone, el.address,
         el.emergencyName, el.emergencyPhone, el.username, el.password].forEach(i => { i.value = ''; });

        window.FBSSDropdown.set($('coachAddSuffixDropdown'), '');
        window.FBSSDropdown.set($('coachAddEmergencyRelationshipDropdown'), '');
        el.password.type = 'password';
        el.showPassword.checked = false;
        el.emailTarget.textContent = '';

        document.querySelectorAll('input[name="coachAddSex"]').forEach(r => { r.checked = false; });
        renderCourseChecks(el.addCourseRow, 'coachAddCourseCheckbox');
        el.addHandlesAthletes.checked = false;

        // Newest allowed birthdate = today minus the minimum age.
        const limit = new Date();
        limit.setFullYear(limit.getFullYear() - MIN_COACH_AGE);
        el.birthdate.max = limit.toISOString().slice(0, 10);

        refreshCounters();
    }

    function selectedSex() {
        return document.querySelector('input[name="coachAddSex"]:checked')?.value || '';
    }

    const NAME_PATTERN = /^\p{L}[\p{L} .'’-]*$/u;

    function validateName(input, label, required) {
        const value = input.value.trim();

        if (value === '' && !required) {
            return true;
        }

        if (required && value.length < 2) {
            showToast(`${label} must be at least 2 characters.`, 'danger');
            return false;
        }

        if (!NAME_PATTERN.test(value)) {
            showToast(`${label} can only contain letters, spaces, periods, apostrophes, and hyphens.`, 'danger');
            return false;
        }

        return true;
    }

    // Re-run each counter (used after the form is cleared from code).
    function refreshCounters() {
        document.querySelectorAll('[data-counter]').forEach(input => {
            input.dispatchEvent(new Event('input'));
        });
    }

    function validateStep1() {
        if (!el.firstName.value.trim() || !el.lastName.value.trim()) {
            showToast('First name and last name are required.', 'danger');
            return false;
        }

        if (!validateName(el.firstName, 'First name', true)) return false;
        if (!validateName(el.middleName, 'Middle name', false)) return false;
        if (!validateName(el.lastName, 'Last name', true)) return false;

        if (!el.birthdate.value) {
            showToast('Please enter the birthdate.', 'danger');
            return false;
        }

        if (ageFrom(el.birthdate.value) < MIN_COACH_AGE) {
            showToast(`A coach must be at least ${MIN_COACH_AGE} years old.`, 'danger');
            return false;
        }

        if (!selectedSex()) {
            showToast('Please select the sex.', 'danger');
            return false;
        }

        if (!checkedCourseIds('coachAddCourseCheckbox').length) {
            showToast('Assign at least one course.', 'danger');
            return false;
        }

        return true;
    }

    function validateStep2() {
        if (!el.email.value.trim() || !el.email.checkValidity()) {
            showToast('Please enter a valid email address.', 'danger');
            return false;
        }

        if (!PHONE_RE.test(el.phone.value)) {
            showToast('Please enter a valid 11-digit Philippine mobile number (09XXXXXXXXX).', 'danger');
            return false;
        }

        if (!el.address.value.trim()) {
            showToast('Please enter the address.', 'danger');
            return false;
        }

        if (!el.emergencyName.value.trim()) {
            showToast('Please enter the emergency contact name.', 'danger');
            return false;
        }

        if (!validateName(el.emergencyName, 'Emergency contact name', true)) return false;

        if (!el.emergencyRelationship.value) {
            showToast('Please select the emergency contact relationship.', 'danger');
            return false;
        }

        if (!PHONE_RE.test(el.emergencyPhone.value)) {
            showToast('Please enter a valid emergency contact number (09XXXXXXXXX).', 'danger');
            return false;
        }

        return true;
    }

    function validateStep3() {
        if (!USERNAME_RE.test(el.username.value.trim())) {
            showToast('Username must be 3 to 30 characters and may only contain letters, numbers, and underscores.', 'danger');
            return false;
        }

        if (!PASSWORD_RE.test(el.password.value)) {
            showToast('Password must be at least 8 characters and include an uppercase letter, lowercase letter, number, and special character.', 'danger');
            return false;
        }

        return true;
    }

    el.addButton.addEventListener('click', () => {
        resetAddForm();
        openModal(el.step1);
    });

    $('coachAddStep1CancelButton').addEventListener('click', () => closeModal(el.step1));

    $('coachAddStep1NextButton').addEventListener('click', () => {
        if (!validateStep1()) return;
        closeModal(el.step1);
        openModal(el.step2);
    });

    $('coachAddStep2BackButton').addEventListener('click', () => {
        closeModal(el.step2);
        openModal(el.step1);
    });

    // Step 3's username/password come from the server (it also makes sure
    // the username is free), so fetch them before opening the modal.
    $('coachAddStep2NextButton').addEventListener('click', async () => {
        if (busy || !validateStep2()) return;

        const nextButton = $('coachAddStep2NextButton');
        busy = true;
        nextButton.disabled = true;
        nextButton.textContent = 'Preparing...';

        try {
            const result = await api(URLS.preview, {
                method: 'POST',
                body: {
                    first_name: el.firstName.value.trim(),
                    last_name: el.lastName.value.trim()
                }
            });

            if (!result.ok) {
                toastServerErrors(result, 'Unable to prepare the account details.');
                return;
            }

            el.username.value = result.data.username;
            el.password.value = result.data.password;
            el.password.type = 'password';
            el.showPassword.checked = false;
            el.emailTarget.textContent = el.email.value.trim();

            closeModal(el.step2);
            openModal(el.step3);
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while preparing the account details.', 'danger');
        } finally {
            busy = false;
            nextButton.disabled = false;
            nextButton.textContent = 'Next';
        }
    });

    $('coachAddStep3BackButton').addEventListener('click', () => {
        closeModal(el.step3);
        openModal(el.step2);
    });

    el.showPassword.addEventListener('change', () => {
        el.password.type = el.showPassword.checked ? 'text' : 'password';
    });

    async function copyText(value, label) {
        try {
            await navigator.clipboard.writeText(value);
            showToast(`${label} copied.`, 'success', 2000);
        } catch (error) {
            showToast('Unable to copy automatically. Use Show Password and copy it by hand.', 'warning');
        }
    }

    el.copyUsername.addEventListener('click', () => copyText(el.username.value, 'Username'));
    el.copyPassword.addEventListener('click', () => copyText(el.password.value, 'Password'));

    [el.phone, el.emergencyPhone].forEach(digitsOnly);

    /* =========================================================
       ADD COACH — SUBMIT
    ========================================================= */

    el.confirmButton.addEventListener('click', async () => {
        if (busy || !validateStep3()) return;

        const payload = {
            first_name: el.firstName.value.trim(),
            middle_name: el.middleName.value.trim() || null,
            last_name: el.lastName.value.trim(),
            name_suffix: el.suffix.value,
            birthdate: el.birthdate.value,
            sex: selectedSex(),
            courses: checkedCourseIds('coachAddCourseCheckbox'),
            handles_athletes: el.addHandlesAthletes.checked,
            email_address: el.email.value.trim(),
            phone_number: el.phone.value,
            address: el.address.value.trim(),
            parent_guardian_fullname: el.emergencyName.value.trim(),
            parent_guardian_relationship: el.emergencyRelationship.value,
            parent_guardian_phone_number: el.emergencyPhone.value,
            username: el.username.value.trim(),
            password: el.password.value
        };

        busy = true;
        el.confirmButton.disabled = true;
        el.confirmButton.textContent = 'Saving...';

        try {
            const result = await api(URLS.store, { method: 'POST', body: payload });

            if (result.ok) {
                closeModal(el.step3);
                showToast(`Coach ${payload.first_name} ${payload.last_name} was added.`, 'success');

                if (result.data.mail_sent === false) {
                    showToast('The account was created, but the email could not be sent. Share the login details with the coach directly.', 'warning', 8000);
                }

                await loadUsers();
            } else {
                const fields = toastServerErrors(result, 'Unable to add the coach.');

                // Send the admin back to the earliest step with a problem.
                const steps = fields.map(f => FIELD_STEP[f.split('.')[0]]).filter(Boolean);
                const target = steps.length ? Math.min(...steps) : 3;

                if (target !== 3) {
                    closeModal(el.step3);
                    openModal(target === 1 ? el.step1 : el.step2);
                }
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while adding the coach.', 'danger');
        } finally {
            busy = false;
            el.confirmButton.disabled = false;
            el.confirmButton.textContent = 'Confirm';
        }
    });

    /* =========================================================
       USER INFORMATION POPUP (view only)
       Details are fetched when the popup opens, not with the list.
    ========================================================= */

    function infoSection(title, rows) {
        const section = make('section', { className: 'userInfoSection' });
        section.append(make('h4', { className: 'userInfoSectionTitle', text: title }));

        const list = make('dl', { className: 'userInfoList' });

        rows.forEach(({ label, value, phone }) => {
            list.append(make('dt', { text: label }));

            const dd = make('dd');

            if (!value) {
                dd.append(make('span', { className: 'userInfoEmpty', text: '--' }));
            } else if (phone) {
                // tap-to-call on a phone
                dd.append(make('a', {
                    text: value,
                    attributes: { href: `tel:${String(value).replace(/[^\d+]/g, '')}` }
                }));
            } else {
                dd.textContent = value;
            }

            list.append(dd);
        });

        section.append(list);
        return section;
    }

    function enrollmentText(e) {
        if (e.state === 'Dropped') return `Dropped on ${e.date}${e.reason ? ` (${e.reason})` : ''}`;
        if (e.state === 'Graduated') return `Graduated on ${e.date}`;
        return 'Enrolled';
    }

    function closeInfoModal() {
        infoSeq++;
        closeModal(el.infoOverlay);
    }

    async function openInfoModal(id) {
        const user = users.find(u => u.id === id);
        if (!user) return;

        const seq = ++infoSeq;

        el.infoName.textContent = personName(user);
        el.infoBody.replaceChildren(make('p', { className: 'text-muted mb-0', text: 'Loading...' }));
        openModal(el.infoOverlay);

        const result = await api(URLS.info(id));

        // closed, or another user opened, while this was loading
        if (seq !== infoSeq || el.infoOverlay.hidden) return;

        if (!result.ok) {
            closeInfoModal();
            toastServerErrors(result, 'Unable to load the user information.');
            return;
        }

        const p = result.data.profile;

        const account = [
            { label: 'Username', value: p.username },
            { label: 'Type', value: p.role_name },
            { label: 'Status', value: p.status },
            { label: 'Handled by', value: p.account_handler }
        ];

        // Coaches: classes worded the same way as the table / Attendance page.
        if (user.role_id === ROLE_COACH) {
            account.splice(2, 0, { label: 'Assigned classes', value: classesLabel(user) });
        }

        const sections = [
            infoSection('Personal', [
                { label: 'Birthdate', value: p.birthdate ? `${p.birthdate} (${p.age} yrs old)` : null },
                { label: 'Sex', value: p.sex },
                { label: 'Address', value: p.address }
            ]),
            infoSection('Contact', [
                { label: 'Mobile number', value: p.phone_number, phone: true },
                { label: 'Email', value: p.email_address }
            ]),
            infoSection('Emergency contact', [
                { label: 'Name', value: p.emergency_name },
                { label: 'Relationship', value: p.emergency_relationship },
                { label: 'Mobile number', value: p.emergency_phone, phone: true }
            ])
        ];

        // Students: every enrollment, including dropped ones with their reason.
        if (p.enrollments && p.enrollments.length) {
            sections.push(infoSection('Enrollment', p.enrollments.map(e => ({
                label: e.course,
                value: enrollmentText(e)
            }))));
        }

        // Athletes and former (retired) athletes.
        if (p.athlete) {
            sections.push(infoSection(p.athlete.retired ? 'Former athlete' : 'Athlete', [
                { label: 'Athlete since', value: p.athlete.since },
                ...(p.athlete.retired ? [{ label: 'Retired on', value: p.athlete.until }] : [])
            ]));
        }

        sections.push(infoSection('Account', account));

        el.infoBody.replaceChildren(...sections);
    }

    el.infoClose.addEventListener('click', closeInfoModal);
    el.infoDone.addEventListener('click', closeInfoModal);

    /* =========================================================
       EDIT COACH — STATUS + COURSES
    ========================================================= */

    // Select an option on a shared dropdown (silent: no change event).
    function setDropdownValue(prefix, value) {
        const root = $(`${prefix}Dropdown`);
        if (root) window.FBSSDropdown.set(root, value);
    }

    function openEditModal(id) {
        const user = users.find(u => u.id === id);
        if (!user) return;

        const isCoach = user.role_id === ROLE_COACH;
        const current = user.status || 'Active';

        editId = id;
        el.editTitle.textContent = `Editing ${tabConfig().singular}`;
        el.editName.textContent = personName(user);

        window.FBSSDropdown.setOptions(
            el.editStatusDropdown,
            (statuses.length ? statuses : [current]).map(name => ({ value: name, label: name })),
            current
        );
        setDropdownValue('coachEditStatus', current);

        el.editCoachFields.hidden = !isCoach;

        if (isCoach) {
            renderCourseChecks(el.editCourseRow, 'coachEditCourseCheckbox', user.course_ids);
            el.editHandlesAthletes.checked = Boolean(user.handles_athletes);
        }

        openModal(el.editModal);
    }

    $('coachEditModalCancelButton').addEventListener('click', () => {
        editId = null;
        closeModal(el.editModal);
    });

    el.editUpdateButton.addEventListener('click', async () => {
        if (busy || editId === null) return;

        const user = users.find(u => u.id === editId);
        const isCoach = Boolean(user) && user.role_id === ROLE_COACH;
        const ids = isCoach ? checkedCourseIds('coachEditCourseCheckbox') : [];

        if (isCoach && !ids.length) {
            showToast('Assign at least one course.', 'danger');
            return;
        }

        busy = true;
        el.editUpdateButton.disabled = true;
        el.editUpdateButton.textContent = 'Updating...';

        try {
            const result = await api(URLS.update(editId), {
                method: 'PUT',
                body: isCoach
                    ? {
                        status: el.editStatusInput.value || 'Active',
                        courses: ids,
                        handles_athletes: el.editHandlesAthletes.checked
                    }
                    : { status: el.editStatusInput.value || 'Active' }
            });

            if (result.ok) {
                const label = tabConfig().singular.charAt(0).toUpperCase() + tabConfig().singular.slice(1);
                closeModal(el.editModal);
                editId = null;
                showToast(user ? `${label} ${personName(user)} was updated.` : `${label} was updated.`, 'success');
                await loadUsers();
            } else {
                toastServerErrors(result, 'Unable to update the user.');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while updating the user.', 'danger');
        } finally {
            busy = false;
            el.editUpdateButton.disabled = false;
            el.editUpdateButton.textContent = 'Update';
        }
    });

    /* =========================================================
       DROPDOWNS — open/close, selection and positioning come from
       the shared assets/js/fbss-dropdown.js (data-fbss-dropdown).
       Each dropdown's hidden <input> keeps the old id, so reading
       el.suffix.value / el.editStatusInput.value works unchanged.
    ========================================================= */

    /* =========================================================
       MODAL DISMISS (Escape only; clicking outside the card does nothing)
    ========================================================= */

    const allModals = [el.step1, el.step2, el.step3, el.editModal, el.infoOverlay];

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || busy) return;

        allModals.forEach(modal => {
            if (!modal.hidden) {
                if (modal === el.editModal) editId = null;
                if (modal === el.infoOverlay) infoSeq++;
                closeModal(modal);
            }
        });
        // open dropdowns are closed by the shared script on Escape
    });

    /* =========================================================
       INIT
    ========================================================= */

    /* =========================================================
       CHARACTER COUNTERS + NAME FILTERS
    ========================================================= */

    document.querySelectorAll('[data-counter]').forEach(input => {
        const limit = input.getAttribute('maxlength');

        const wrap = make('div', { className: 'formHint d-flex justify-content-between' });
        const msg = make('span', { className: 'text-danger' });
        const count = make('span', { className: 'ms-auto' });
        wrap.append(msg, count);
        input.after(wrap);

        const update = () => {
            count.textContent = `${input.value.length} / ${limit}`;
            count.classList.toggle('text-danger', input.value.length >= Number(limit));
        };

        input.addEventListener('input', () => {
            // Names: no numbers or symbols, must start with a letter
            if (input.hasAttribute('data-name')) {
                const cleaned = input.value
                    .replace(/[^\p{L} .'’-]/gu, '')
                    .replace(/^[^\p{L}]+/u, '')
                    .replace(/ {2,}/g, ' ');

                if (cleaned !== input.value) {
                    input.value = cleaned;
                    msg.textContent = 'Only letters, spaces, periods, apostrophes, and hyphens are allowed.';
                } else {
                    msg.textContent = '';
                }
            }

            update();
        });

        update();
    });

    setupGroupFilter();
    renderTabs();
    loadUsers();

})();
</script>