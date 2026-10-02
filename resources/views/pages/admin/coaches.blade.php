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
.coachesTable th:last-child,
.coachesTable td:last-child { text-align: center; }
.coachesTable tbody tr:hover { background: #f8fafb; }

.coachesEmptyState {
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}

/* Status badges — Active / Held / Inactive */
.statusBadge {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 12px;
    font-size: .75rem;
    font-weight: 600;
}
.statusBadge.active   { background: #e6f7ec; color: #1a7f3c; }
.statusBadge.held     { background: #fff4e0; color: #b26a00; }
.statusBadge.inactive { background: #fdebec; color: #c22b3a; }

/* Shared modal */
.modalOverlay[hidden] { display: none !important; }

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

@media (max-width: 575.98px) {
    .coachesContainer { padding: 1rem; }
    .modalContentBox { padding: 1.25rem; }
}
</style>

<section class="coachesContainer" id="coachesContainer" aria-labelledby="coachesTitle">

    <header class="coachesHeader">
        <div>
            <h2 class="mb-1" id="coachesTitle">Coaches</h2>
            <p class="text-muted mb-0">Manage coaches.</p>
        </div>

        <button class="btn btn-primary btn-icon-label" id="addCoachButton" type="button">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Add New Coach
        </button>
    </header>

    <div class="coachesToolbar">
        <div class="coachesSearchContainer">
            <i class="bi bi-search coachesSearchIcon" aria-hidden="true"></i>
            <input class="form-control coachesSearchInput" id="coachesSearch" type="search"
                   placeholder="Search" autocomplete="off" aria-label="Search coaches">
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
                <input class="form-control" id="coachAddFirstName" type="text" maxlength="255" placeholder="Juan">
            </div>

            <div class="formGroup">
                <label for="coachAddMiddleName">Middle name</label>
                <input class="form-control" id="coachAddMiddleName" type="text" maxlength="255" placeholder="Martin">
            </div>

            <div class="formGroup">
                <label for="coachAddLastName">Last name *</label>
                <input class="form-control" id="coachAddLastName" type="text" maxlength="255" placeholder="Dela Cruz">
            </div>

            <div class="formGroup">
                <label for="coachAddSuffix">Name suffix</label>
                <select class="form-select" id="coachAddSuffix">
                    <option value="" selected>None</option>
                    <option value="Jr.">Jr.</option>
                    <option value="Sr.">Sr.</option>
                    <option value="II">II</option>
                    <option value="III">III</option>
                    <option value="IV">IV</option>
                    <option value="V">V</option>
                </select>
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
                <input class="form-control" id="coachAddEmail" type="email" placeholder="juandelacruz@example.com" autocomplete="off">
            </div>

            <div class="formGroup">
                <label for="coachAddPhone">Mobile number *</label>
                <input class="form-control" id="coachAddPhone" type="tel" placeholder="09XXXXXXXXX"
                       maxlength="11" inputmode="numeric" autocomplete="off">
            </div>

            <div class="formGroup">
                <label for="coachAddEmergencyName">Emergency contact name *</label>
                <input class="form-control" id="coachAddEmergencyName" type="text" maxlength="255" placeholder="Marco Dela Cruz">
            </div>

            <div class="formGroup">
                <label for="coachAddEmergencyRelationship">Emergency contact relationship *</label>
                <select class="form-select" id="coachAddEmergencyRelationship">
                    <option value="" selected disabled>Select relationship</option>
                    <option value="Mother">Mother</option>
                    <option value="Father">Father</option>
                    <option value="Wife">Wife</option>
                    <option value="Husband">Husband</option>
                    <option value="Partner">Partner</option>
                    <option value="Sibling">Sibling</option>
                    <option value="Grandmother">Grandmother</option>
                    <option value="Grandfather">Grandfather</option>
                    <option value="Aunt">Aunt</option>
                    <option value="Uncle">Uncle</option>
                    <option value="Legal Guardian">Legal Guardian</option>
                    <option value="Friend">Friend</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="formGroup">
                <label for="coachAddEmergencyPhone">Emergency contact number *</label>
                <input class="form-control" id="coachAddEmergencyPhone" type="tel" placeholder="09XXXXXXXXX"
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

        <h3 id="coachEditModalTitle">Editing coach</h3>
        <p class="modalCoachName">Coach name: <span id="coachEditModalName"></span></p>

        <div class="modalDataContainer">

            <div class="formGroup">
                <label for="coachEditStatusToggle">Status *</label>
                <div class="registerDropdown" data-dropdown>
                    <button class="registerDropdownToggle" id="coachEditStatusToggle" type="button"
                            aria-expanded="false" aria-controls="coachEditStatusOptions">
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
                <div class="formCheckRow" id="coachEditCourseRow"></div>
            </div>

        </div>

        <div class="modalActions">
            <button class="btn btn-outline-primary" id="coachEditModalCancelButton" type="button">Cancel</button>
            <button class="btn btn-primary" id="coachEditModalUpdateButton" type="button">Update</button>
        </div>

    </div>
</div>
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
        update: id => `/coaches/${id}`
    };

    const MIN_COACH_AGE = 18;
    const USERNAME_RE = /^[A-Za-z0-9_]{3,30}$/;
    const PASSWORD_RE = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/;
    const PHONE_RE = /^09\d{9}$/;

    // Which add-modal each server field belongs to, so a 422 can send the
    // admin back to the step that needs fixing.
    const FIELD_STEP = {
        first_name: 1, middle_name: 1, last_name: 1, name_suffix: 1,
        birthdate: 1, sex: 1, courses: 1,
        email_address: 2, phone_number: 2, parent_guardian_fullname: 2,
        parent_guardian_relationship: 2, parent_guardian_phone_number: 2,
        username: 3, password: 3
    };

    /* =========================================================
       STATE + ELEMENTS
    ========================================================= */

    let coaches = [];
    let courses = [];
    let editId = null;
    let busy = false;

    const $ = id => document.getElementById(id);

    const el = {
        search: $('coachesSearch'),
        tableHead: $('coachesTableHead'),
        tableBody: $('coachesTableBody'),
        addButton: $('addCoachButton'),

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

        email: $('coachAddEmail'),
        phone: $('coachAddPhone'),
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

        editName: $('coachEditModalName'),
        editCourseRow: $('coachEditCourseRow'),
        editStatusInput: $('coachEditStatusInput'),
        editUpdateButton: $('coachEditModalUpdateButton')
    };

    const columns = [
        { label: 'Username',    value: c => c.username },
        { label: 'Last name',   value: c => c.last_name },
        { label: 'First name',  value: c => c.first_name },
        { label: 'Middle name', value: c => c.middle_name },
        { label: 'Contact',     value: c => c.phone_number },
        { label: 'Courses',     value: c => courseNames(c.course_ids).join(', ') },
        { label: 'Status',      value: c => c.status, badge: true }
    ];

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

    function coachName(c) {
        const given = [c.first_name, c.middle_name].filter(Boolean).join(' ');
        const base = c.last_name ? `${c.last_name}, ${given}` : given;
        return c.name_suffix ? `${base} ${c.name_suffix}` : base;
    }

    function courseNames(ids) {
        return (ids || [])
            .map(id => courses.find(c => Number(c.id) === Number(id))?.name)
            .filter(Boolean);
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

    function renderTable() {
        const term = el.search.value.trim().toLowerCase();

        const rows = coaches.filter(c =>
            [c.username, c.email_address, c.last_name, c.first_name, c.middle_name,
             c.phone_number, c.status, ...courseNames(c.course_ids)]
                .join(' ').toLowerCase().includes(term)
        );

        el.tableHead.replaceChildren();
        el.tableBody.replaceChildren();

        const headerRow = make('tr');
        columns.forEach(col => headerRow.append(make('th', { text: col.label, attributes: { scope: 'col' } })));
        headerRow.append(make('th', { text: 'Actions', attributes: { scope: 'col' } }));
        el.tableHead.append(headerRow);

        if (!rows.length) {
            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'coachesEmptyState',
                text: coaches.length ? 'No matching coaches found.' : 'No coaches yet.',
                attributes: { colSpan: columns.length + 1 }
            }));
            el.tableBody.append(emptyRow);
            return;
        }

        rows.forEach(coach => {
            const row = make('tr');
            row.dataset.id = coach.id;

            columns.forEach(col => {
                const cell = make('td');
                const value = col.value(coach);

                if (col.badge) {
                    cell.append(make('span', {
                        className: `statusBadge ${String(value).toLowerCase()}`,
                        text: value
                    }));
                } else {
                    cell.textContent = value || '--';
                }

                row.append(cell);
            });

            const actionCell = make('td');
            const editButton = make('button', {
                className: 'btn btn-outline-secondary btn-sm',
                attributes: { type: 'button', 'aria-label': `Edit ${coachName(coach)}`, title: 'Edit coach' }
            });
            editButton.append(make('i', { className: 'bi bi-pencil', attributes: { 'aria-hidden': 'true' } }));
            editButton.addEventListener('click', () => openEditModal(coach.id));
            actionCell.append(editButton);
            row.append(actionCell);

            el.tableBody.append(row);
        });
    }

    el.search.addEventListener('input', renderTable);

    async function loadCoaches() {
        const result = await api(URLS.data);

        if (!result.ok) {
            toastServerErrors(result, 'Unable to load coaches.');
            return;
        }

        coaches = result.data.coaches || [];
        courses = result.data.courses || [];
        renderTable();
    }

    /* =========================================================
       ADD COACH — STEP NAVIGATION
    ========================================================= */

    function resetAddForm() {
        [el.firstName, el.middleName, el.lastName, el.birthdate, el.email, el.phone,
         el.emergencyName, el.emergencyPhone, el.username, el.password].forEach(i => { i.value = ''; });

        el.suffix.value = '';
        el.emergencyRelationship.value = '';
        el.password.type = 'password';
        el.showPassword.checked = false;
        el.emailTarget.textContent = '';

        document.querySelectorAll('input[name="coachAddSex"]').forEach(r => { r.checked = false; });
        renderCourseChecks(el.addCourseRow, 'coachAddCourseCheckbox');

        // Newest allowed birthdate = today minus the minimum age.
        const limit = new Date();
        limit.setFullYear(limit.getFullYear() - MIN_COACH_AGE);
        el.birthdate.max = limit.toISOString().slice(0, 10);
    }

    function selectedSex() {
        return document.querySelector('input[name="coachAddSex"]:checked')?.value || '';
    }

    function validateStep1() {
        if (!el.firstName.value.trim() || !el.lastName.value.trim()) {
            showToast('First name and last name are required.', 'danger');
            return false;
        }

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

        if (!el.emergencyName.value.trim()) {
            showToast('Please enter the emergency contact name.', 'danger');
            return false;
        }

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
            email_address: el.email.value.trim(),
            phone_number: el.phone.value,
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

                await loadCoaches();
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
       EDIT COACH — STATUS + COURSES
    ========================================================= */

    function setDropdownValue(prefix, value) {
        const label = $(`${prefix}Selected`);
        const input = $(`${prefix}Input`);
        const options = $(`${prefix}Options`);

        if (label) label.textContent = value;
        if (input) input.value = value;
        if (options) {
            options.querySelectorAll('.registerDropdownOption').forEach(o => {
                o.classList.toggle('active', o.dataset.value === value);
            });
        }
    }

    function openEditModal(id) {
        const coach = coaches.find(c => c.id === id);
        if (!coach) return;

        editId = id;
        el.editName.textContent = coachName(coach);
        setDropdownValue('coachEditStatus', coach.status || 'Active');
        renderCourseChecks(el.editCourseRow, 'coachEditCourseCheckbox', coach.course_ids);

        openModal(el.editModal);
    }

    $('coachEditModalCancelButton').addEventListener('click', () => {
        editId = null;
        closeModal(el.editModal);
    });

    el.editUpdateButton.addEventListener('click', async () => {
        if (busy || editId === null) return;

        const ids = checkedCourseIds('coachEditCourseCheckbox');

        if (!ids.length) {
            showToast('Assign at least one course.', 'danger');
            return;
        }

        busy = true;
        el.editUpdateButton.disabled = true;
        el.editUpdateButton.textContent = 'Updating...';

        try {
            const result = await api(URLS.update(editId), {
                method: 'PUT',
                body: { status: el.editStatusInput.value || 'Active', courses: ids }
            });

            if (result.ok) {
                const coach = coaches.find(c => c.id === editId);
                closeModal(el.editModal);
                editId = null;
                showToast(coach ? `Coach ${coachName(coach)} was updated.` : 'Coach was updated.', 'success');
                await loadCoaches();
            } else {
                toastServerErrors(result, 'Unable to update the coach.');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while updating the coach.', 'danger');
        } finally {
            busy = false;
            el.editUpdateButton.disabled = false;
            el.editUpdateButton.textContent = 'Update';
        }
    });

    /* =========================================================
       DROPDOWN (theme .registerDropdown — needs this small JS)
    ========================================================= */

    function positionDropdownOptions(dropdown) {
        const options = dropdown.querySelector('.registerDropdownOptions');
        const toggle = dropdown.querySelector('.registerDropdownToggle');
        if (!options || !toggle) return;

        const rect = toggle.getBoundingClientRect();
        options.style.top = `${rect.bottom + 8}px`;
        options.style.left = `${rect.left}px`;
        options.style.width = `${rect.width}px`;
    }

    function closeDropdown(dropdown) {
        dropdown.classList.remove('isOpen');
        dropdown.querySelector('.registerDropdownOptions').hidden = true;
        dropdown.querySelector('.registerDropdownToggle').setAttribute('aria-expanded', 'false');
    }

    function closeAllDropdowns(except = null) {
        document.querySelectorAll('[data-dropdown]').forEach(d => {
            if (d !== except) closeDropdown(d);
        });
    }

    function openDropdown(dropdown) {
        closeAllDropdowns(dropdown);
        dropdown.classList.add('isOpen');
        dropdown.querySelector('.registerDropdownOptions').hidden = false;
        dropdown.querySelector('.registerDropdownToggle').setAttribute('aria-expanded', 'true');
        positionDropdownOptions(dropdown);
    }

    function initializeDropdown(dropdown) {
        const toggle = dropdown.querySelector('.registerDropdownToggle');
        const options = dropdown.querySelector('.registerDropdownOptions');
        const label = toggle?.querySelector('span');
        const input = dropdown.querySelector('input[type="hidden"]');
        if (!toggle || !options) return;

        toggle.addEventListener('click', event => {
            event.stopPropagation();
            options.hidden ? openDropdown(dropdown) : closeDropdown(dropdown);
        });

        options.addEventListener('click', event => {
            const option = event.target.closest('.registerDropdownOption');
            if (!option) return;

            if (label) label.textContent = option.dataset.value;
            if (input) input.value = option.dataset.value;
            options.querySelectorAll('.registerDropdownOption').forEach(o => {
                o.classList.toggle('active', o === option);
            });

            closeDropdown(dropdown);
        });
    }

    document.querySelectorAll('#coachEditModal [data-dropdown]').forEach(initializeDropdown);

    document.addEventListener('click', event => {
        if (!event.target.closest('[data-dropdown]')) closeAllDropdowns();
    });

    window.addEventListener('resize', () => {
        const open = document.querySelector('[data-dropdown].isOpen');
        if (open) positionDropdownOptions(open);
    });

    /* =========================================================
       MODAL DISMISS (Escape only; clicking outside the card does nothing)
    ========================================================= */

    const allModals = [el.step1, el.step2, el.step3, el.editModal];

    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || busy) return;

        allModals.forEach(modal => {
            if (!modal.hidden) {
                if (modal === el.editModal) editId = null;
                closeModal(modal);
            }
        });

        closeAllDropdowns();
    });

    /* =========================================================
       INIT
    ========================================================= */

    loadCoaches();

})();
</script>