@extends('layouts.toast')

<style>
/* =========================================================
   Athletes
========================================================= */

.athletesContainer {
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

.athletesHeader {
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

#athletesTabs {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-bottom: 1rem;
}

.athletesTab {
    min-width: 140px;
    flex-shrink: 0;
    transition: background-color .15s ease, color .15s ease, border-color .15s ease;
}

/* =========================================================
   Toolbar
========================================================= */

.athletesToolbar {
    display: flex;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .25rem;
    margin-bottom: 1rem;
}

.athletesSearchContainer {
    position: relative;
    width: min(300px, 100%);
    flex-shrink: 0;
}

.athletesSearchIcon {
    position: absolute;
    top: 50%;
    left: .75rem;
    transform: translateY(-50%);
    color: #6c757d;
    pointer-events: none;
}

.athletesSearchInput {
    height: 40px;
    box-sizing: border-box;
    border-radius: 20px;
    padding-left: 2.25rem;
}

.athletesToolbarActions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-left: auto;
}

/* =========================================================
   Scrollable Table Container
========================================================= */

.athletesTableContainer {
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

.athletesTableContainer.isCollapsing {
    flex: 0 0 0;
    max-height: 0;
    opacity: 0;
    overflow: hidden;
}

.athletesTableContainer::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.athletesTableContainer::-webkit-scrollbar-track {
    background: #f1f3f5;
    border-radius: 10px;
}

.athletesTableContainer::-webkit-scrollbar-thumb {
    background: #c9ced3;
    border-radius: 10px;
}

.athletesTableContainer::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* =========================================================
   Table
========================================================= */

.athletesTable {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.athletesTable thead {
    background: #f8fafb;
}

.athletesTable thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafb;
    color: #667085;
    font-weight: 600;
    border-bottom: 1px solid #e7ebef;
    box-shadow: 0 1px 0 #e7ebef;
}

.athletesTable th {
    color: #667085;
    font-weight: 600;
}

.athletesTable th,
.athletesTable td {
    padding: .75rem 1rem;
    vertical-align: middle;
}

.athletesTable th:last-child,
.athletesTable td:last-child {
    text-align: center;
}
/* =========================================================
   Empty State
========================================================= */

.athletesEmptyState {
    padding: 2rem !important;
    color: #6c757d;
    text-align: center;
}
/* =========================================================
   Modals
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

.modalActions {
    display: flex;
    justify-content: flex-end;
    gap: .75rem;
    margin-top: 1.5rem;
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
   Leaderboard filters — dropdown event selection
========================================================= */

.adFilters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.5rem;
    margin-bottom: 1rem;
}

.adFilterGroup {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.adFilterLabel {
    color: #667085;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.adEventTitle {
    margin: 0 0 0.75rem;
    color: #1f2937;
    font-size: 1.15rem;
    font-weight: 700;
}

.adFilterGroup .registerDropdown { --dd-min-width: 180px; }

#adminLeaderboardTableContainer {
    max-height: calc(
        var(--register-header-height, 49px) +
        (var(--register-row-height, 49px) * var(--register-visible-rows, 8))
    );
}

@media (max-width: 575.98px) {
    .adFilters {
        gap: 1rem;
    }

    .adFilterGroup {
        width: 100%;
    }
}

/* =========================================================
   Leaderboard filters and table
========================================================= */

.adFilters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1.5rem;
    margin-bottom: 1rem;
}

.adFilterGroup {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.adFilterLabel {
    color: #667085;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}

.adEventTitle {
    margin: 0 0 0.75rem;
    color: #1f2937;
    font-size: 1.15rem;
    font-weight: 700;
}


#adminLeaderboardTableContainer {
    max-height: calc(
        var(--register-header-height, 49px) +
        (var(--register-row-height, 49px) * var(--register-visible-rows, 8))
    );
    overflow: auto;
}

#adminLeaderboardTable {
    width: 100%;
    margin: 0;
}

