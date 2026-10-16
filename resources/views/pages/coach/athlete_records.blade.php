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
    <div id="arPanelLeaderboards">
        <div class="arFilters">
            <div class="arFilterGroup">
                <span class="arFilterLabel">Stroke</span>
                <div class="btn-tab-group" id="arLbStrokes" role="group" aria-label="Stroke"></div>
            </div>
            <div class="arFilterGroup">
                <span class="arFilterLabel">Distance</span>
                <div class="btn-tab-group" id="arLbDistances" role="group" aria-label="Distance"></div>
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

<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    const URLS = {
        data: '/coach/athlete-records/data',
        leaderboard: '/coach/athlete-records/leaderboard',
        athlete: id => `/coach/athlete-records/athletes/${id}`,
        store: '/coach/athlete-records',
        remove: id => `/coach/athlete-records/${id}`
    };

    const $ = id => document.getElementById(id);

    const el = {
        tabs: $('arTabs'),
        panelLeaderboards: $('arPanelLeaderboards'),
        panelManage: $('arPanelManage'),

        lbStrokes: $('arLbStrokes'),
        lbDistances: $('arLbDistances'),
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

        historyBody: $('arHistoryBody')
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

    function buildChoiceGroup(container, items, isActive, onPick) {
        container.replaceChildren();

        items.forEach(item => {
            const button = make('button', {
                className: `btn btn-outline-primary btn-tab${isActive(item) ? ' active' : ''}`,
                text: item.label,
                attributes: { type: 'button' }
            });
            button.addEventListener('click', () => onPick(item));
            container.append(button);
        });
    }

    function renderLbFilters() {
        buildChoiceGroup(
            el.lbStrokes,
            strokes.map(s => ({ value: s.key, label: s.label })),
            item => item.value === lb.stroke,
            item => { lb.stroke = item.value; renderLbFilters(); loadLeaderboard(); }
        );

        buildChoiceGroup(
            el.lbDistances,
            distances.map(d => ({ value: d, label: `${d} m` })),
            item => item.value === lb.distance,
            item => { lb.distance = item.value; renderLbFilters(); loadLeaderboard(); }
        );
    }

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

    async function loadLeaderboard() {
        const seq = ++lbSeq;

        emptyRow(el.lbBody, 4, 'Loading...');

        const params = new URLSearchParams({ stroke: lb.stroke, distance: String(lb.distance) });

        try {
            const result = await api(`${URLS.leaderboard}?${params}`);
            if (seq !== lbSeq) return;

            if (!result.ok) {
                emptyRow(el.lbBody, 4, 'Unable to load the leaderboard.');
                showToast(errorText(result, 'Unable to load the leaderboard.'), 'danger');
                return;
            }

            el.lbEvent.textContent = result.data.event;
            renderLeaderboard(result.data.entries || []);
        } catch (error) {
            if (seq !== lbSeq) return;
            console.error(error);
            emptyRow(el.lbBody, 4, 'Unable to load the leaderboard.');
            showToast('Something went wrong while loading the leaderboard.', 'danger');
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

    // Only digits, ":" and "." in the time box.
    el.time.addEventListener('input', () => {
        el.time.value = el.time.value.replace(/[^0-9:.]/g, '');
    });

    el.athleteDropdown.addEventListener('dropdown:change', loadAthlete);
    el.save.addEventListener('click', saveRecord);

    function setupManage(athletes) {
        window.FBSSDropdown.setOptions(
            el.athleteDropdown,
            [{ value: '', label: 'Select an athlete' }].concat(athletes.map(a => ({ value: String(a.id), label: a.name }))),
            ''
        );

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
            const result = await api(URLS.data);

            if (!result.ok) {
                emptyRow(el.lbBody, 4, 'Unable to load athlete records.');
                showToast(errorText(result, 'Unable to load athlete records.'), 'danger');
                return;
            }

            strokes = result.data.strokes || [];
            distances = result.data.distances || [];
            canManage = Boolean(result.data.can_manage);

            lb = { stroke: strokes[0].key, distance: distances[0] };
            renderLbFilters();
            loadLeaderboard();

            if (canManage) setupManage(result.data.athletes || []);
        } catch (error) {
            console.error(error);
            emptyRow(el.lbBody, 4, 'Unable to load athlete records.');
            showToast('Something went wrong while loading athlete records.', 'danger');
        }
    }

    init();
})();
</script>