@extends('layouts.toast')

<style>
/* Page-specific layout only. Typography, buttons, tabs, inputs and the dropdown
   come from default.css / our_theme.css and fbss-dropdown.js. */

.arPage {
    width: 100%;
    padding: 1.25rem;
    box-sizing: border-box;
}

.arHeader { margin-bottom: 1rem; }

.arTabs { margin-bottom: 1rem; }

.arCard {
    padding: 1.1rem 1.25rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

.arCardTitle {
    margin: 0 0 .85rem;
    color: #667085;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.arFilters { display: flex; flex-wrap: wrap; gap: .75rem 1.5rem; margin-bottom: 1rem; }

.arFilterGroup { display: flex; flex-direction: column; gap: .35rem; }
.arFilterGroup .registerDropdown { --dd-min-width: 180px; }

.arFilterLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}

.arEventTitle { margin: 0 0 .75rem; color: #1f2937; font-size: 1.15rem; font-weight: 700; }

/* ---- Tables ---- */

.arTableWrap { width: 100%; overflow: auto; }

.arTable { width: 100%; margin: 0; white-space: nowrap; border-collapse: separate; border-spacing: 0; }

.arTable th {
    padding: .65rem .9rem;
    background: #f8fafb;
    border-bottom: 1px solid #e7ebef;
    color: #667085;
    font-size: .8125rem;
    font-weight: 600;
    text-align: left;
}

.arTable td { padding: .65rem .9rem; border-bottom: 1px solid #f1f3f5; vertical-align: middle; color: #344054; }

.arTable tbody tr:last-child td { border-bottom: 0; }
.arTable tbody tr:hover { background: #f8fafb; }

.arEmpty { padding: 1.75rem 1rem !important; color: #6c757d; text-align: center; }

.arTime { font-weight: 700; color: #1f2937; font-variant-numeric: tabular-nums; }
.arMuted { color: #98a2b3; }

.arRank { display: inline-flex; align-items: center; gap: .4rem; font-weight: 700; }
.arRank .bi-trophy-fill.gold   { color: #d4a017; }
.arRank .bi-trophy-fill.silver { color: #98a2b3; }
.arRank .bi-trophy-fill.bronze { color: #b87333; }

.arGood { color: var(--fbss-success, #1a7f3c); font-weight: 600; }
.arBad  { color: var(--fbss-danger, #c22b3a); font-weight: 600; }

/* ---- Manage tab ---- */

.arManageGrid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 1rem;
    align-items: start;
    margin-bottom: 1rem;
}

.arForm { display: grid; gap: .85rem; }
.arFormRow { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.arField { display: flex; flex-direction: column; gap: .3rem; min-width: 0; }
.arField label { font-size: .8125rem; font-weight: 600; color: #344054; }
.arField .registerDropdown { --dd-min-width: 100%; }

.arHint { margin: 0; color: #667085; font-size: .78rem; }

.arAthleteDropdown { --dd-min-width: 100%; }

/* ---- Attendance of the chosen athlete ---- */

.arAttendance { display: grid; gap: .6rem; margin-bottom: 1rem; padding: .85rem 1rem; background: #f8fafb; border: 1px solid #e7ebef; border-radius: 10px; }
.arAttendanceStatus { margin: 0; color: #344054; font-size: .875rem; }
.arAttendanceActions { display: flex; flex-wrap: wrap; gap: .5rem; }
.arNotice { margin: 0; padding: .5rem .75rem; border: 1px solid #ffe08a; border-radius: .5rem; background: #fff8e1; color: #8a6100; font-size: .8125rem; }
.arLastUpdated { margin: 0 0 .75rem; color: #98a2b3; font-size: .75rem; }

/* Small time modal (backdrop = shared .modalOverlay) */
.arModal { width: 100%; max-width: 360px; display: flex; flex-direction: column; background: #fff; border-radius: 12px; box-shadow: 0 20px 40px rgba(16, 24, 40, .18); overflow: hidden; }
.arModalHeader { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #e7ebef; }
.arModalHeader h5 { margin: 0; font-size: 1rem; font-weight: 700; }
.arModalClose { border: none; background: transparent; color: #667085; font-size: 1.25rem; line-height: 1; padding: 0; cursor: pointer; }
.arModalBody { display: flex; flex-direction: column; gap: .5rem; padding: 1.25rem; }
.arModalName { margin: 0 0 .25rem; color: #344054; font-size: .875rem; }
.arModalFooter { display: flex; justify-content: flex-end; gap: .75rem; padding: 1rem 1.25rem; border-top: 1px solid #e7ebef; }

@media (max-width: 991.98px) {
    .arManageGrid { grid-template-columns: minmax(0, 1fr); }
}

@media (max-width: 575.98px) {
    .arPage { padding: 1rem; }
    .arFormRow { grid-template-columns: minmax(0, 1fr); }
}
</style>

<section class="arPage" id="athleteRecordsPage" aria-labelledby="arTitle">

    <header class="arHeader">
        <h2 class="mb-1" id="arTitle">Athlete Records</h2>
        <p class="text-muted mb-0">Personal records (PRs) of our athletes in the four main strokes.</p>
    </header>

    <div class="btn-tab-group arTabs" id="arTabs" role="tablist" aria-label="Athlete records sections">
        <button class="btn btn-outline-primary btn-tab active" type="button" role="tab" data-ar-tab="leaderboards" aria-selected="true">Leaderboards</button>
        <button class="btn btn-outline-primary btn-tab" type="button" role="tab" data-ar-tab="manage" aria-selected="false" hidden>Manage Records</button>
    </div>

    {{-- ===== LEADERBOARDS (every coach) ===== --}}
    <p class="arLastUpdated" id="arLastUpdated" aria-hidden="true"></p>

    <div id="arPanelLeaderboards">
        <div class="arFilters">
            <div class="arFilterGroup">
                <span class="arFilterLabel">Stroke</span>
                <div class="registerDropdown" id="arLbStrokeDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="arLbStrokeOptions" aria-label="Stroke">
                        <span>Freestyle</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>
                    <div class="registerDropdownOptions" id="arLbStrokeOptions" hidden></div>
                    <input id="arLbStrokeInput" type="hidden" value="">
                </div>
            </div>
            <div class="arFilterGroup">
                <span class="arFilterLabel">Distance</span>
                <div class="registerDropdown" id="arLbDistanceDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="arLbDistanceOptions" aria-label="Distance">
                        <span>50 m</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>
                    <div class="registerDropdownOptions" id="arLbDistanceOptions" hidden></div>
                    <input id="arLbDistanceInput" type="hidden" value="">
                </div>
            </div>
        </div>

        <div class="arCard">
            <h3 class="arEventTitle" id="arLbEvent">--</h3>
            <div class="arTableWrap">
                <table class="table arTable">
                    <thead>
                        <tr><th>Rank</th><th>Athlete</th><th>Time</th><th>Date Set</th></tr>
                    </thead>
                    <tbody id="arLbBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== MANAGE RECORDS (coaches who handle athletes) ===== --}}
    <div id="arPanelManage" hidden>

        <div class="arManageGrid">

            <article class="arCard">
                <h3 class="arCardTitle">Athlete</h3>

                <div class="registerDropdown arAthleteDropdown mb-3" id="arAthleteDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="arAthleteOptions" aria-label="Choose an athlete">
                        <span>Select an athlete</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>
                    <div class="registerDropdownOptions" id="arAthleteOptions" hidden></div>
                    <input id="arAthleteInput" type="hidden" value="">
                </div>

                <p class="arHint mb-3" id="arAthleteEmpty" hidden>No athletes yet. Only athletes you log in attendance today appear here.</p>

                <!-- Attendance of the chosen athlete -->
                <div class="arAttendance" id="arAttendance" hidden>
                    <p class="arAttendanceStatus" id="arAttendanceStatus"></p>

                    <div class="arAttendanceActions">
                        <button class="btn btn-sm btn-outline-primary" id="arTimeInButton" type="button" disabled>Time In</button>
                        <button class="btn btn-sm btn-outline-primary" id="arTimeOutButton" type="button" disabled>Time Out</button>
                        <button class="btn btn-sm btn-outline-danger" id="arAbsentButton" type="button" disabled>Mark Absent</button>
                        <button class="btn btn-sm btn-outline-danger" id="arRemoveMarkButton" type="button" disabled>Remove Mark</button>
                    </div>

                    <p class="arNotice" id="arAttendanceNotice" role="status" hidden></p>
                </div>

                <div class="arTableWrap">
                    <table class="table arTable">
                        <thead id="arBestsHead"></thead>
                        <tbody id="arBestsBody"></tbody>
                    </table>
                </div>
            </article>

            <article class="arCard">
                <h3 class="arCardTitle">Record a new PR</h3>

                <div class="arForm">
                    <div class="arFormRow">
                        <div class="arField">
                            <label>Stroke</label>
                            <div class="registerDropdown" id="arStrokeDropdown" data-fbss-dropdown>
                                <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="arStrokeOptions">
                                    <span>Freestyle</span>
                                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                                </button>
                                <div class="registerDropdownOptions" id="arStrokeOptions" hidden></div>
                                <input id="arStrokeInput" type="hidden" value="">
                            </div>
                        </div>

                        <div class="arField">
                            <label>Distance</label>
                            <div class="registerDropdown" id="arDistanceDropdown" data-fbss-dropdown>
                                <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="arDistanceOptions">
                                    <span>50 m</span>
                                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                                </button>
                                <div class="registerDropdownOptions" id="arDistanceOptions" hidden></div>
                                <input id="arDistanceInput" type="hidden" value="">
                            </div>
                        </div>
                    </div>

                    <div class="arFormRow">
                        <div class="arField">
                            <label for="arTimeInput">Time</label>
                            <input class="form-control" id="arTimeInput" type="text" maxlength="8" placeholder="31.25 or 1:05.30" autocomplete="off">
                        </div>

                        <div class="arField">
                            <label for="arDateInput">Date</label>
                            <input class="form-control" id="arDateInput" type="date">
                        </div>
                    </div>

                    <div class="arField">
                        <label for="arNotesInput">Notes (optional)</label>
                        <input class="form-control" id="arNotesInput" type="text" maxlength="255" placeholder="e.g. Inter-club meet" autocomplete="off">
                    </div>

                    <p class="arHint">A new record must be faster than the athlete's current record for that stroke and distance.</p>

                    <div>
                        <button class="btn btn-primary" id="arSaveButton" type="button" disabled>Save Record</button>
                    </div>
                </div>
            </article>

        </div>

        <article class="arCard">
            <h3 class="arCardTitle">History</h3>
            <div class="arTableWrap">
                <table class="table arTable">
                    <thead>
                        <tr><th>Date</th><th>Event</th><th>Time</th><th>Change</th><th>Notes</th><th>Recorded By</th><th>Remove</th></tr>
                    </thead>
                    <tbody id="arHistoryBody"></tbody>
                </table>
            </div>
        </article>

    </div>

</section>

<!-- TIME IN / TIME OUT MODAL (athlete) -->
<div class="modalOverlay" id="arClockOverlay" hidden>
    <div class="arModal" role="dialog" aria-modal="true" aria-labelledby="arClockTitle">

        <div class="arModalHeader">
            <h5 id="arClockTitle">Record Time Out</h5>
            <button class="arModalClose" id="arClockClose" type="button" aria-label="Close">&times;</button>
        </div>

        <div class="arModalBody">
            <p class="arModalName" id="arClockName"></p>
            <label class="arFilterLabel" for="arClockInput">Current Time</label>
            <input class="form-control" id="arClockInput" type="time">
        </div>

        <div class="arModalFooter">
            <button class="btn btn-outline-primary" id="arClockCancel" type="button">Cancel</button>
            <button class="btn btn-primary" id="arClockSave" type="button">Save</button>
        </div>

    </div>
</div>

<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    const URLS = {
        data: '/coach/athlete-records/data',
        leaderboard: '/coach/athlete-records/leaderboard',
        athlete: id => `/coach/athlete-records/athletes/${id}`,
        store: '/coach/athlete-records',
        remove: id => `/coach/athlete-records/${id}`,

        // Athlete attendance: the same endpoints the Attendance page uses (they accept athlete_id).
        timeIn: '/coach/attendance/students/time-in',
        timeOut: '/coach/attendance/students/time-out',
        expecting: '/coach/attendance/students/expecting',
        absent: '/coach/attendance/students/absent'
    };

    const REFRESH_MS = 10000;   // auto-refresh every 10 seconds

    // The stored attendance_state stays 'Expecting'; this is only the wording people read.
    const EXPECTING_LABEL = 'Expected';

    const $ = id => document.getElementById(id);

    const el = {
        tabs: $('arTabs'),
        panelLeaderboards: $('arPanelLeaderboards'),
        panelManage: $('arPanelManage'),

        lbStrokeDropdown: $('arLbStrokeDropdown'),
        lbDistanceDropdown: $('arLbDistanceDropdown'),
        lbEvent: $('arLbEvent'),
        lbBody: $('arLbBody'),

        athleteDropdown: $('arAthleteDropdown'),
        bestsHead: $('arBestsHead'),
        bestsBody: $('arBestsBody'),

        strokeDropdown: $('arStrokeDropdown'),
        distanceDropdown: $('arDistanceDropdown'),
        time: $('arTimeInput'),
        date: $('arDateInput'),
        notes: $('arNotesInput'),
        save: $('arSaveButton'),

        historyBody: $('arHistoryBody'),

        lastUpdated: $('arLastUpdated'),
        athleteEmpty: $('arAthleteEmpty'),
        attendance: $('arAttendance'),
        attStatus: $('arAttendanceStatus'),
        attNotice: $('arAttendanceNotice'),
        timeInButton: $('arTimeInButton'),
        timeOutButton: $('arTimeOutButton'),
        absentButton: $('arAbsentButton'),
        removeMarkButton: $('arRemoveMarkButton'),

        clockOverlay: $('arClockOverlay'),
        clockTitle: $('arClockTitle'),
        clockName: $('arClockName'),
        clockInput: $('arClockInput'),
        clockClose: $('arClockClose'),
        clockCancel: $('arClockCancel'),
        clockSave: $('arClockSave')
    };

    let strokes = [];
    let distances = [];
    let canManage = false;

    let lb = { stroke: null, distance: null };
    let lbSeq = 0;
    let lbStale = false;     // a record changed, reload the leaderboard when it is shown again

    let athleteId = null;
    let payload = null;      // { athlete, bests, history } of the chosen athlete
    let busy = false;

    /* ---------- helpers ---------- */

    function make(tag, { className, text, attributes } = {}) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        if (attributes) Object.entries(attributes).forEach(([k, v]) => node.setAttribute(k, v));
        return node;
    }

    function toLocalISODate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function dateLabel(iso) {
        if (!iso) return '';
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function formatTimeLabel(value) {
        if (!value) return '';
        const [h, m] = value.split(':').map(Number);
        const ref = new Date();
        ref.setHours(h, m, 0, 0);
        return ref.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    }

    async function api(url, { method = 'GET', body } = {}) {
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' };
        if (body !== undefined) headers['Content-Type'] = 'application/json';

        const response = await fetch(url, { method, headers, body: body !== undefined ? JSON.stringify(body) : undefined });

        let data = {};
        try { data = await response.json(); } catch (e) { /* non-JSON error page */ }

        return { ok: response.ok, status: response.status, data };
    }

    function errorText(result, fallback) {
        const errors = result.data.errors;
        const first = errors ? Object.values(errors).flat()[0] : null;
        return first || result.data.message || `${fallback} (error ${result.status})`;
    }

    const dropdownValue = root => window.FBSSDropdown.get(root);

    function emptyRow(body, columns, text) {
        const tr = make('tr');
        tr.append(make('td', { className: 'arEmpty', text, attributes: { colSpan: columns } }));
        body.replaceChildren(tr);
    }

    /* ---------- tabs ---------- */

    function showTab(name) {
        el.tabs.querySelectorAll('[data-ar-tab]').forEach(button => {
            const active = button.dataset.arTab === name;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
        });

        el.panelLeaderboards.hidden = name !== 'leaderboards';
        el.panelManage.hidden = name !== 'manage';

        if (name === 'leaderboards' && lbStale) {
            lbStale = false;
            loadLeaderboard();
        }
    }

    el.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-ar-tab]');
        if (button) showTab(button.dataset.arTab);
    });

    /* ---------- leaderboards ---------- */

    function setupLbFilters() {
        window.FBSSDropdown.setOptions(
            el.lbStrokeDropdown,
            strokes.map(s => ({ value: s.key, label: s.label })),
            lb.stroke
        );

        window.FBSSDropdown.setOptions(
            el.lbDistanceDropdown,
            distances.map(d => ({ value: String(d), label: `${d} m` })),
            String(lb.distance)
        );
    }

    el.lbStrokeDropdown.addEventListener('dropdown:change', () => {
        lb.stroke = window.FBSSDropdown.get(el.lbStrokeDropdown);
        loadLeaderboard();
    });

    el.lbDistanceDropdown.addEventListener('dropdown:change', () => {
        lb.distance = Number(window.FBSSDropdown.get(el.lbDistanceDropdown));
        loadLeaderboard();
    });

    function rankCell(rank) {
        const wrapper = make('span', { className: 'arRank' });

        if (rank <= 3) {
            wrapper.append(make('i', {
                className: `bi bi-trophy-fill ${['gold', 'silver', 'bronze'][rank - 1]}`,
                attributes: { 'aria-hidden': 'true' }
            }));
        }

        wrapper.append(document.createTextNode(String(rank)));
        return wrapper;
    }

    function renderLeaderboard(entries) {
        if (!entries.length) {
            emptyRow(el.lbBody, 4, 'No records have been set for this event yet.');
            return;
        }

        el.lbBody.replaceChildren();

        entries.forEach(entry => {
            const tr = make('tr');

            const rank = make('td');
            rank.append(rankCell(entry.rank));

            const time = make('td');
            time.append(make('span', { className: 'arTime', text: entry.time }));

            tr.append(rank, make('td', { text: entry.name }), time, make('td', { text: dateLabel(entry.date) }));
            el.lbBody.append(tr);
        });
    }

    async function loadLeaderboard({ silent = false } = {}) {
        const seq = ++lbSeq;

        if (!silent) emptyRow(el.lbBody, 4, 'Loading...');

        const params = new URLSearchParams({ stroke: lb.stroke, distance: String(lb.distance) });

        try {
            const result = await api(`${URLS.leaderboard}?${params}`);
            if (seq !== lbSeq) return;

            if (!result.ok) {
                if (!silent) {
                    emptyRow(el.lbBody, 4, 'Unable to load the leaderboard.');
                    showToast(errorText(result, 'Unable to load the leaderboard.'), 'danger');
                }
                return;
            }

            el.lbEvent.textContent = result.data.event;
            renderLeaderboard(result.data.entries || []);
        } catch (error) {
            if (seq !== lbSeq) return;
            console.error(error);

            if (!silent) {
                emptyRow(el.lbBody, 4, 'Unable to load the leaderboard.');
                showToast('Something went wrong while loading the leaderboard.', 'danger');
            }
        }
    }

    /* ---------- manage records ---------- */

    function renderBests() {
        el.bestsHead.replaceChildren();
        el.bestsBody.replaceChildren();

        const headRow = make('tr');
        headRow.append(make('th', { text: 'Current records' }));
        distances.forEach(d => headRow.append(make('th', { text: `${d} m` })));
        el.bestsHead.append(headRow);

        strokes.forEach(stroke => {
            const tr = make('tr');
            tr.append(make('td', { text: stroke.label }));

            distances.forEach(distance => {
                const best = payload && payload.bests ? payload.bests[`${stroke.key}-${distance}`] : null;
                const td = make('td');

                if (best) {
                    td.append(make('span', { className: 'arTime', text: best.time, attributes: { title: dateLabel(best.date) } }));
                } else {
                    td.append(make('span', { className: 'arMuted', text: '--' }));
                }

                tr.append(td);
            });

            el.bestsBody.append(tr);
        });
    }

    function renderHistory() {
        const list = payload ? payload.history : [];

        if (!athleteId) {
            emptyRow(el.historyBody, 7, 'Choose an athlete to see their history.');
            return;
        }

        if (!list.length) {
            emptyRow(el.historyBody, 7, 'No records have been set for this athlete yet.');
            return;
        }

        el.historyBody.replaceChildren();

        list.forEach(record => {
            const tr = make('tr');

            const time = make('td');
            time.append(make('span', { className: 'arTime', text: record.time }));

            const change = make('td');
            if (record.improvement) {
                change.append(make('span', { className: record.improvement.startsWith('-') ? 'arGood' : 'arBad', text: record.improvement }));
            } else {
                change.append(make('span', { className: 'arMuted', text: 'First record' }));
            }

            const action = make('td');
            const remove = make('button', {
                className: 'btn btn-icon btn-icon-square btn-outline-danger btn-icon-sm',
                attributes: { type: 'button', 'aria-label': `Remove ${record.event} ${record.time}`, title: 'Remove this record' }
            });
            remove.append(make('i', { className: 'bi bi-trash', attributes: { 'aria-hidden': 'true' } }));
            remove.addEventListener('click', () => removeRecord(record));
            action.append(remove);

            const notes = make('td');
            notes.append(record.notes ? document.createTextNode(record.notes) : make('span', { className: 'arMuted', text: '--' }));

            tr.append(
                make('td', { text: dateLabel(record.date) }),
                make('td', { text: record.event }),
                time,
                change,
                notes,
                make('td', { text: record.recorded_by || '--' }),
                action
            );

            el.historyBody.append(tr);
        });
    }

    function renderManage() {
        renderBests();
        renderHistory();
        renderAttendance();
        el.save.disabled = busy || !athleteId;
    }

    async function loadAthlete() {
        athleteId = dropdownValue(el.athleteDropdown) || null;
        payload = null;
        renderManage();

        if (!athleteId) return;

        try {
            const result = await api(URLS.athlete(athleteId));

            if (!result.ok) {
                showToast(errorText(result, 'Unable to load this athlete.'), 'danger');
                return;
            }

            payload = result.data;
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while loading this athlete.', 'danger');
        }

        renderManage();
    }

    async function saveRecord() {
        if (busy || !athleteId) return;

        const time = el.time.value.trim();

        if (!time) {
            showToast('Enter the time first.', 'danger');
            return;
        }

        if (!el.date.value) {
            showToast('Choose the date of the swim.', 'danger');
            return;
        }

        busy = true;
        el.save.disabled = true;
        el.save.textContent = 'Saving...';

        try {
            const result = await api(URLS.store, {
                method: 'POST',
                body: {
                    athlete_id: Number(athleteId),
                    stroke: dropdownValue(el.strokeDropdown),
                    distance: Number(dropdownValue(el.distanceDropdown)),
                    time,
                    recorded_on: el.date.value,
                    notes: el.notes.value.trim() || null
                }
            });

            if (result.ok) {
                payload = result.data;
                lbStale = true;
                el.time.value = '';
                el.notes.value = '';
                showToast('New record saved.', 'success');
            } else {
                showToast(errorText(result, 'Unable to save the record.'), 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving the record.', 'danger');
        } finally {
            busy = false;
            el.save.textContent = 'Save Record';
            renderManage();
        }
    }

    async function removeRecord(record) {
        if (busy) return;
        if (!window.confirm(`Remove ${record.event} - ${record.time}? The previous record becomes the current record again.`)) return;

        busy = true;

        try {
            const result = await api(URLS.remove(record.id), { method: 'DELETE' });

            if (result.ok) {
                payload = result.data;
                lbStale = true;
                showToast('Record removed.', 'success');
            } else {
                showToast(errorText(result, 'Unable to remove the record.'), 'danger');
            }
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while removing the record.', 'danger');
        } finally {
            busy = false;
            renderManage();
        }
    }

    /* ---------- attendance of the chosen athlete ---------- */

    let athletes = [];          // ONLY the athletes this coach logged today (the server decides)
    let canLog = false;         // this coach has timed in (on the Attendance page)
    let blockReason = null;
    let optionsKey = '';
    let clockMode = null;       // 'in' | 'out'

    const selectedAthlete = () => athletes.find(a => String(a.id) === String(athleteId)) || null;

    // 'Absent' | 'Expected' for an athlete who only has a mark (no Time In), else null.
    function markLabelOf(a) {
        if (!a || a.time_in) return null;
        if (a.state === 'Absent') return 'Absent';
        return a.is_expecting ? EXPECTING_LABEL : null;
    }

    function renderAttendance() {
        const a = selectedAthlete();

        el.athleteEmpty.hidden = !canManage || athletes.length > 0;
        el.attendance.hidden = !a;
        if (!a) return;

        const label = a.state === 'Expecting' ? EXPECTING_LABEL : (a.state || 'Logged');
        const parts = [`Today: ${label}`];
        if (a.time_in) parts.push(`Time In ${formatTimeLabel(a.time_in)}`);
        if (a.time_out) parts.push(`Time Out ${formatTimeLabel(a.time_out)}`);
        el.attStatus.textContent = parts.join(' \u00B7 ');

        // Needs this coach to be timed in; the buttons follow the same rules as the Attendance page.
        const open = canLog && !busy;
        const mark = markLabelOf(a);

        el.timeInButton.disabled = !open || Boolean(a.time_in);
        el.timeOutButton.disabled = !open || !a.time_in || Boolean(a.time_out);
        el.absentButton.disabled = !open || Boolean(a.time_in) || a.state === 'Absent';
        el.removeMarkButton.disabled = !open || !mark;
        el.removeMarkButton.textContent = mark ? `Remove ${mark}` : 'Remove Mark';

        el.attNotice.hidden = canLog || !blockReason;
        if (blockReason && el.attNotice.textContent !== blockReason) el.attNotice.textContent = blockReason;
    }

    // Fills the dropdown with the athletes the server returned. Only touches it when the list
    // changed, so the auto-refresh never closes a menu the coach is using.
    function syncAthleteOptions() {
        const options = [{ value: '', label: 'Select an athlete' }].concat(
            athletes.map(a => {
                const mark = markLabelOf(a);
                return { value: String(a.id), label: mark ? `${a.name} (${mark})` : a.name };
            })
        );

        // Removing a mark takes the athlete out of the list: drop the selection too.
        if (athleteId && !selectedAthlete()) {
            athleteId = null;
            payload = null;
            optionsKey = '';
        }

        const key = JSON.stringify(options);
        if (key === optionsKey) return;

        optionsKey = key;
        window.FBSSDropdown.setOptions(el.athleteDropdown, options, athleteId ? String(athleteId) : '');
    }

    function applyAttendanceData(data) {
        athletes = data.athletes || [];
        canLog = Boolean(data.can_log);
        blockReason = data.block_reason || null;

        if (!canManage) return;

        const hadSelection = athleteId;
        syncAthleteOptions();

        if (hadSelection && !athleteId) renderManage();   // the chosen athlete left the list
        else renderAttendance();
    }

    const todayParam = () => `?date=${toLocalISODate(new Date())}`;

    function touchUpdated() {
        el.lastUpdated.textContent = `Auto-refreshes every ${REFRESH_MS / 1000}s. Updated ${new Date().toLocaleTimeString('en-US')}`;
    }

    async function loadAthletes() {
        const result = await api(URLS.data + todayParam());
        if (result.ok) applyAttendanceData(result.data);
    }

    // One attendance action for the chosen athlete. Returns true when it saved.
    async function attendanceAction(url, extra, okText) {
        const a = selectedAthlete();
        if (!a || busy || !canLog) return false;

        busy = true;
        renderAttendance();
        el.save.disabled = true;

        let saved = false;

        try {
            const result = await api(url, {
                method: 'POST',
                body: { athlete_id: a.id, date: toLocalISODate(new Date()), ...extra }
            });

            if (result.ok) {
                saved = true;
                showToast(result.data.message || okText, 'success');
            } else {
                showToast(errorText(result, 'Unable to save the attendance.'), 'danger');
            }

            // Whatever happened, show what the server now says.
            await loadAthletes();
        } catch (error) {
            console.error(error);
            showToast('Something went wrong while saving the attendance.', 'danger');
        } finally {
            busy = false;
            renderAttendance();
            el.save.disabled = busy || !athleteId;
        }

        return saved;
    }

    function openClock(mode) {
        const a = selectedAthlete();
        if (!a || busy || !canLog) return;

        clockMode = mode;
        el.clockTitle.textContent = mode === 'in' ? 'Record Time In' : 'Record Time Out';
        el.clockName.textContent = a.name;
        el.clockInput.value = new Date().toTimeString().slice(0, 5);   // "HH:MM", change it if needed
        el.clockOverlay.hidden = false;
        el.clockInput.focus();
    }

    function closeClock() {
        el.clockOverlay.hidden = true;
        clockMode = null;
    }

    async function saveClock() {
        const a = selectedAthlete();
        if (!a || busy || !clockMode) return;

        const time = el.clockInput.value;   // "HH:MM"

        if (!time) {
            showToast('Please choose a time.', 'danger');
            return;
        }

        if (clockMode === 'out' && a.time_in && time <= a.time_in) {
            showToast('Time Out must be later than Time In.', 'danger');
            return;
        }

        const saved = await attendanceAction(
            clockMode === 'in' ? URLS.timeIn : URLS.timeOut,
            { time },
            clockMode === 'in' ? 'Time In saved.' : 'Time Out saved.'
        );

        if (saved) closeClock();
    }

    el.timeInButton.addEventListener('click', () => openClock('in'));
    el.timeOutButton.addEventListener('click', () => openClock('out'));
    el.absentButton.addEventListener('click', () => attendanceAction(URLS.absent, {}, 'Marked as Absent.'));
    el.removeMarkButton.addEventListener('click', () => {
        const a = selectedAthlete();
        if (!markLabelOf(a)) return;

        attendanceAction(a.state === 'Absent' ? URLS.absent : URLS.expecting, { remove: true }, 'Mark removed.');
    });

    el.clockSave.addEventListener('click', saveClock);
    el.clockCancel.addEventListener('click', closeClock);
    el.clockClose.addEventListener('click', closeClock);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !el.clockOverlay.hidden) closeClock();
    });

    // Keeps the list and the leaderboard current without pressing anything. Skipped while the tab
    // is hidden, a save is running, the time modal is open or the athlete menu is being used.
    let refreshing = false;

    async function refreshQuietly() {
        const menuOpen = el.athleteDropdown.querySelector('.registerDropdownToggle')?.getAttribute('aria-expanded') === 'true';
        if (document.hidden || busy || refreshing || menuOpen || !el.clockOverlay.hidden) return;

        refreshing = true;

        try {
            if (canManage) await loadAthletes();
            if (!el.panelLeaderboards.hidden && lb.stroke) await loadLeaderboard({ silent: true });
            touchUpdated();
        } catch (error) {
            console.error(error);
        } finally {
            refreshing = false;
        }
    }

    document.addEventListener('visibilitychange', refreshQuietly);
    setInterval(refreshQuietly, REFRESH_MS);

    // Only digits, ":" and "." in the time box.
    el.time.addEventListener('input', () => {
        el.time.value = el.time.value.replace(/[^0-9:.]/g, '');
    });

    el.athleteDropdown.addEventListener('dropdown:change', loadAthlete);
    el.save.addEventListener('click', saveRecord);

    function setupManage() {
        // The athlete list (only the ones this coach logged today) is filled by applyAttendanceData().
        window.FBSSDropdown.setOptions(
            el.strokeDropdown,
            strokes.map(s => ({ value: s.key, label: s.label })),
            strokes[0].key
        );

        window.FBSSDropdown.setOptions(
            el.distanceDropdown,
            distances.map(d => ({ value: String(d), label: `${d} m` })),
            String(distances[0])
        );

        const today = toLocalISODate(new Date());
        el.date.max = today;
        el.date.value = today;

        el.tabs.querySelector('[data-ar-tab="manage"]').hidden = false;
        renderManage();
    }

    /* ---------- start ---------- */

    async function init() {
        emptyRow(el.lbBody, 4, 'Loading...');

        try {
            const result = await api(URLS.data + todayParam());

            if (!result.ok) {
                emptyRow(el.lbBody, 4, 'Unable to load athlete records.');
                showToast(errorText(result, 'Unable to load athlete records.'), 'danger');
                return;
            }

            strokes = result.data.strokes || [];
            distances = result.data.distances || [];
            canManage = Boolean(result.data.can_manage);

            lb = { stroke: strokes[0].key, distance: distances[0] };
            setupLbFilters();
            loadLeaderboard();

            if (canManage) setupManage();
            applyAttendanceData(result.data);
            touchUpdated();
        } catch (error) {
            console.error(error);
            emptyRow(el.lbBody, 4, 'Unable to load athlete records.');
            showToast('Something went wrong while loading athlete records.', 'danger');
        }
    }

    init();
})();
</script>