@extends('layouts.toast')

@php
    // Athletes (role 4) see the same page without the course filter and Course card.
    $isAthlete = (int) auth()->user()->role_id === 4;
@endphp

<style>
/* =========================================================
   Payments (swimmer, view only)
   Same look as the Attendance page. Dropdowns use the shared
   .registerDropdown + assets/js/fbss-dropdown.js (data-fbss-dropdown).
========================================================= */

.paymentsContainer {
    width: 100%;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
}

.paymentsHeader { flex: 0 0 auto; margin-bottom: 1rem; }

/* Summary cards */
.paymentsSummary {
    flex: 0 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: .75rem;
    margin-bottom: 1rem;
}

.paymentsCard {
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    padding: .85rem 1rem;
    min-width: 0;
}

.paymentsCardLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #667085;
    margin-bottom: .25rem;
}

.paymentsCardValue { font-size: 1.05rem; font-weight: 600; color: #101828; overflow-wrap: anywhere; }
.paymentsCardValue.isOwing { color: var(--fbss-danger, #c22b3a); }
.paymentsCardValue.isPaid  { color: var(--fbss-success, #1a7f3c); }

/* Toolbar */
.paymentsToolbar {
    display: flex;
    align-items: flex-end;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

.paymentsFilterGroup { display: flex; flex-direction: column; gap: .35rem; }

.paymentsFilterLabel {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    color: #667085;
    text-transform: uppercase;
}

/* Sizes come from the shared dropdown variables (our_theme.css). */
.paymentsCourseFilter { --dd-min-width: 300px; }

.paymentsResultCount { margin-left: auto; font-size: .8125rem; color: #667085; white-space: nowrap; }

/* Table */
.paymentsTableContainer {
    --payments-row-height: 49px;
    --payments-header-height: 49px;
    --payments-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;
    max-height: calc(var(--payments-header-height) + (var(--payments-row-height) * var(--payments-visible-rows)));
    width: 100%;
    overflow: auto;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    box-sizing: border-box;
}

.paymentsTable {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.paymentsTable thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafb;
    color: #667085;
    font-weight: 600;
    border-bottom: 1px solid #e7ebef;
    box-shadow: 0 1px 0 #e7ebef;
}

.paymentsTable th,
.paymentsTable td { padding: .75rem 1rem; vertical-align: middle; }

.paymentsRow:hover { background: #f8fafb; }

.paymentsEmptyState { padding: 2rem !important; color: #6c757d; text-align: center; }
.paymentsEmptyValue { color: #adb5bd; }
.paymentsAmount { color: var(--fbss-success, #1a7f3c); font-weight: 700; }

/* Status pills */
.paymentsPill {
    display: inline-block;
    padding: .2rem .65rem;
    border-radius: 999px;
    font-size: .75rem;
    font-weight: 600;
    white-space: nowrap;
}

.paymentsPill.is-paid    { background: var(--fbss-success-tint, #e6f7ec); color: var(--fbss-success, #1a7f3c); }
.paymentsPill.is-partial { background: #fff4e0; color: #b26a00; }
.paymentsPill.is-unpaid  { background: var(--fbss-danger-tint, #fdebec); color: var(--fbss-danger, #c22b3a); }

/* Change history (archived / edited entries) */
.paymentsHistory { flex: 0 0 auto; margin-top: 1rem; }
.paymentsHistory summary { cursor: pointer; font-weight: 600; color: #344054; }
.paymentsHistory .paymentsTableContainer { --payments-visible-rows: 4; margin-top: .6rem; }
.paymentsHistoryNote { margin: .4rem 0 0; font-size: .8rem; color: #667085; }
.paymentsHistory .paymentsRow td { color: #667085; }
.paymentsHistory .paymentsAmount { color: #667085; text-decoration: line-through; }

@media (max-width: 575.98px) {
    .paymentsContainer { padding: 1rem; }
    .paymentsResultCount { margin-left: 0; width: 100%; }
}

/* ---------- Invoice popup: same design as the admin Billing page ---------- */

.paymentsReceipt {
    --sales-blue: var(--fbss-blue, #0D6EFD);
    --sales-ink: #16202c;
    --sales-muted: #64748b;
    --sales-line: #dce5ed;

    width: min(465px, calc(100vw - 1.25rem));
    max-height: 92vh;
    overflow-y: auto;
    background: #fff;
    color: var(--sales-ink);
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(16, 24, 40, .18);
}

.sales-badge {
    display: inline-flex;
    min-width: 42px;
    justify-content: center;
    border-radius: 999px;
    padding: .2rem .45rem;
    font-size: .7rem;
    font-weight: 600;
}

.sales-badge--paid {
    color: var(--fbss-success, #198754);
    background: var(--fbss-success-tint, #E9F5EF);
}

.sales-badge--unpaid {
    color: var(--fbss-danger, #DC3545);
    background: var(--fbss-danger-tint, #FBEAEC);
}

.sales-badge--partial {
    color: var(--fbss-warning-text, #6A4E00);
    background: #FFF7E0;
}

.sales-badge--archived {
    color: #5B6470;
    background: #ECEEF1;
}

.sales-receipt-notice {
    margin: .5rem 0 0;
    text-align: center;
    color: var(--sales-muted);
    font-size: .66rem;
}

.sales-receipt-actions {
    display: flex;
    justify-content: flex-end;
    padding: .6rem 1.1rem 1rem;
    background: #fff;
}

.sales-receipt {
    overflow: hidden;
    background: #fff;
    color: var(--sales-ink);
    border-radius: 12px;
}

.sales-receipt-head {
    padding: 1rem 1.1rem;
    background: var(--sales-blue);
    color: #fff;
}

.sales-receipt-brand {
    display: flex;
    align-items: center;
    gap: .55rem;
    margin-bottom: .6rem;
    font-size: .9rem;
    font-weight: 700;
}

.sales-receipt-brand-mark {
    width: 22px;
    height: 22px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: #fff;
    color: var(--sales-blue);
    font-size: .6rem;
}

.sales-receipt-head strong {
    display: block;
    font-size: .93rem;
}

.sales-receipt-head small {
    display: block;
    margin-top: .2rem;
    color: rgba(255, 255, 255, .85);
    font-size: .68rem;
}

.sales-receipt-body {
    padding: .9rem 1.1rem 1.15rem;
}

.sales-receipt-meta {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: .45rem;
    padding-bottom: .55rem;
    border-bottom: 1px solid #e6edf2;
    font-size: .75rem;
}

.sales-receipt-meta span:nth-child(odd) {
    color: var(--sales-muted);
}

.sales-receipt-section {
    margin: .75rem 0 .4rem;
    color: var(--sales-blue);
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
}

.sales-receipt-line {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    padding: .35rem 0;
    font-size: .8rem;
}

.sales-receipt-line small {
    display: block;
    color: var(--sales-muted);
}

.sales-receipt-total {
    border-top: 1px solid #dfe7ee;
    padding-top: .5rem;
    font-weight: 700;
}

.sales-receipt-amounts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .4rem;
    margin: .8rem 0;
}

.sales-receipt-amount {
    padding: .55rem;
    border: 1px solid #b6d4fe;
    border-radius: var(--fbss-radius, .5rem);
    background: var(--fbss-blue-tint, #E7F1FF);
    color: var(--fbss-blue-active, #0A58CA);
}

.sales-receipt-amount:nth-child(2) {
    background: var(--fbss-success-tint, #E9F5EF);
    border-color: #a3cfbb;
    color: var(--fbss-success, #198754);
}

.sales-receipt-amount:nth-child(3) {
    background: var(--fbss-danger-tint, #FBEAEC);
    border-color: #f1aeb5;
    color: var(--fbss-danger, #DC3545);
}

.sales-receipt-amount small {
    display: block;
    font-size: .6rem;
    font-weight: 600;
    text-transform: uppercase;
}

.sales-receipt-amount strong {
    font-size: 1rem;
}

.sales-receipt-method {
    padding: .55rem .7rem;
    border: 1px solid #e2eaf0;
    border-radius: var(--fbss-radius, .5rem);
    background: #f7f9fb;
    font-size: .72rem;
}

.sales-receipt-thanks {
    margin: .85rem 0 0;
    text-align: center;
    color: var(--sales-blue);
    font-size: .83rem;
    font-weight: 700;
}

.sales-receipt-footer {
    margin: .2rem 0 0;
    text-align: center;
    color: var(--sales-muted);
    font-size: .68rem;
}
</style>


<section class="paymentsContainer" id="paymentsContainer" aria-labelledby="paymentsTitle">

    <header class="paymentsHeader">
        <h2 class="mb-1" id="paymentsTitle">My Payments &amp; Invoices</h2>
        <p class="text-muted mb-0" id="paymentsDescription"></p>
        <p class="text-muted small mb-0">View only. Payments are recorded by the school when you pay. If a payment is corrected, the old entry is kept in the change history below.</p>
    </header>

    <!-- SUMMARY (the Course card is for students only; athletes see the rest) -->
    <div class="paymentsSummary" id="paymentsSummary">
        @unless ($isAthlete)
        <div class="paymentsCard">
            <div class="paymentsCardLabel">Course</div>
            <div class="paymentsCardValue" id="summaryCourse">--</div>
        </div>
        @endunless
        <div class="paymentsCard">
            <div class="paymentsCardLabel">{{ $isAthlete ? 'Total Fees' : 'Total Fee' }}</div>
            <div class="paymentsCardValue" id="summaryFee">--</div>
        </div>
        <div class="paymentsCard">
            <div class="paymentsCardLabel">Total Paid</div>
            <div class="paymentsCardValue" id="summaryPaid">--</div>
        </div>
        <div class="paymentsCard">
            <div class="paymentsCardLabel">Outstanding Balance</div>
            <div class="paymentsCardValue" id="summaryBalance">--</div>
        </div>
        <div class="paymentsCard">
            <div class="paymentsCardLabel">Payment Status</div>
            <div class="paymentsCardValue" id="summaryStatus">--</div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="paymentsToolbar">

        @unless ($isAthlete)
            <div class="paymentsFilterGroup">
                <label class="paymentsFilterLabel">Course</label>

                <div class="registerDropdown paymentsCourseFilter" id="courseFilterDropdown" data-fbss-dropdown>
                    <button class="registerDropdownToggle" id="courseFilterToggle" type="button" aria-expanded="false" aria-controls="courseFilterOptions" aria-label="Filter by course">
                        <span>Loading...</span>
                        <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                    </button>

                    <div class="registerDropdownOptions" id="courseFilterOptions" hidden></div>

                    <input id="courseFilterInput" name="courseFilter" type="hidden" value="">
                </div>
            </div>
        @endunless

        <span class="paymentsResultCount" id="paymentsResultCount"></span>

    </div>

    <!-- TABLE -->
    <div class="paymentsTableContainer" id="paymentsTableContainer">
        <table class="table paymentsTable" id="paymentsTable">
            <thead id="paymentsTableHead"></thead>
            <tbody id="paymentsTableBody"></tbody>
        </table>
    </div>

    <!-- CHANGE HISTORY: archived / edited entries (never counted in balances) -->
    <details class="paymentsHistory" id="paymentsHistory" hidden>
        <summary>Change history (<span id="historyCount">0</span>)</summary>
        <p class="paymentsHistoryNote">These entries were corrected or removed by the school. They no longer count toward your balance.</p>
        <div class="paymentsTableContainer">
            <table class="table paymentsTable">
                <thead id="historyHead"></thead>
                <tbody id="historyBody"></tbody>
            </table>
        </div>
    </details>

</section>


<!-- INVOICE (same layout as the admin Billing page) -->
<div class="modalOverlay" id="receiptOverlay" hidden>
    <div class="paymentsReceipt" id="receiptCard" role="dialog" aria-modal="true" aria-label="Invoice">
        <article class="sales-receipt" id="receiptContent"></article>
        <div class="sales-receipt-actions"><button class="btn btn-outline-secondary btn-sm" type="button" id="receiptCloseButton">Close</button></div>
    </div>
</div>


<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    'use strict';

    const URL_DATA = '/payments/data';
    const IS_ATHLETE = @json($isAthlete);

    const STATUS = {
        paid: { text: 'Paid', cls: 'is-paid' },
        partial: { text: 'Partial', cls: 'is-partial' },
        unpaid: { text: 'Unpaid', cls: 'is-unpaid' }
    };

    // Athletes pay fees, not courses: the third column shows the fee instead.
    const HEADERS = IS_ATHLETE
        ? ['Date', 'Invoice No.', 'Fee', 'Amount', 'Method', 'Reference No.', 'Balance After', 'Status', 'Invoice']
        : ['Date', 'Invoice No.', 'Course', 'Amount', 'Method', 'Reference No.', 'Balance After', 'Status', 'Invoice'];

    const HISTORY_HEADERS = ['Invoice No.', 'Date paid', IS_ATHLETE ? 'Fee' : 'Course', 'Amount', 'Method', 'Reference No.', 'Archived on', 'Archived by', 'Reason', 'Replaced by'];

    const $ = id => document.getElementById(id);

    const el = {
        description: $('paymentsDescription'),
        courseFilter: $('courseFilterDropdown'),      // null for athletes
        count: $('paymentsResultCount'),
        tableHead: $('paymentsTableHead'),
        tableBody: $('paymentsTableBody'),
        sCourse: $('summaryCourse'),
        sFee: $('summaryFee'),
        sPaid: $('summaryPaid'),
        sBalance: $('summaryBalance'),
        sStatus: $('summaryStatus'),
        overlay: $('receiptOverlay'),
        history: $('paymentsHistory'),
        historyCount: $('historyCount'),
        historyHead: $('historyHead'),
        historyBody: $('historyBody')
    };

    let enrollments = [];
    let payments = [];
    let history = [];
    let athleteSummary = null;   // athletes only: totals computed by the server
    let client = '';
    let loading = true;

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
    const dateLabel = iso => iso ? formatShortDate(parseISODate(iso)) : '';
    const peso = n => '₱' + Number(n || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });

    const dropdownValue = root => (root ? window.FBSSDropdown.get(root) : 'all');

    function cell(value, dash = '--') {
        return value
            ? document.createTextNode(value)
            : make('span', { className: 'paymentsEmptyValue', text: dash });
    }

    function pill(status) {
        const info = STATUS[status] || STATUS.unpaid;
        return make('span', { className: `paymentsPill ${info.cls}`, text: info.text });
    }

    function statusFor(price, paid) {
        const balance = Math.max(price - paid, 0);
        if (price > 0 && balance <= 0) return 'paid';
        return paid > 0 ? 'partial' : 'unpaid';
    }

    /* ---------- selection ---------- */

    function selectedEnrollment() {
        const value = dropdownValue(el.courseFilter);
        return enrollments.find(e => String(e.id) === value) || null;
    }

    // Payments for the chosen course ('all' = everything).
    function visiblePayments() {
        const value = dropdownValue(el.courseFilter);
        return value === 'all' || value === ''
            ? payments
            : payments.filter(p => String(p.enrollment_id) === value);
    }

    function buildCourseOptions(defaultId) {
        if (!el.courseFilter) return;

        const items = [{ value: 'all', label: 'All Courses' }]
            .concat(enrollments.map(e => ({ value: String(e.id), label: e.label })));

        window.FBSSDropdown.setOptions(el.courseFilter, items, defaultId ? String(defaultId) : 'all');
    }

    /* ---------- render ---------- */

    function renderSummary() {
        if (IS_ATHLETE) {
            // Athletes have no course card; the server works out what they owe,
            // including fees they haven't paid anything on yet.
            const a = athleteSummary;
            const nodes = [el.sFee, el.sPaid, el.sBalance, el.sStatus];

            if (!a) {
                nodes.forEach(node => { node.textContent = '--'; node.className = 'paymentsCardValue'; });
                return;
            }

            el.sFee.textContent = peso(a.fee);
            el.sPaid.textContent = peso(a.paid);

            el.sBalance.textContent = peso(a.balance);
            el.sBalance.className = `paymentsCardValue ${a.balance > 0 ? 'isOwing' : 'isPaid'}`;

            if (a.status) el.sStatus.replaceChildren(pill(a.status));
            else el.sStatus.textContent = '--';
            return;
        }

        const one = selectedEnrollment();
        const list = one ? [one] : enrollments;

        if (!list.length) {
            [el.sCourse, el.sFee, el.sPaid, el.sBalance, el.sStatus].forEach(node => {
                node.textContent = '--';
                node.className = 'paymentsCardValue';
            });
            return;
        }

        const fee = list.reduce((sum, e) => sum + e.price, 0);
        const paid = list.reduce((sum, e) => sum + e.paid, 0);
        const balance = Math.max(fee - paid, 0);

        el.sCourse.textContent = one ? one.course : (list.length === 1 ? list[0].course : 'All courses');
        el.sFee.textContent = peso(fee);
        el.sPaid.textContent = peso(paid);

        el.sBalance.textContent = peso(balance);
        el.sBalance.className = `paymentsCardValue ${balance > 0 ? 'isOwing' : 'isPaid'}`;

        el.sStatus.replaceChildren(pill(statusFor(fee, paid)));
    }

    function renderTable() {
        const list = visiblePayments();

        el.tableHead.replaceChildren();
        el.tableBody.replaceChildren();

        const headerRow = make('tr');
        headerRow.append(make('th', { text: 'No.', attributes: { scope: 'col' } }));
        HEADERS.forEach(h => headerRow.append(make('th', { text: h, attributes: { scope: 'col' } })));
        el.tableHead.append(headerRow);

        if (loading || !list.length) {
            const emptyRow = make('tr');
            emptyRow.append(make('td', {
                className: 'paymentsEmptyState',
                text: loading
                    ? 'Loading...'
                    : IS_ATHLETE
                        ? 'No athlete payments have been recorded for you yet.'
                        : (enrollments.length ? 'No payments have been recorded for this selection.' : 'You are not enrolled in a course yet.'),
                attributes: { colSpan: HEADERS.length + 1 }
            }));
            el.tableBody.append(emptyRow);
            el.count.textContent = '';
            return;
        }

        list.forEach((payment, index) => {
            const row = make('tr', { className: 'paymentsRow' });
            row.append(make('td', { text: String(index + 1) }));

            const amount = make('span', { className: 'paymentsAmount', text: peso(payment.amount) });

            const view = make('button', {
                className: 'btn btn-icon btn-icon-square btn-outline-primary btn-icon-sm',
                attributes: { type: 'button', 'aria-label': `View invoice ${payment.receipt}` }
            });
            view.append(make('i', { className: 'bi bi-eye', attributes: { 'aria-hidden': 'true' } }));
            view.addEventListener('click', () => openReceipt(payment));

            const cells = [
                cell(dateLabel(payment.date)),
                cell(payment.receipt),
                cell(payment.course),   // course name, or the fee name for athletes
                amount,
                cell(payment.method),
                cell(payment.reference, '-'),
                cell(peso(payment.balance)),
                pill(payment.status),
                view
            ];

            cells.forEach(content => {
                const td = make('td');
                td.append(content);
                row.append(td);
            });

            el.tableBody.append(row);
        });

        const total = list.reduce((sum, p) => sum + p.amount, 0);
        el.count.textContent = `${list.length} payment${list.length === 1 ? '' : 's'} · ${peso(total)} paid`;
    }

    function visibleHistory() {
        const value = dropdownValue(el.courseFilter);
        return value === 'all' || value === ''
            ? history
            : history.filter(h => String(h.enrollment_id) === value);
    }

    function renderHistory() {
        const list = visibleHistory();

        el.history.hidden = !list.length;
        el.historyCount.textContent = String(list.length);
        el.historyHead.replaceChildren();
        el.historyBody.replaceChildren();
        if (!list.length) return;

        const headerRow = make('tr');
        HISTORY_HEADERS.forEach(h => headerRow.append(make('th', { text: h, attributes: { scope: 'col' } })));
        el.historyHead.append(headerRow);

        list.forEach(entry => {
            const row = make('tr', { className: 'paymentsRow' });
            const cells = [
                cell(entry.receipt),
                cell(dateLabel(entry.date)),
                cell(entry.course),
                make('span', { className: 'paymentsAmount', text: peso(entry.amount) }),
                cell(entry.method),
                cell(entry.reference, '-'),
                cell(entry.archived_at),
                cell(entry.archived_by),
                cell(entry.reason),
                cell(entry.replaced_by, 'None')
            ];
            cells.forEach(content => {
                const td = make('td');
                td.append(content);
                row.append(td);
            });
            el.historyBody.append(row);
        });
    }

    function render() {
        renderSummary();
        renderTable();
        renderHistory();
    }

    /* ---------- receipt ---------- */

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c]);

    function openReceipt(payment) {
        const status = payment.status;
        const balance = Number(payment.balance);
        const kind = IS_ATHLETE ? 'Athlete fee' : 'Course';
        const quantityLine = IS_ATHLETE ? 'Athlete fee' : 'Course enrollment';

        const metaRows = [
            ['Invoice #', payment.receipt],
            ['Date paid', dateLabel(payment.date) || '--'],
            ['Client', client || '--']
        ];
        if (payment.reference) metaRows.push(['Reference #', payment.reference]);
        if (payment.received_by) metaRows.push(['Recorded by', payment.received_by]);
        if (payment.corrects) metaRows.push(['Corrects', payment.corrects]);
        const meta = metaRows.map(([label, value]) => `<span>${label}</span><strong>${escapeHtml(value)}</strong>`).join('');

        const thanks = status === 'paid' ? 'Thank you for your payment! This invoice is fully settled.'
            : `Thank you for your payment. Remaining balance: ${peso(balance)}`;

        $('receiptContent').innerHTML =
            `<header class="sales-receipt-head"><div class="sales-receipt-brand"><img src="{{ asset('assets/images/icon_fbss.png') }}" alt="" width="22" height="22" style="border-radius:50%"> FBSS SwimOS <span class="sales-badge sales-badge--${status}">${(STATUS[status] || STATUS.unpaid).text}</span></div><strong>Froy Bartolome Swimming School</strong><small>R3MG+2X9, Del Monte - Norzagaray Rd, SJDM, Bulacan<br>(+63) 966 203 1918</small></header>` +
            `<div class="sales-receipt-body"><div class="sales-receipt-meta">${meta}</div>` +
            `<p class="sales-receipt-section">${kind}</p>` +
            `<div class="sales-receipt-line"><span>${escapeHtml(payment.course || '--')}<small>${quantityLine}</small></span><strong>${peso(payment.price)}</strong></div>` +
            `<div class="sales-receipt-line"><span>Total paid to date</span><strong>${peso(payment.paid_to_date)}</strong></div>` +
            `<div class="sales-receipt-amounts"><div class="sales-receipt-amount"><small>${IS_ATHLETE ? 'Fee amount' : 'Course price'}</small><strong>${peso(payment.price)}</strong></div><div class="sales-receipt-amount"><small>This payment</small><strong>${peso(payment.amount)}</strong></div><div class="sales-receipt-amount"><small>Balance due</small><strong>${peso(balance)}</strong></div></div>` +
            `<div class="sales-receipt-method">Payment Method: <strong>${escapeHtml(payment.method || '--')}</strong></div>` +
            `<p class="sales-receipt-thanks">${escapeHtml(thanks)}</p><p class="sales-receipt-footer">We look forward to seeing you at the pool</p><p class="sales-receipt-notice">This is a billing invoice, not an official receipt.</p></div>`;

        el.overlay.hidden = false;
    }

    function closeReceipt() {
        el.overlay.hidden = true;
    }

    $('receiptCloseButton').addEventListener('click', closeReceipt);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !el.overlay.hidden) closeReceipt();
    });

    /* ---------- load ---------- */

    async function load() {
        renderTable();

        try {
            const response = await fetch(URL_DATA, { headers: { 'Accept': 'application/json' } });
            let data = {};
            try { data = await response.json(); } catch (e) { /* non-JSON error page */ }

            loading = false;

            if (!response.ok) {
                showToast(data.message || 'Unable to load your payments.', 'danger');
                render();
                return;
            }

            enrollments = data.enrollments || [];
            payments = data.payments || [];
            history = data.history || [];
            athleteSummary = data.summary || null;
            client = data.client || '';

            buildCourseOptions(data.default_enrollment_id);
        } catch (error) {
            console.error(error);
            loading = false;
            showToast('Something went wrong while loading your payments.', 'danger');
        }

        render();
    }

    // Fired by the shared dropdown (assets/js/fbss-dropdown.js) when an option is picked.
    if (el.courseFilter) el.courseFilter.addEventListener('dropdown:change', render);

    el.description.textContent = `Current date: ${formatLongDate(new Date())}`;
    load();

})();
</script>