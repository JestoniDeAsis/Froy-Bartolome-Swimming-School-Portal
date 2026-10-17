@extends('layouts.toast')

<style>
/* Page-specific layout only. Typography, buttons, tabs and the base look come
   from default.css / our_theme.css. */

.adPage {
    width: 100%;
    padding: 1.25rem;
    box-sizing: border-box;
}

.adHeader { margin-bottom: 1rem; }

/* ---- Summary cards ---- */

.adSummary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: .75rem;
    margin-bottom: 1rem;
}

.adCard {
    padding: 1.1rem 1.25rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    min-width: 0;
}

.adCardLabel {
    margin: 0 0 .3rem;
    color: #667085;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.adCardValue { margin: 0; color: #101828; font-size: 1.15rem; font-weight: 700; overflow-wrap: anywhere; }
.adCardSub { margin: .15rem 0 0; color: #667085; font-size: .78rem; }

.adCardTitle {
    margin: 0 0 .85rem;
    color: #667085;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

.adTabs { margin-bottom: 1rem; }
.adStack { display: grid; gap: 1rem; }

/* ---- Filters (leaderboards) ---- */

.adFilters { display: flex; flex-wrap: wrap; gap: .75rem 1.5rem; margin-bottom: 1rem; }
.adFilterGroup { display: flex; flex-direction: column; gap: .35rem; }
.adFilterGroup .registerDropdown { --dd-min-width: 180px; }

.adFilterLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}

.adEventTitle { margin: 0 0 .75rem; color: #1f2937; font-size: 1.15rem; font-weight: 700; }

/* ---- Tables ---- */

.adTableWrap { width: 100%; overflow: auto; }

.adTable { width: 100%; margin: 0; white-space: nowrap; border-collapse: separate; border-spacing: 0; }

.adTable th {
    padding: .65rem .9rem;
    background: #f8fafb;
    border-bottom: 1px solid #e7ebef;
    color: #667085;
    font-size: .8125rem;
    font-weight: 600;
    text-align: left;
}

.adTable td { padding: .65rem .9rem; border-bottom: 1px solid #f1f3f5; vertical-align: middle; color: #344054; }

.adTable tbody tr:last-child td { border-bottom: 0; }
.adTable tbody tr:hover { background: #f8fafb; }

/* the logged-in athlete's own row in a leaderboard */
.adTable tbody tr.adMe,
.adTable tbody tr.adMe:hover { background: #e7f5ff; }

.adEmpty { padding: 1.75rem 1rem !important; color: #6c757d; text-align: center; }

.adTime { font-weight: 700; color: #1f2937; font-variant-numeric: tabular-nums; }
.adRankNote { display: block; color: #667085; font-size: .72rem; font-weight: 500; }
.adMuted { color: #98a2b3; }

.adRank { display: inline-flex; align-items: center; gap: .4rem; font-weight: 700; }
.adRank .bi-trophy-fill.gold   { color: #d4a017; }
.adRank .bi-trophy-fill.silver { color: #98a2b3; }
.adRank .bi-trophy-fill.bronze { color: #b87333; }

.adYou {
    margin-left: .4rem;
    padding: .1rem .5rem;
    border-radius: 999px;
    background: var(--fbss-blue, #0d6efd);
    color: #fff;
    font-size: .68rem;
    font-weight: 700;
}

.adGood { color: var(--fbss-success, #1a7f3c); font-weight: 600; }
.adBad  { color: var(--fbss-danger, #c22b3a); font-weight: 600; }

@media (max-width: 575.98px) {
    .adPage { padding: 1rem; }
}
</style>

<section class="adPage" id="athleteDashboard" aria-labelledby="adTitle">

    <header class="adHeader">
        <h2 class="mb-1" id="adTitle">Dashboard</h2>
        <p class="text-muted mb-0" id="adWelcome">Welcome.</p>
    </header>

    <div class="adSummary" id="adSummary">
        <div class="adCard">
            <p class="adCardLabel">Records Set</p>
            <p class="adCardValue" id="adSumRecords">--</p>
        </div>
        <div class="adCard">
            <p class="adCardLabel">Events With a Record</p>
            <p class="adCardValue" id="adSumEvents">--</p>
        </div>
        <div class="adCard">
            <p class="adCardLabel">Best Ranking</p>
            <p class="adCardValue" id="adSumRank">--</p>
            <p class="adCardSub" id="adSumRankSub"></p>
        </div>
        <div class="adCard">
            <p class="adCardLabel">Latest Record</p>
            <p class="adCardValue" id="adSumLatest">--</p>
            <p class="adCardSub" id="adSumLatestSub"></p>
        </div>
    </div>

    <div class="btn-tab-group adTabs" id="adTabs" role="tablist" aria-label="Dashboard sections">
        <button class="btn btn-outline-primary btn-tab active" type="button" role="tab" data-ad-tab="mine" aria-selected="true">My Records</button>
        <button class="btn btn-outline-primary btn-tab" type="button" role="tab" data-ad-tab="leaderboards" aria-selected="false">Leaderboards</button>
    </div>

    {{-- ===== MY RECORDS ===== --}}
    <div class="adStack" id="adPanelMine">

        <article class="adCard">
            <h3 class="adCardTitle">My current records</h3>
            <div class="adTableWrap">
                <table class="table adTable">
                    <thead id="adBestsHead"></thead>
                    <tbody id="adBestsBody"></tbody>
                </table>
            </div>
        </article>

        <article class="adCard">
            <h3 class="adCardTitle">History</h3>
            <div class="adTableWrap">
                <table class="table adTable">
                    <thead>
                        <tr><th>Date</th><th>Event</th><th>Time</th><th>Change</th><th>Notes</th><th>Recorded By</th></tr>
                    </thead>
                    <tbody id="adHistoryBody"></tbody>
                </table>
            </div>
        </article>

    </div>

    {{-- ===== LEADERBOARDS (view only) ===== --}}
    <div id="adPanelLeaderboards" hidden>
        <div class="adFilters">
            <div class="adFilterGroup">
                <span class="adFilterLabel">Stroke</span>
                <div class="registerDropdown" id="adLbStrokeDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="adLbStrokeOptions" aria-label="Stroke">
                        <span>Freestyle</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>
                    <div class="registerDropdownOptions" id="adLbStrokeOptions" hidden></div>
                    <input id="adLbStrokeInput" type="hidden" value="">
                </div>
            </div>
            <div class="adFilterGroup">
                <span class="adFilterLabel">Distance</span>
                <div class="registerDropdown" id="adLbDistanceDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" type="button" aria-expanded="false" aria-controls="adLbDistanceOptions" aria-label="Distance">
                        <span>50 m</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>
                    <div class="registerDropdownOptions" id="adLbDistanceOptions" hidden></div>
                    <input id="adLbDistanceInput" type="hidden" value="">
                </div>
            </div>
        </div>

        <article class="adCard">
            <h3 class="adEventTitle" id="adLbEvent">--</h3>
            <div class="adTableWrap">
                <table class="table adTable">
                    <thead>
                        <tr><th>Rank</th><th>Athlete</th><th>Time</th><th>Date Set</th></tr>
                    </thead>
                    <tbody id="adLbBody"></tbody>
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
        data: '/athlete/records/data',
        leaderboard: '/athlete/records/leaderboard'
    };

    const $ = id => document.getElementById(id);

    const el = {
        welcome: $('adWelcome'),
        tabs: $('adTabs'),
        panelMine: $('adPanelMine'),
        panelLeaderboards: $('adPanelLeaderboards'),

        sumRecords: $('adSumRecords'),
        sumEvents: $('adSumEvents'),
        sumRank: $('adSumRank'),
        sumRankSub: $('adSumRankSub'),
        sumLatest: $('adSumLatest'),
        sumLatestSub: $('adSumLatestSub'),

        bestsHead: $('adBestsHead'),
        bestsBody: $('adBestsBody'),
        historyBody: $('adHistoryBody'),

        lbStrokeDropdown: $('adLbStrokeDropdown'),
        lbDistanceDropdown: $('adLbDistanceDropdown'),
        lbEvent: $('adLbEvent'),
        lbBody: $('adLbBody')
    };

    let strokes = [];
    let distances = [];
    let payload = { bests: {}, history: [] };

    let lb = { stroke: null, distance: null };
    let lbSeq = 0;
    let lbLoaded = false;

    /* ---------- helpers ---------- */

    function make(tag, { className, text, attributes } = {}) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        if (attributes) Object.entries(attributes).forEach(([k, v]) => node.setAttribute(k, v));
        return node;
    }

    function dateLabel(iso) {
        if (!iso) return '';
        const [y, m, d] = iso.split('-').map(Number);
        return new Date(y, m - 1, d).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    async function api(url) {
        const response = await fetch(url, { headers: { 'Accept': 'application/json' } });

        let data = {};
        try { data = await response.json(); } catch (e) { /* non-JSON error page */ }

        return { ok: response.ok, status: response.status, data };
    }

    function errorText(result, fallback) {
        const errors = result.data.errors;
        const first = errors ? Object.values(errors).flat()[0] : null;
        return first || result.data.message || `${fallback} (error ${result.status})`;
    }

    function emptyRow(body, columns, text) {
        const tr = make('tr');
        tr.append(make('td', { className: 'adEmpty', text, attributes: { colSpan: columns } }));
        body.replaceChildren(tr);
    }

    /* ---------- tabs ---------- */

    function showTab(name) {
        el.tabs.querySelectorAll('[data-ad-tab]').forEach(button => {
            const active = button.dataset.adTab === name;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
        });

        el.panelMine.hidden = name !== 'mine';
        el.panelLeaderboards.hidden = name !== 'leaderboards';

        if (name === 'leaderboards' && !lbLoaded) {
            lbLoaded = true;
            loadLeaderboard();
        }
    }

    el.tabs.addEventListener('click', event => {
        const button = event.target.closest('[data-ad-tab]');
        if (button) showTab(button.dataset.adTab);
    });

    /* ---------- my records ---------- */

    function renderSummary() {
        const bests = Object.entries(payload.bests || {});
        const history = payload.history || [];
        const totalEvents = strokes.length * distances.length;

        el.sumRecords.textContent = String(history.length);
        el.sumEvents.textContent = `${bests.length} of ${totalEvents}`;

        // best (lowest) rank among my events
        const ranked = bests
            .filter(([, best]) => best.rank)
            .sort((a, b) => a[1].rank - b[1].rank);

        if (ranked.length) {
            const [key, best] = ranked[0];
            const [strokeKey, distance] = key.split('-');
            const stroke = strokes.find(s => s.key === strokeKey);

            el.sumRank.textContent = `#${best.rank}`;
            el.sumRankSub.textContent = `${distance} m ${stroke ? stroke.label : strokeKey} · of ${best.of}`;
        } else {
            el.sumRank.textContent = '--';
            el.sumRankSub.textContent = '';
        }

        if (history.length) {
            const latest = history[0];
            el.sumLatest.textContent = `${latest.event} · ${latest.time}`;
            el.sumLatestSub.textContent = dateLabel(latest.date);
        } else {
            el.sumLatest.textContent = '--';
            el.sumLatestSub.textContent = '';
        }
    }

    function renderBests() {
        el.bestsHead.replaceChildren();
        el.bestsBody.replaceChildren();

        const headRow = make('tr');
        headRow.append(make('th', { text: 'Stroke' }));
        distances.forEach(d => headRow.append(make('th', { text: `${d} m` })));
        el.bestsHead.append(headRow);

        strokes.forEach(stroke => {
            const tr = make('tr');
            tr.append(make('td', { text: stroke.label }));

            distances.forEach(distance => {
                const best = (payload.bests || {})[`${stroke.key}-${distance}`];
                const td = make('td');

                if (best) {
                    td.append(make('span', { className: 'adTime', text: best.time, attributes: { title: dateLabel(best.date) } }));
                    if (best.rank) {
                        td.append(make('span', { className: 'adRankNote', text: `#${best.rank} of ${best.of}` }));
                    }
                } else {
                    td.append(make('span', { className: 'adMuted', text: '--' }));
                }

                tr.append(td);
            });

            el.bestsBody.append(tr);
        });
    }

    function renderHistory() {
        const list = payload.history || [];

        if (!list.length) {
            emptyRow(el.historyBody, 6, 'No records yet. Your coach will add your PRs here.');
            return;
        }

        el.historyBody.replaceChildren();

        list.forEach(record => {
            const tr = make('tr');

            const time = make('td');
            time.append(make('span', { className: 'adTime', text: record.time }));

            const change = make('td');
            if (record.improvement) {
                change.append(make('span', { className: record.improvement.startsWith('-') ? 'adGood' : 'adBad', text: record.improvement }));
            } else {
                change.append(make('span', { className: 'adMuted', text: 'First record' }));
            }

            const notes = make('td');
            notes.append(record.notes ? document.createTextNode(record.notes) : make('span', { className: 'adMuted', text: '--' }));

            tr.append(
                make('td', { text: dateLabel(record.date) }),
                make('td', { text: record.event }),
                time,
                change,
                notes,
                make('td', { text: record.recorded_by || '--' })
            );

            el.historyBody.append(tr);
        });
    }

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
        const wrapper = make('span', { className: 'adRank' });

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
            const tr = make('tr', { className: entry.is_me ? 'adMe' : '' });

            const rank = make('td');
            rank.append(rankCell(entry.rank));

            const name = make('td');
            name.append(document.createTextNode(entry.name));
            if (entry.is_me) name.append(make('span', { className: 'adYou', text: 'You' }));

            const time = make('td');
            time.append(make('span', { className: 'adTime', text: entry.time }));

            tr.append(rank, name, time, make('td', { text: dateLabel(entry.date) }));
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

    /* ---------- start ---------- */

    async function init() {
        emptyRow(el.historyBody, 6, 'Loading...');

        try {
            const result = await api(URLS.data);

            if (!result.ok) {
                emptyRow(el.historyBody, 6, 'Unable to load your records.');
                showToast(errorText(result, 'Unable to load your records.'), 'danger');
                return;
            }

            strokes = result.data.strokes || [];
            distances = result.data.distances || [];
            payload = { bests: result.data.bests || {}, history: result.data.history || [] };

            el.welcome.textContent = `Welcome, ${result.data.first_name || 'athlete'}.`;

            lb = { stroke: strokes[0].key, distance: distances[0] };
            setupLbFilters();

            renderSummary();
            renderBests();
            renderHistory();
        } catch (error) {
            console.error(error);
            emptyRow(el.historyBody, 6, 'Unable to load your records.');
            showToast('Something went wrong while loading your records.', 'danger');
        }
    }

    init();
})();
</script>