#adminLeaderboardStatus {
    margin-top: 0.75rem;
    font-size: 0.875rem;
}

#leaderboardsPanel[hidden] {
    display: none !important;
}

.adCard {
    padding: 1rem;
    margin-bottom: 1rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

@media (max-width: 575.98px) {
    .adFilters {
        gap: 1rem;
    }

    .adFilterGroup {
        width: 100%;
    }
}

/* =========================================================
   Responsive
========================================================= */

@media (max-width: 767.98px), (max-height: 700px) {
    .athletesTableContainer:not(.isCollapsing) {
        min-height: 260px;
        -webkit-overflow-scrolling: touch;
    }
}

@media (max-width: 767.98px) {
    .athletesHeader {
        display: flex;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: .75rem !important;
    }

    #athletesHeaderText {
        width: 100% !important;
    }

    #addAthleteButton {
        width: 100% !important;
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        flex: 0 0 auto !important;
        margin-top: .25rem !important;
    }
}

@media (max-width: 575.98px) {
    .athletesContainer {
        height: 100vh;
        padding: 1rem;
    }

    #athletesTabs .athletesTab {
        min-width: 125px;
        flex: 1 1 calc(50% - .25rem);
    }

    .registerCourseFilter .registerDropdownToggle {
        max-width: 140px;
    }

    .athletesToolbarActions {
        margin-left: 0;
        width: 100%;
    }

    .athletesToolbarActions button {
        flex: 1 1 auto;
    }

    .modalContentBox {
        padding: 1.25rem 1.25rem;
    }
}

.athletesComingSoon {
    padding: 2rem;
    color: #6c757d;
    text-align: center;
}
</style>

<!-- =========================================================
     ATHLETES SECTION
========================================================= -->

<section class="athletesContainer" id="athletesContainer" aria-labelledby="athletesTitle">

    <header class="athletesHeader">
        <div id="athletesHeaderText">
            <h2 class="mb-1" id="athletesTitle">Athletes</h2>
            <p class="text-muted mb-0" id="athletesDescription">
                Manage athletes and their leaderboards.
            </p>
        </div>

        <button class="btn btn-primary" id="addAthleteButton" type="button">
            <i class="bi bi-plus-lg" id="addAthleteIcon"></i>
            Add New Athlete
        </button>
    </header>


    <!-- TABS -->

    <nav class="btn-tab-group" id="athletesTabs" aria-label="Athlete sections">
        <button class="btn btn-outline-primary btn-tab athletesTab active" type="button" data-athlete-tab="athletes" aria-selected="true">
            Athletes
        </button>
        <button class="btn btn-outline-primary btn-tab athletesTab" type="button" data-athlete-tab="former" aria-selected="false">
            Former Athletes
        </button>
        <button class="btn btn-outline-primary btn-tab athletesTab" type="button" data-athlete-tab="leaderboards" aria-selected="false">
            Leaderboards
        </button>
    </nav>


    <!-- ATHLETES PANEL -->

    <div id="athletesPanel">
        <div class="athletesToolbar">
            <div class="athletesSearchContainer">
                <i class="bi bi-search athletesSearchIcon" aria-hidden="true"></i>
                <input class="form-control athletesSearchInput" id="athletesSearch" type="search" placeholder="Search" autocomplete="off" aria-label="Search athletes">
            </div>
        </div>

        <div class="athletesTableContainer" id="athletesTableContainer">
            <table class="table athletesTable" id="athletesTable">
                <thead id="athletesTableHead"></thead>
                <tbody id="athletesTableBody"></tbody>
            </table>
        </div>
    </div>


    <!-- ADMIN LEADERBOARDS — VIEW ONLY -->
    <div id="leaderboardsPanel" hidden>
        <div class="adFilters">
            <div class="adFilterGroup">
                <span class="adFilterLabel">Stroke</span>

                <div class="registerDropdown" id="adminLeaderboardStrokeDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="adminLeaderboardStrokeOptions" aria-label="Select swimming stroke">
                        <span>Freestyle</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>
                    <div class="registerDropdownOptions" id="adminLeaderboardStrokeOptions" hidden></div>
                    <input id="adminLeaderboardStrokeInput" type="hidden" value="">
                </div>
            </div>

            <div class="adFilterGroup">
                <span class="adFilterLabel">Distance</span>

                <div class="registerDropdown" id="adminLeaderboardDistanceDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="adminLeaderboardDistanceOptions" aria-label="Select swimming distance">
                        <span>50 m</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>
                    <div class="registerDropdownOptions" id="adminLeaderboardDistanceOptions" hidden></div>
                    <input id="adminLeaderboardDistanceInput" type="hidden" value="">
                </div>
            </div>
        </div>

        <article class="adCard">
            <h3 class="adEventTitle" id="adminLeaderboardEvent">
                Select an event
            </h3>

            <div
                class="athletesTableContainer"
                id="adminLeaderboardTableContainer"
            >
                <table
                    class="table athletesTable"
                    id="adminLeaderboardTable"
                >
                    <thead id="adminLeaderboardTableHead"></thead>
                    <tbody id="adminLeaderboardTableBody">
                        <tr>
                            <td colspan="100%" class="text-center">
                                Select a stroke and distance to view the leaderboard.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </article>

        <p
            class="text-muted"
            id="adminLeaderboardStatus"
            role="status"
            aria-live="polite"
        >
            Select an event to view its leaderboard.
        </p>
    </div>

