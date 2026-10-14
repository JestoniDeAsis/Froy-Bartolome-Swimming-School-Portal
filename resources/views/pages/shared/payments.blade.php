@extends('layouts.toast')

@php
    // Athletes (role 4) see the same page without the course filter.
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
    overflow: hidden;
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

@media (max-width: 575.98px) {
    .paymentsContainer { padding: 1rem; }
    .paymentsResultCount { margin-left: 0; width: 100%; }
}

/* ---------- Receipt popup (backdrop = shared .modalOverlay) ---------- */

.paymentsReceipt {
    width: 100%;
    max-width: 480px;
    max-height: 92vh;
    display: flex;
    flex-direction: column;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(16, 24, 40, .18);
    overflow: hidden;
}

.paymentsReceiptHead {
    padding: 1.1rem 1.4rem;
    background: var(--fbss-blue, #0d6efd);
    color: #fff;
}

.paymentsReceiptHeadTop {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    margin-bottom: .9rem;
}

.paymentsReceiptBrand { display: inline-flex; align-items: center; gap: .5rem; font-weight: 700; }
.paymentsReceiptBrand img { border-radius: 50%; }
.paymentsReceiptHead h5 { margin: 0 0 .25rem; font-size: 1rem; font-weight: 700; }
.paymentsReceiptHead p { margin: .1rem 0 0; font-size: .75rem; color: #e6f1f8; }

.paymentsReceiptBody { padding: 1.1rem 1.4rem; overflow-y: auto; }

.paymentsReceiptLine {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .5rem 0;
    border-bottom: 1px solid #eef1f4;
    font-size: .85rem;
}

.paymentsReceiptLine span { color: #667085; }
.paymentsReceiptLine strong { text-align: right; color: #1f2937; }

.paymentsReceiptTotal {
    display: flex;
    justify-content: space-between;
    padding: .7rem 0;
    color: var(--fbss-blue, #0d6efd);
    font-size: 1rem;
    font-weight: 700;
}

.paymentsReceiptAmounts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: .6rem;
    margin: .25rem 0 .75rem;
    padding: .75rem 0;
    border-block: 1px solid #eef1f4;
}

.paymentsReceiptAmounts small { display: block; color: #667085; font-size: .72rem; }
.paymentsReceiptAmounts strong { font-size: .95rem; }

.paymentsReceiptThanks { margin: .9rem 0 0; text-align: center; color: var(--fbss-blue, #0d6efd); font-weight: 700; }
.paymentsReceiptSub { margin: 0; text-align: center; color: #667085; font-size: .78rem; }

.paymentsReceiptActions {
    display: flex;
    justify-content: flex-end;
    gap: .5rem;
    padding: .75rem 1.4rem 1.1rem;
}

/* Print only the receipt */
@media print {
    body * { visibility: hidden !important; }
    #receiptCard, #receiptCard * { visibility: visible !important; }
    #receiptCard { position: fixed; top: 0; left: 0; width: 100%; max-width: none; box-shadow: none; }
    #receiptActions { display: none !important; }
}
</style>


<section class="paymentsContainer" id="paymentsContainer" aria-labelledby="paymentsTitle">

    <header class="paymentsHeader">
        <h2 class="mb-1" id="paymentsTitle">My Payments</h2>
        <p class="text-muted mb-0" id="paymentsDescription"></p>
        <p class="text-muted small mb-0">View only. Payments are recorded by the school when you pay.</p>
    </header>

    <!-- SUMMARY -->
    <div class="paymentsSummary" id="paymentsSummary">
        <div class="paymentsCard">
            <div class="paymentsCardLabel">Course</div>
            <div class="paymentsCardValue" id="summaryCourse">--</div>
        </div>
        <div class="paymentsCard">
            <div class="paymentsCardLabel">Total Fee</div>
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

</section>


<!-- RECEIPT -->
<div class="modalOverlay" id="receiptOverlay" hidden>
    <div class="paymentsReceipt" id="receiptCard" role="dialog" aria-modal="true" aria-labelledby="receiptTitle">

        <div class="paymentsReceiptHead">
            <div class="paymentsReceiptHeadTop">
                <span class="paymentsReceiptBrand">
                    <img src="{{ asset('assets/images/icon_fbss.png') }}" alt="" width="28" height="28">
                    FBSS SwimOS
                </span>
                <span class="paymentsPill" id="receiptStatus">Paid</span>
            </div>
            <h5 id="receiptTitle">Froy Bartolome Swimming School</h5>
            <p>R3MG+2X9, Del Monte - Norzagaray Rd, SJDM, Bulacan</p>
            <p>(+63) 966 203 1918</p>
        </div>

        <div class="paymentsReceiptBody">
            <div class="paymentsReceiptLine"><span>Receipt #</span><strong id="receiptNumber">--</strong></div>
            <div class="paymentsReceiptLine"><span>Date</span><strong id="receiptDate">--</strong></div>
            <div class="paymentsReceiptLine"><span>Client</span><strong id="receiptClient">--</strong></div>
            <div class="paymentsReceiptLine"><span>Course</span><strong id="receiptCourse">--</strong></div>
            <div class="paymentsReceiptLine"><span>Received by</span><strong id="receiptReceivedBy">--</strong></div>

            <div class="paymentsReceiptTotal"><span>Course Fee</span><span id="receiptFee">--</span></div>

            <div class="paymentsReceiptAmounts">
                <div><small>This Payment</small><strong id="receiptAmount">--</strong></div>
                <div><small>Paid To Date</small><strong id="receiptPaidToDate">--</strong></div>
                <div><small>Balance Due</small><strong id="receiptBalance">--</strong></div>
            </div>

            <div class="paymentsReceiptLine">
                <span>Payment Method</span><strong id="receiptMethod">--</strong>
            </div>
            <div class="paymentsReceiptLine">
                <span>Reference #</span><strong id="receiptReference">--</strong>
            </div>

            <p class="paymentsReceiptThanks">Thank You for Your Payment!</p>
            <p class="paymentsReceiptSub">We look forward to seeing you at the pool!</p>
        </div>

        <div class="paymentsReceiptActions" id="receiptActions">
            <button class="btn btn-primary btn-sm" id="receiptPrintButton" type="button"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print</button>
            <button class="btn btn-outline-secondary btn-sm" id="receiptCloseButton" type="button">Close</button>
        </div>

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

    // Athletes have no Course column to filter by, so it is left out of the table too.
    const HEADERS = IS_ATHLETE
        ? ['Date', 'Receipt No.', 'Amount', 'Method', 'Reference No.', 'Balance After', 'Status', 'Receipt']
        : ['Date', 'Receipt No.', 'Course', 'Amount', 'Method', 'Reference No.', 'Balance After', 'Status', 'Receipt'];

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
        overlay: $('receiptOverlay')
    };

    let enrollments = [];
    let payments = [];
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
                attributes: { type: 'button', 'aria-label': `View receipt ${payment.receipt}` }
            });
            view.append(make('i', { className: 'bi bi-eye', attributes: { 'aria-hidden': 'true' } }));
            view.addEventListener('click', () => openReceipt(payment));

            const cells = [
                cell(dateLabel(payment.date)),
                cell(payment.receipt),
                ...(IS_ATHLETE ? [] : [cell(payment.course)]),
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

    function render() {
        renderSummary();
        renderTable();
    }

    /* ---------- receipt ---------- */

    function openReceipt(payment) {
        const info = STATUS[payment.status] || STATUS.unpaid;

        $('receiptStatus').className = `paymentsPill ${info.cls}`;
        $('receiptStatus').textContent = info.text;

        $('receiptNumber').textContent = payment.receipt;
        $('receiptDate').textContent = dateLabel(payment.date) || '--';
        $('receiptClient').textContent = client || '--';
        $('receiptCourse').textContent = payment.course || '--';
        $('receiptReceivedBy').textContent = payment.received_by || '--';

        $('receiptFee').textContent = peso(payment.price);
        $('receiptAmount').textContent = peso(payment.amount);
        $('receiptPaidToDate').textContent = peso(payment.paid_to_date);
        $('receiptBalance').textContent = peso(payment.balance);

        $('receiptMethod').textContent = payment.method || '--';
        $('receiptReference').textContent = payment.reference || '--';

        el.overlay.hidden = false;
    }

    function closeReceipt() {
        el.overlay.hidden = true;
    }

    $('receiptCloseButton').addEventListener('click', closeReceipt);
    $('receiptPrintButton').addEventListener('click', () => window.print());
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