</section>


<!-- =========================================================
     CONFIRM MODAL (Revoke)
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

        <div class="athletesSearchContainer athleteModalSearchContainer">
            <i class="bi bi-search athletesSearchIcon" aria-hidden="true"></i>
            <input class="form-control athletesSearchInput" id="athleteModalSearch" type="search" placeholder="Search" autocomplete="off" aria-label="Search pending registrants">
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
       DATA — from AthleteController::data()
         athletes    people with role 4
         candidates  applicants + graduates (not enrolled now) who can
                     be made athletes; each has source = Applicant|Graduate|Former Athlete
    ========================================================= */

    let athletes = @json($athletes);
    let candidates = @json($candidates);
    let former = @json($former ?? []);

    const selectedAthleteCandidateIds = new Set();

    let activeTab = 'athletes';
    let pendingConfirmAction = null;

    const COLUMNS = [
        { label: 'Last Name', key: 'lastName' },
        { label: 'First Name', key: 'firstName' },
        { label: 'Middle Name', key: 'middleName' },
        { label: 'Email Address', key: 'email' },
        { label: 'Birthdate', key: 'birthdate' },
        { label: 'Age', key: 'age' }
    ];

    const FORMER_COLUMNS = [
        ...COLUMNS,
        { label: 'Athlete Since', key: 'athleteSince' },
        { label: 'Retired On', key: 'retiredOn' }
    ];

    const elements = {
        tabs: document.getElementById('athletesTabs'),
        search: document.getElementById('athletesSearch'),
        athletesPanel: document.getElementById('athletesPanel'),
        leaderboardsPanel: document.getElementById('leaderboardsPanel'),
        tableHead: document.getElementById('athletesTableHead'),
        tableBody: document.getElementById('athletesTableBody'),

        addAthleteButton: document.getElementById('addAthleteButton'),
        athleteModal: document.getElementById('athleteModal'),
        athleteModalSearch: document.getElementById('athleteModalSearch'),
        athleteModalSelectAllCheckbox: document.getElementById('athleteModalSelectAllCheckbox'),
        athleteModalSelectionCount: document.getElementById('athleteModalSelectionCount'),
        athleteModalCandidateList: document.getElementById('athleteModalCandidateList'),
        athleteModalBackButton: document.getElementById('athleteModalBackButton'),
        athleteModalConfirmButton: document.getElementById('athleteModalConfirmButton'),

        confirmModal: document.getElementById('confirmModal'),
        confirmModalTitle: document.getElementById('confirmModalTitle'),
        confirmModalMessage: document.getElementById('confirmModalMessage'),
        confirmModalCancelButton: document.getElementById('confirmModalCancelButton'),
        confirmModalConfirmButton: document.getElementById('confirmModalConfirmButton'),

        leaderboardStrokeDropdown: document.getElementById('adminLeaderboardStrokeDropdown'),
        leaderboardDistanceDropdown: document.getElementById('adminLeaderboardDistanceDropdown'),
        leaderboardEvent: document.getElementById('adminLeaderboardEvent'),
        leaderboardTableHead: document.getElementById('adminLeaderboardTableHead'),
        leaderboardTableBody: document.getElementById('adminLeaderboardTableBody'),
        leaderboardStatus: document.getElementById('adminLeaderboardStatus')
    };

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

    function getPersonName(person) {
        const given = [person.firstName, person.middleName && person.middleName !== '--' ? person.middleName : '']
            .filter(Boolean)
            .join(' ');

        return `${person.lastName}, ${given}`;
    }

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function openModal(modal) {
        modal.hidden = false;
    }

    function closeModal(modal) {
        modal.hidden = true;
    }

    /* =========================================================
       CONFIRM MODAL
    ========================================================= */

    function openConfirmModal({ title, message, confirmLabel = 'Confirm', cancelLabel = 'Cancel', onConfirm }) {
        elements.confirmModalTitle.textContent = title;
        elements.confirmModalMessage.textContent = message;
        elements.confirmModalConfirmButton.textContent = confirmLabel;
        elements.confirmModalCancelButton.textContent = cancelLabel;
        elements.confirmModalConfirmButton.className = 'btn btn-primary';

        pendingConfirmAction = typeof onConfirm === 'function' ? onConfirm : null;

        openModal(elements.confirmModal);
    }

    elements.confirmModalCancelButton.addEventListener('click', () => {
        pendingConfirmAction = null;
        closeModal(elements.confirmModal);
    });

    elements.confirmModalConfirmButton.addEventListener('click', () => {
        const action = pendingConfirmAction;
        pendingConfirmAction = null;
        closeModal(elements.confirmModal);
        if (action) action();
    });

    /* =========================================================
       RENDER ATHLETES TABLE
    ========================================================= */

    function renderAthletes() {
        const isFormerTab = activeTab === 'former';
        const columns = isFormerTab ? FORMER_COLUMNS : COLUMNS;
        const source = isFormerTab ? former : athletes;
        const searchTerm = elements.search.value.trim().toLowerCase();

        const rows = source.filter(person =>
            columns.map(col => person[col.key] ?? '').join(' ').toLowerCase().includes(searchTerm)
        );

        elements.tableHead.replaceChildren();
        elements.tableBody.replaceChildren();

        const headerRow = createElement('tr', { id: 'athletesHeaderRow' });

        columns.forEach((col, index) => {
            headerRow.append(createElement('th', {
                id: `athleteColumn${index}`,
                text: col.label,
                attributes: { scope: 'col' }
            }));
        });

        headerRow.append(createElement('th', {
            id: 'athleteActionHeader',
            text: 'Action',
            attributes: { scope: 'col' }
        }));

        elements.tableHead.append(headerRow);

        if (!rows.length) {
            const emptyRow = createElement('tr');

            emptyRow.append(createElement('td', {
                id: 'athletesEmptyCell',
                className: 'athletesEmptyState',
                text: isFormerTab ? 'No former athletes found.' : 'No matching athletes found.',
                attributes: { colSpan: columns.length + 1 }
            }));

            elements.tableBody.append(emptyRow);
            return;
        }

        rows.forEach((person, rowIndex) => {
            const tableRow = createElement('tr', { id: `athleteRow${rowIndex}` });
            tableRow.dataset.id = person.id;

            columns.forEach((col, colIndex) => {
                tableRow.append(createElement('td', {
                    id: `athleteCell${rowIndex}_${colIndex}`,
                    text: person[col.key] ?? ''
                }));
            });

            const actionCell = createElement('td', { id: `athleteAction${rowIndex}` });

            if (isFormerTab) {
                const restoreButton = createElement('button', {
                    id: `athleteActionButton${rowIndex}Restore`,
                    className: 'btn btn-outline-primary btn-sm',
                    text: 'Restore',
                    attributes: { type: 'button' }
                });

                restoreButton.addEventListener('click', () => {
                    openConfirmModal({
                        title: 'Restore this athlete?',
                        message: `Restore ${getPersonName(person)} as an athlete? Use this if they were retired by mistake. They go back to the Athletes tab and keep their original start date.`,
                        confirmLabel: 'Restore',
                        onConfirm: () => restoreAthlete(person)
                    });
                });

                actionCell.append(restoreButton);
            } else {
                const revokeButton = createElement('button', {
                    id: `athleteActionButton${rowIndex}Revoke`,
                    className: 'btn btn-outline-danger btn-sm me-1',
                    text: 'Revoke',
                    attributes: { type: 'button' }
                });

                revokeButton.addEventListener('click', () => {
                    openConfirmModal({
                        title: 'Revoke athlete status?',
                        message: `Revoke ${getPersonName(person)}'s athlete status? They go back to Applicants, or to Graduates if they graduated before.`,
                        confirmLabel: 'Revoke',
                        onConfirm: () => revokeAthlete(person)
                    });
                });

                const retireButton = createElement('button', {
                    id: `athleteActionButton${rowIndex}Retire`,
                    className: 'btn btn-outline-secondary btn-sm me-1',
                    text: 'Retire',
                    attributes: { type: 'button' }
                });

                retireButton.addEventListener('click', () => {
                    openConfirmModal({
                        title: 'Retire this athlete?',
                        message: `Retire ${getPersonName(person)}? They become a Former Athlete. Their attendance and records are kept, and you can restore them from the Former Athletes tab.`,
                        confirmLabel: 'Retire',
                        onConfirm: () => retireAthlete(person)
                    });
                });

                actionCell.append(retireButton, revokeButton);
            }

            tableRow.append(actionCell);
            elements.tableBody.append(tableRow);
        });
    }

    elements.search.addEventListener('input', renderAthletes);

    /* =========================================================
       TABS — Athletes / Leaderboards
    ========================================================= */

    function setTab(tabName) {
        activeTab = tabName;

        elements.tabs.querySelectorAll('[data-athlete-tab]').forEach(button => {
            const isActive = button.dataset.athleteTab === tabName;
            button.classList.toggle('active', isActive);
            button.setAttribute('aria-selected', String(isActive));
        });

        const showTable = tabName === 'athletes' || tabName === 'former';

        elements.athletesPanel.hidden = !showTable;
        elements.leaderboardsPanel.hidden = tabName !== 'leaderboards';
        elements.addAthleteButton.hidden = tabName !== 'athletes';

        if (showTable) {
            renderAthletes();
        }

        if (tabName === 'leaderboards') {
            loadAdminLeaderboard();
        }
    }

    elements.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-athlete-tab]');
        if (button) setTab(button.dataset.athleteTab);
    });

    /* =========================================================
       SERVER CALLS
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

    // The server sends back the fresh Athletes / Candidates lists.
    function applyLists(data) {
        athletes = data.athletes || [];
        candidates = data.candidates || [];
        former = data.former || [];
    }

    async function revokeAthlete(person) {
        try {
            const result = await sendJson(`{{ url('/athletes') }}/${person.id}/revoke`, 'POST');

            if (result.ok) {
                applyLists(result.data);
                renderAthletes();
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
        return candidates;
    }

    async function retireAthlete(person) {
        try {
            const result = await sendJson(`{{ url('/athletes') }}/${person.id}/retire`, 'POST');

            if (result.ok) {
                applyLists(result.data);
                renderAthletes();
                showToast(`${getPersonName(person)} is now a former athlete.`, 'success');
            } else {
                showToast(firstError(result.data, 'Something went wrong while retiring the athlete.'), 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while retiring the athlete.', 'danger');
        }
    }

    async function restoreAthlete(person) {
        try {
            const result = await sendJson(`{{ url('/athletes') }}/${person.id}/restore`, 'POST');

            if (result.ok) {
                applyLists(result.data);
                renderAthletes();
                showToast(`${getPersonName(person)} is an athlete again.`, 'success');
            } else {
                showToast(firstError(result.data, 'Something went wrong while restoring the athlete.'), 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while restoring the athlete.', 'danger');
        }
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
                    ? 'No matching applicants, graduates or former athletes.'
                    : 'No applicants, graduates or former athletes to add.'
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
            const result = await sendJson('{{ url('/athletes') }}', 'POST', { user_ids: ids });

            if (result.ok) {
                applyLists(result.data);
                selectedAthleteCandidateIds.clear();
                closeAthleteModal();
                renderAthletes();
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


    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('.modalOverlay:not([hidden])').forEach(modal => {
            if (modal === elements.confirmModal) pendingConfirmAction = null;
            closeModal(modal);
        });
    });

    /* =========================================================
       PREVENT PAGE-LEVEL SCROLL / FIT CONTAINER
    ========================================================= */

    document.documentElement.style.height = '100%';
    document.documentElement.style.overflow = 'hidden';
    document.body.style.height = '100%';
    document.body.style.margin = '0';
    document.body.style.overflow = 'hidden';

    const athletesContainerEl = document.getElementById('athletesContainer');

    function fitAthletesContainer() {
        if (!athletesContainerEl) return;

        const top = athletesContainerEl.getBoundingClientRect().top + window.scrollY;
        const available = window.innerHeight - top;

        athletesContainerEl.style.height = `${Math.max(available, 320)}px`;
    }

    window.addEventListener('resize', fitAthletesContainer);
    window.addEventListener('load', fitAthletesContainer);

    /* =========================================================
        ADMIN LEADERBOARD — VIEW ONLY
    ========================================================= */

    const adminLeaderboardStrokes =
        @json(\App\Models\AthleteRecord::STROKES);

    const adminLeaderboardDistances =
        @json(\App\Models\AthleteRecord::DISTANCES);

    let adminLeaderboardSelection = {
        stroke: Object.keys(adminLeaderboardStrokes)[0] ?? null,
        distance: adminLeaderboardDistances[0] ?? null
    };

    let adminLeaderboardRequestSequence = 0;


    /* =========================================================
    FILTER DROPDOWNS
    ========================================================= */

    function renderAdminLeaderboardFilters() {
        window.FBSSDropdown.setOptions(
            elements.leaderboardStrokeDropdown,
            Object.entries(adminLeaderboardStrokes).map(([value, label]) => ({
                value,
                label
            })),
            adminLeaderboardSelection.stroke
        );

        window.FBSSDropdown.setOptions(
            elements.leaderboardDistanceDropdown,
            adminLeaderboardDistances.map(distance => ({
                value: String(distance),
                label: `${distance} m`
            })),
            String(adminLeaderboardSelection.distance)
        );
    }

    elements.leaderboardStrokeDropdown.addEventListener('dropdown:change', () => {
        adminLeaderboardSelection.stroke =
            window.FBSSDropdown.get(elements.leaderboardStrokeDropdown);

        loadAdminLeaderboard();
    });

    elements.leaderboardDistanceDropdown.addEventListener('dropdown:change', () => {
        adminLeaderboardSelection.distance =
            Number(window.FBSSDropdown.get(elements.leaderboardDistanceDropdown));

        loadAdminLeaderboard();
    });


    /* =========================================================
    LEADERBOARD TABLE
    ========================================================= */

    function setAdminLeaderboardMessage(message) {
        elements.leaderboardTableHead.replaceChildren();
        elements.leaderboardTableBody.replaceChildren();

        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 4;
        cell.className = 'athletesEmptyState';
        cell.textContent = message;

        row.appendChild(cell);
        elements.leaderboardTableBody.appendChild(row);
    }


    function renderAdminLeaderboard(entries) {
        const head = elements.leaderboardTableHead;
        const body = elements.leaderboardTableBody;

        head.replaceChildren();
        body.replaceChildren();

        const columns = [
            'Rank',
            'Athlete',
            'Best Time',
            'Record Date'
        ];

        const headerRow = document.createElement('tr');

        columns.forEach(label => {
            const th = document.createElement('th');

            th.scope = 'col';
            th.textContent = label;

            headerRow.appendChild(th);
        });

        head.appendChild(headerRow);

        if (!Array.isArray(entries) || entries.length === 0) {
            setAdminLeaderboardMessage(
                'No records found for this event yet.'
            );
            return;
        }

        entries.forEach(entry => {
            const row = document.createElement('tr');

            const values = [
                entry.rank ?? '—',
                entry.name ?? 'Unknown athlete',
                entry.time ?? '—',
                entry.date ?? '—'
            ];

            values.forEach(value => {
                const cell = document.createElement('td');

                cell.textContent = String(value);
                row.appendChild(cell);
            });

            body.appendChild(row);
        });
    }


    /* =========================================================
    LOAD SELECTED LEADERBOARD
    ========================================================= */

    async function loadAdminLeaderboard() {
        const requestSequence = ++adminLeaderboardRequestSequence;

        const stroke = adminLeaderboardSelection.stroke;
        const distance = adminLeaderboardSelection.distance;

        if (
            stroke === null ||
            stroke === undefined ||
            distance === null ||
            distance === undefined
        ) {
            elements.leaderboardEvent.textContent = 'No event selected';

            setAdminLeaderboardMessage(
                'No stroke or distance is available.'
            );

            elements.leaderboardStatus.textContent =
                'No event is available to display.';

            return;
        }

        const strokeLabel =
            adminLeaderboardStrokes[stroke] ?? String(stroke);

        elements.leaderboardEvent.textContent =
            `${strokeLabel} — ${distance}m`;

        setAdminLeaderboardMessage('Loading leaderboard...');

        elements.leaderboardStatus.textContent =
            'Loading leaderboard...';

        try {
            const url =
                `{{ url('/athletes/leaderboard') }}` +
                `?stroke=${encodeURIComponent(stroke)}` +
                `&distance=${encodeURIComponent(distance)}`;

            const result = await sendJson(url, 'GET');

            // Ignore responses from previous filter selections.
            if (requestSequence !== adminLeaderboardRequestSequence) {
                return;
            }

            if (!result.ok) {
                throw new Error(
                    firstError(
                        result.data,
                        'Unable to load the leaderboard.'
                    )
                );
            }

            const data = result.data;

            // Update the title if the server supplies the event name.
            if (data.event) {
                elements.leaderboardEvent.textContent =
                    typeof data.event === 'string'
                        ? data.event
                        : `${strokeLabel} — ${distance}m`;
            }

            // The endpoint returns { success: true, event, entries }.
            const entries = Array.isArray(data.entries)
                ? data.entries
                : [];

            renderAdminLeaderboard(entries);

            elements.leaderboardStatus.textContent =
                entries.length === 0
                    ? 'No records found for this event yet.'
                    : `${entries.length} leaderboard record(s).`;

        } catch (error) {
            if (requestSequence !== adminLeaderboardRequestSequence) {
                return;
            }

            console.error('Failed to load leaderboard:', error);

            setAdminLeaderboardMessage(
                'Unable to load the leaderboard. Please try again.'
            );

            elements.leaderboardStatus.textContent =
                'Unable to load the leaderboard. Please try again.';
        }
    }

    /* =========================================================
       INITIALIZE
    ========================================================= */

    renderAdminLeaderboardFilters();
    setTab(activeTab);
    renderAthletes();
    fitAthletesContainer();

})();
</script>