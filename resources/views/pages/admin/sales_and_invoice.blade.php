@extends('layouts.toast')

<style>
.sales-page, .sales-dialog {
    --sales-blue: var(--fbss-blue, #0D6EFD);
    --sales-blue-dark: var(--fbss-blue-hover, #0B5ED7);
    --sales-ink: #16202c;
    --sales-muted: #64748b;
    --sales-line: #dce5ed;
    --sales-canvas: #f4f7f6;
}

/* =========================================================
   Page frame + components — deliberately the same values as the
   Attendance / Registers / Graduation pages, so every admin page
   looks alike (header, stat cards, tabs, search, table, modals).
========================================================= */
.sales-page {
    width: 100%;
    min-width: 0;
    height: 100vh;
    min-height: 0;
    padding: 1.25rem;
    box-sizing: border-box;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    color: var(--sales-ink);
}

.sales-heading {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
}

.sales-tabs { flex: 0 0 auto; margin-bottom: 1rem; }
.sales-tabs .btn-tab { min-width: 150px; }

.sales-stats { flex: 0 0 auto; margin-bottom: 1rem; }
.sales-stats[hidden] { display: none; }

.sales-stat {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: .85rem 1.1rem;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
}

.sales-stat-label { margin: 0 0 .2rem; font-size: .72rem; font-weight: 600; text-transform: uppercase; color: #667085; }
.sales-stat-value { margin: 0; font-size: 1.45rem; font-weight: 700; line-height: 1; }

.sales-stat-icon--blue  { background: #e7f1ff; color: #0d6efd; }
.sales-stat-icon--green { background: #e6f7ec; color: #1a7f3c; }
.sales-stat-icon--amber { background: #fff4e0; color: #b26a00; }
.sales-stat-icon--red   { background: #fdebec; color: #c22b3a; }

.sales-toolbar { flex: 0 0 auto; margin-bottom: .75rem; }

.sales-search { position: relative; width: min(260px, 100%); }
.sales-search input { height: 40px; box-sizing: border-box; border-radius: 20px; padding-left: 2.25rem; }

.sales-panel { flex: 0 1 auto; min-height: 0; display: flex; flex-direction: column; }
.sales-panel[hidden] { display: none; }
.sales-field[hidden] { display: none; }

/* Searchable picker (student / athlete): type to filter, click or press Enter to choose.
   The list opens inline under the box so the dialog never clips it. */
.sales-picker { position: relative; }
.sales-picker.isInvalid .sales-picker-input { border-color: var(--fbss-danger, #DC3545); }
.sales-picker-box { position: relative; }
.sales-picker-input { padding-right: 2.4rem; }
.sales-picker-clear { position: absolute; right: .35rem; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; display: grid; place-items: center; border: 0; border-radius: 50%; background: transparent; color: var(--sales-muted); }
.sales-picker-clear:hover { background: var(--sales-canvas); color: var(--sales-ink); }
.sales-picker-clear[hidden] { display: none; }
.sales-picker-list { margin-top: .35rem; max-height: min(232px, 38dvh); overflow-y: auto; border: 1px solid var(--sales-line); border-radius: 10px; background: #fff; padding: .25rem; }
.sales-picker-list[hidden] { display: none; }
.sales-picker-option { display: flex; align-items: center; justify-content: space-between; gap: .75rem; width: 100%; padding: .5rem .65rem; border: 0; border-radius: 8px; background: transparent; text-align: left; color: var(--sales-ink); }
@media (pointer: coarse) { .sales-picker-option { min-height: 48px; } .sales-picker-input { font-size: 16px; } }  /* 16px stops iOS zooming on focus */
.sales-picker-option:hover, .sales-picker-option.active { background: var(--sales-canvas); }
.sales-picker-main { display: flex; flex-direction: column; min-width: 0; }
.sales-picker-main strong, .sales-picker-main small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sales-picker-main small { color: var(--sales-muted); font-size: .75rem; }
.sales-picker-right { flex: 0 0 auto; font-size: .78rem; font-weight: 600; color: var(--sales-muted); }
.sales-picker-empty, .sales-picker-more { margin: 0; padding: .6rem .65rem; font-size: .78rem; color: var(--sales-muted); }
.sales-check { display: flex; align-items: center; gap: .5rem; font-size: .8rem; font-weight: 600; }

.sales-table-wrap {
    --sales-row-height: 49px;
    --sales-header-height: 49px;
    --sales-visible-rows: 8;

    flex: 0 1 auto;
    min-height: 0;
    max-height: calc(var(--sales-header-height) + (var(--sales-row-height) * var(--sales-visible-rows)));
    width: 100%;
    overflow: auto;
    background: #fff;
    border: 1px solid #e7ebef;
    border-radius: 12px;
    box-sizing: border-box;
}

.sales-table {
    width: 100%;
    margin: 0;
    white-space: nowrap;
    border-collapse: separate;
    border-spacing: 0;
}

.sales-table thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    background: #f8fafb;
    color: #667085;
    font-weight: 600;
    border-bottom: 1px solid #e7ebef;
    box-shadow: 0 1px 0 #e7ebef;
}

.sales-table th,
.sales-table td { padding: .75rem 1rem; vertical-align: middle; text-align: left; }

.sales-table tbody tr { transition: background-color .1s ease; }
.sales-table tbody tr:hover { background: #f8fafb; }

/* Modals: same card as the other pages' modals. */
.sales-dialog {
    width: min(500px, calc(100vw - 2rem));
    max-height: min(90vh, 850px);
    padding: 0;
    border: 0;
    border-radius: 12px;
    box-shadow: 0 20px 40px rgba(16, 24, 40, .18);
    background: #fff;
    color: var(--sales-ink);
    color-scheme: light;
}

.sales-dialog::backdrop { background: rgba(0, 0, 0, .4); }

.sales-dialog-inner { padding: 1.5rem 2rem; }

@media (max-width: 575.98px) {
    .sales-dialog-inner { padding: 1.25rem; }
}

.sales-page *, .sales-page *::before, .sales-page *::after {
    box-sizing: border-box;
}

.sales-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}

.sales-stat-icon {
    width: 2rem;
    height: 2rem;
    flex: 0 0 2rem;
    display: grid;
    place-items: center;
    border-radius: 9px;
    font-size: 1rem;
}

.sales-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: .75rem;
    flex-wrap: wrap;
}

.sales-toolbar-start {
    display: flex;
    align-items: center;
    gap: .6rem;
    flex-wrap: wrap;
}

.sales-search i {
    position: absolute;
    left: .8rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--sales-muted);
    pointer-events: none;
}

/* Dropdowns use the shared defaults (our_theme.css), same as every other page.
   Only the filter's minimum width is set, matching Attendance's filters. */
.sales-filter { --dd-min-width: 160px; }
/* Item-log filters sit inline with the other toolbar controls. */
.sales-item-filters { display: contents; }
.sales-item-filters[hidden] { display: none; }

.sales-table .sales-name {
    font-weight: 600;
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

.sales-reason {
    display: block;
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--sales-muted);
    font-size: .72rem;
}

.sales-receipt-notice {
    margin: .5rem 0 0;
    text-align: center;
    color: var(--sales-muted);
    font-size: .66rem;
}

.sales-empty {
    padding: 2rem !important;
    color: var(--sales-muted);
    text-align: center !important;
    white-space: normal;
}

.sales-dialog h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.sales-dialog-subtitle {
    margin: .3rem 0 1rem;
    color: var(--sales-muted);
    font-size: .78rem;
}

.sales-form {
    display: grid;
    gap: .75rem;
}

.sales-field {
    display: grid;
    gap: .35rem;
}

.sales-field label {
    font-size: .8rem;
    font-weight: 600;
}

.sales-field small {
    color: var(--sales-muted);
    font-size: .76rem;
}

.sales-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .7rem;
}

.sales-dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: .55rem;
    margin-top: 1rem;
}

.sales-required {
    color: var(--fbss-danger, #DC3545);
}

.sales-field .registerDropdown.isInvalid .registerDropdownToggle {
    border-color: var(--fbss-danger, #DC3545);
}

.sales-field-error {
    color: var(--fbss-danger, #DC3545);
    font-size: .76rem;
    font-weight: 600;
}

.sales-balance-line {
    display: flex;
    justify-content: space-between;
    font-size: .83rem;
}

.sales-receipt-dialog {
    width: min(465px, calc(100vw - 1.25rem));
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

@media (max-width: 1050px) {
    .sales-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

}

@media (max-width: 700px) {
    .sales-page {
        padding: 1rem;
    }

    .sales-heading {
        align-items: flex-start;
    }

    .sales-toolbar, .sales-toolbar-start, .sales-search {
        width: 100%;
    }

    .sales-toolbar-start {
        align-items: stretch;
    }

    .sales-filter {
        flex: 1;
        --dd-width: 100%;
    }

    .sales-form-grid {
        grid-template-columns: 1fr;
    }

}

@media (max-width: 430px) {
    .sales-stats {
        grid-template-columns: 1fr 1fr;
        gap: .5rem;
    }

    .sales-stat {
        min-height: 76px;
        padding: .7rem;
    }

    .sales-stat-value {
        font-size: 1.25rem;
    }

    .sales-stat-label {
        font-size: .62rem;
    }

    .sales-stat-icon {
        width: 1.6rem;
        height: 1.6rem;
        flex-basis: 1.6rem;
    }

    .sales-receipt-amount {
        padding: .4rem;
    }

}
</style>

<section class="sales-page" id="salesPage" aria-labelledby="salesTitle">
    <header class="sales-heading">
        <div>
            <h2 class="mb-1" id="salesTitle">Billing &amp; Invoices</h2>
            <p class="text-muted mb-0">Course payments, item sales, and invoices in one place</p>
        </div>
        <button class="btn btn-primary btn-icon-label" id="salesAddButton" type="button"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add New Purchase</span></button>
    </header>

    <nav class="btn-tab-group sales-tabs" aria-label="Billing sections" role="tablist">
        <button class="btn btn-outline-primary btn-tab" id="salesTabAll" type="button" role="tab" aria-selected="false" aria-controls="salesPanelAll" data-sales-tab="all">All</button>
        <button class="btn btn-outline-primary btn-tab active" id="salesTabCourse" type="button" role="tab" aria-selected="true" aria-controls="salesPanelCourse" data-sales-tab="course">Course payment</button>
        <button class="btn btn-outline-primary btn-tab" id="salesTabAthletes" type="button" role="tab" aria-selected="false" aria-controls="salesPanelAthletes" data-sales-tab="athletes">Athlete payments</button>
        <button class="btn btn-outline-primary btn-tab" id="salesTabFees" type="button" role="tab" aria-selected="false" aria-controls="salesPanelFees" data-sales-tab="fees">Athlete fees</button>
        <button class="btn btn-outline-primary btn-tab" id="salesTabLogs" type="button" role="tab" aria-selected="false" aria-controls="salesPanelLogs" data-sales-tab="logs">Item logs</button>
        <button class="btn btn-outline-primary btn-tab" id="salesTabItems" type="button" role="tab" aria-selected="false" aria-controls="salesPanelItems" data-sales-tab="items">Items</button>
    </nav>

    <div class="sales-stats" id="salesStats" aria-label="Payment totals">
        <article class="sales-stat"><div><p class="sales-stat-label">Total collected</p><p class="sales-stat-value" id="salesTotalCollected">₱0</p></div><span class="sales-stat-icon sales-stat-icon--blue"><i class="bi bi-cash-stack"></i></span></article>
        <article class="sales-stat"><div><p class="sales-stat-label">Outstanding</p><p class="sales-stat-value" id="salesOutstanding">₱0</p></div><span class="sales-stat-icon sales-stat-icon--amber"><i class="bi bi-hourglass-split"></i></span></article>
        <article class="sales-stat"><div><p class="sales-stat-label">Paid</p><p class="sales-stat-value" id="salesPaid">₱0</p></div><span class="sales-stat-icon sales-stat-icon--green"><i class="bi bi-check-circle"></i></span></article>
        <article class="sales-stat"><div><p class="sales-stat-label">Unpaid</p><p class="sales-stat-value" id="salesUnpaid">₱0</p></div><span class="sales-stat-icon sales-stat-icon--red"><i class="bi bi-x-circle"></i></span></article>
    </div>

    <div class="sales-toolbar">
        <div class="sales-toolbar-start">
            <label class="sales-search"><i class="bi bi-search" aria-hidden="true"></i><input class="form-control" id="salesSearch" type="search" placeholder="Search" autocomplete="off" aria-label="Search rows"></label>
            <div class="registerDropdown sales-filter" id="salesStatusFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="salesStatusFilterToggle" type="button" aria-expanded="false" aria-controls="salesStatusFilterOptions" aria-label="Filter by payment status">
                    <span>All Status</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>
                <div class="registerDropdownOptions" id="salesStatusFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All Status</button>
                    <button class="registerDropdownOption" type="button" data-value="paid">Paid</button>
                    <button class="registerDropdownOption" type="button" data-value="unpaid">Unpaid</button>
                    <button class="registerDropdownOption" type="button" data-value="partial">Partial</button>
                </div>
                <input id="salesStatusFilter" name="statusFilter" type="hidden" value="">
            </div>
            <div class="registerDropdown sales-filter" id="salesRecordFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="salesRecordFilterToggle" type="button" aria-expanded="false" aria-controls="salesRecordFilterOptions" aria-label="Show active or archived payments">
                    <span>Active</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>
                <div class="registerDropdownOptions" id="salesRecordFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="active">Active</button>
                    <button class="registerDropdownOption" type="button" data-value="archived">Archived</button>
                </div>
                <input id="salesRecordFilter" name="recordFilter" type="hidden" value="active">
            </div>
            <div class="sales-item-filters" id="salesItemFilters" hidden>
            <div class="registerDropdown sales-filter" id="itemMonthFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="itemMonthFilterToggle" type="button" aria-expanded="false" aria-controls="itemMonthFilterOptions" aria-label="Filter by month">
                    <span>All months</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>
                <div class="registerDropdownOptions" id="itemMonthFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All months</button>
                </div>
                <input id="itemMonthFilter" name="monthFilter" type="hidden" value="">
            </div>
            <div class="registerDropdown sales-filter" id="itemNameFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="itemNameFilterToggle" type="button" aria-expanded="false" aria-controls="itemNameFilterOptions" aria-label="Filter by item">
                    <span>All items</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>
                <div class="registerDropdownOptions" id="itemNameFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All items</button>
                </div>
                <input id="itemNameFilter" name="itemFilter" type="hidden" value="">
            </div>
            <div class="registerDropdown sales-filter" id="itemMethodFilterDropdown" data-fbss-dropdown>
                <button class="registerDropdownToggle" id="itemMethodFilterToggle" type="button" aria-expanded="false" aria-controls="itemMethodFilterOptions" aria-label="Filter by payment method">
                    <span>All methods</span>
                    <i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i>
                </button>
                <div class="registerDropdownOptions" id="itemMethodFilterOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">All methods</button>
                </div>
                <input id="itemMethodFilter" name="methodFilter" type="hidden" value="">
            </div>
            <button class="btn btn-outline-primary" id="itemFilterClear" type="button" hidden>Clear filters</button>
            </div>
        </div>
    </div>

    <section class="sales-panel" id="salesPanelAll" role="tabpanel" aria-labelledby="salesTabAll" data-sales-panel="all" hidden>
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Type</th><th>Name</th><th>Client</th><th>Total</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Invoice</th></tr></thead><tbody id="allRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelCourse" role="tabpanel" aria-labelledby="salesTabCourse" data-sales-panel="course">
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Course</th><th>Name</th><th>Price</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Updated</th><th>Actions</th><th>Invoice</th></tr></thead><tbody id="coursePaymentRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelAthletes" role="tabpanel" aria-labelledby="salesTabAthletes" data-sales-panel="athletes" hidden>
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Fee</th><th>Athlete</th><th>Period</th><th>Amount</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Updated</th><th>Actions</th><th>Invoice</th></tr></thead><tbody id="athletePaymentRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelFees" role="tabpanel" aria-labelledby="salesTabFees" data-sales-panel="fees" hidden>
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Fee name</th><th>Description</th><th>Amount</th><th>Billing</th><th>Status</th><th>Actions</th></tr></thead><tbody id="athleteFeeRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelLogs" role="tabpanel" aria-labelledby="salesTabLogs" data-sales-panel="logs" hidden>
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Item name</th><th>Description</th><th>Quantity</th><th>Price</th><th>To pay</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Actions</th><th>Invoice</th></tr></thead><tbody id="itemLogRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelItems" role="tabpanel" aria-labelledby="salesTabItems" data-sales-panel="items" hidden>
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Item name</th><th>Description</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody id="itemCatalogRows"></tbody></table></div>
    </section>
</section>

<dialog class="sales-dialog" id="coursePaymentDialog" aria-labelledby="coursePaymentTitle">
    <div class="sales-dialog-inner">
        <h2 id="coursePaymentTitle">Updating course payment</h2><p class="sales-dialog-subtitle" id="coursePaymentStudent"></p>
        <form class="sales-form" id="coursePaymentForm">
            <input type="hidden" name="id">
            <input type="hidden" name="paymentId">
            <input type="hidden" name="relog" value="">
            <div class="sales-field" id="coursePaymentEnrollmentGroup" hidden><label for="coursePaymentEnrollmentToggle">Student <span class="sales-required">*</span></label><div class="sales-picker ddBlock" id="coursePaymentEnrollmentDropdown"><div class="sales-picker-box"><input class="form-control sales-picker-input" id="coursePaymentEnrollmentToggle" type="text" role="combobox" aria-expanded="false" aria-controls="coursePaymentEnrollmentOptions" aria-label="Search student" placeholder="Search student or course" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="search" enterkeyhint="search"><button class="sales-picker-clear" type="button" aria-label="Clear selection" title="Clear" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button></div><div class="sales-picker-list" id="coursePaymentEnrollmentOptions" role="listbox" hidden></div><input id="coursePaymentEnrollment" type="hidden" value=""></div></div>
            <div class="sales-balance-line"><strong>Balance</strong><strong id="coursePaymentBalance">₱0</strong></div>
            <div class="sales-field"><label for="coursePaymentAmount">Payment <span class="sales-required">*</span></label><input class="form-control" id="coursePaymentAmount" name="amount" type="number" min="0.01" step="0.01" placeholder="₱2,000" required><small id="coursePaymentLimit"></small><small class="sales-field-error" id="coursePaymentError" hidden></small></div>
            <div class="sales-field"><label for="coursePaymentMethodToggle">Payment Method <span class="sales-required">*</span></label><div class="registerDropdown ddBlock" id="coursePaymentMethodDropdown" data-fbss-dropdown><button class="registerDropdownToggle" id="coursePaymentMethodToggle" type="button" aria-expanded="false" aria-controls="coursePaymentMethodOptions"><span>Select method</span><i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i></button><div class="registerDropdownOptions" id="coursePaymentMethodOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">Select method</button>
                    <button class="registerDropdownOption" type="button" data-value="Cash">Cash</button>
                    <button class="registerDropdownOption" type="button" data-value="GCash">GCash</button>
                    <button class="registerDropdownOption" type="button" data-value="Bank Transfer">Bank Transfer</button>
                    <button class="registerDropdownOption" type="button" data-value="Other">Other</button>
                </div><input id="coursePaymentMethod" name="method" type="hidden" value=""></div></div>
            <div class="sales-field"><label for="coursePaymentReference">Reference Number</label><input class="form-control" id="coursePaymentReference" name="reference" type="text" maxlength="100" placeholder="e.g. GCash reference number"></div>
            <div class="sales-field" id="coursePaymentReasonGroup" hidden><label for="coursePaymentReason">Reason for change <span class="sales-required">*</span></label><input class="form-control" id="coursePaymentReason" name="reason" type="text" maxlength="255" placeholder="e.g. Wrong amount typed"><small>Saving archives the original entry (it stays on file) and records a corrected one.</small></div>
            <div class="sales-balance-line"><strong>New payment status</strong><span class="sales-badge" id="coursePaymentStatus"></span></div>
            <div class="sales-dialog-actions"><button class="btn btn-outline-primary" type="button" data-dialog-close>Cancel</button><button class="btn btn-primary" type="submit" id="coursePaymentSubmit">Update</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog" id="purchaseDialog" aria-labelledby="purchaseDialogTitle">
    <div class="sales-dialog-inner">
        <h2 id="purchaseDialogTitle">Adding New Purchase</h2><p class="sales-dialog-subtitle" id="purchaseDialogSubtitle">New Purchase</p>
        <form class="sales-form" id="purchaseForm">
            <input type="hidden" name="id">
            <div class="sales-field"><label for="purchaseItemToggle">Item *</label><div class="registerDropdown ddBlock" id="purchaseItemDropdown" data-fbss-dropdown><button class="registerDropdownToggle" id="purchaseItemToggle" type="button" aria-expanded="false" aria-controls="purchaseItemOptions"><span>Select an item</span><i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i></button><div class="registerDropdownOptions" id="purchaseItemOptions" hidden>

                </div><input id="purchaseItem" name="itemId" type="hidden" value=""></div></div>
            <div class="sales-form-grid"><div class="sales-field"><label for="purchaseQuantity">Quantity *</label><input class="form-control" id="purchaseQuantity" name="quantity" type="number" min="1" step="1" value="1" required></div><div class="sales-field"><label for="purchaseUnitPrice">Price</label><input class="form-control" id="purchaseUnitPrice" type="text" readonly></div></div>
            <div class="sales-balance-line"><strong>To Pay</strong><strong id="purchaseToPay">₱0</strong></div>
            <div class="sales-form-grid"><div class="sales-field"><label for="purchasePaid">Payment *</label><input class="form-control" id="purchasePaid" name="paid" type="number" min="0" step="0.01" value="0" required></div><div class="sales-field"><label for="purchaseMethodToggle">Mode of Payment *</label><div class="registerDropdown ddBlock" id="purchaseMethodDropdown" data-fbss-dropdown><button class="registerDropdownToggle" id="purchaseMethodToggle" type="button" aria-expanded="false" aria-controls="purchaseMethodOptions"><span>Cash</span><i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i></button><div class="registerDropdownOptions" id="purchaseMethodOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="Cash">Cash</button>
                    <button class="registerDropdownOption" type="button" data-value="GCash">GCash</button>
                    <button class="registerDropdownOption" type="button" data-value="Bank Transfer">Bank Transfer</button>
                </div><input id="purchaseMethod" name="method" type="hidden" value="Cash"></div></div></div>
            <div class="sales-field"><label for="purchaseDescription">Description</label><input class="form-control" id="purchaseDescription" name="description" maxlength="255" placeholder="Color and stuff"></div>
            <div class="sales-dialog-actions"><button class="btn btn-outline-primary" type="button" data-dialog-close>Cancel</button><button class="btn btn-primary" type="submit" id="purchaseSubmit">Add</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog" id="itemDialog" aria-labelledby="itemDialogTitle">
    <div class="sales-dialog-inner">
        <h2 id="itemDialogTitle">Adding New Item</h2><p class="sales-dialog-subtitle" id="itemDialogSubtitle">New Item</p>
        <form class="sales-form" id="itemForm">
            <input type="hidden" name="id">
            <div class="sales-field"><label for="catalogItemName">Item name *</label><input class="form-control" id="catalogItemName" name="name" maxlength="150" required></div>
            <div class="sales-field"><label for="catalogItemDescription">Description</label><input class="form-control" id="catalogItemDescription" name="description" maxlength="255"></div>
            <div class="sales-form-grid"><div class="sales-field"><label for="catalogItemPrice">Price *</label><input class="form-control" id="catalogItemPrice" name="price" type="number" min="0" step="0.01" required></div><div class="sales-field"><label for="catalogItemStock">Stock *</label><input class="form-control" id="catalogItemStock" name="stock" type="number" min="0" step="1" required></div></div>
            <div class="sales-dialog-actions"><button class="btn btn-outline-primary" type="button" data-dialog-close>Cancel</button><button class="btn btn-primary" type="submit" id="itemSubmit">Add</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog" id="athletePaymentDialog" aria-labelledby="athletePaymentTitle">
    <div class="sales-dialog-inner">
        <h2 id="athletePaymentTitle">Recording athlete payment</h2><p class="sales-dialog-subtitle" id="athletePaymentSubtitle"></p>
        <form class="sales-form" id="athletePaymentForm">
            <input type="hidden" name="paymentId">
            <input type="hidden" name="userId">
            <input type="hidden" name="feeId">
            <input type="hidden" name="periodKey">
            <input type="hidden" name="relog" value="">
            <div class="sales-field" id="athletePaymentDueGroup" hidden><label for="athletePaymentDueToggle">Athlete &amp; fee <span class="sales-required">*</span></label><div class="sales-picker ddBlock" id="athletePaymentDueDropdown"><div class="sales-picker-box"><input class="form-control sales-picker-input" id="athletePaymentDueToggle" type="text" role="combobox" aria-expanded="false" aria-controls="athletePaymentDueOptions" aria-label="Search athlete" placeholder="Search athlete or fee" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" inputmode="search" enterkeyhint="search"><button class="sales-picker-clear" type="button" aria-label="Clear selection" title="Clear" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button></div><div class="sales-picker-list" id="athletePaymentDueOptions" role="listbox" hidden></div><input id="athletePaymentDue" type="hidden" value=""></div></div>
            <div class="sales-balance-line"><strong>Balance</strong><strong id="athletePaymentBalance">₱0</strong></div>
            <div class="sales-field"><label for="athletePaymentAmount">Payment <span class="sales-required">*</span></label><input class="form-control" id="athletePaymentAmount" name="amount" type="number" min="0.01" step="0.01" placeholder="₱500" required><small id="athletePaymentLimit"></small><small class="sales-field-error" id="athletePaymentError" hidden></small></div>
            <div class="sales-field"><label for="athletePaymentMethodToggle">Payment Method <span class="sales-required">*</span></label><div class="registerDropdown ddBlock" id="athletePaymentMethodDropdown" data-fbss-dropdown><button class="registerDropdownToggle" id="athletePaymentMethodToggle" type="button" aria-expanded="false" aria-controls="athletePaymentMethodOptions"><span>Select method</span><i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i></button><div class="registerDropdownOptions" id="athletePaymentMethodOptions" hidden>
                    <button class="registerDropdownOption active" type="button" data-value="">Select method</button>
                    <button class="registerDropdownOption" type="button" data-value="Cash">Cash</button>
                    <button class="registerDropdownOption" type="button" data-value="GCash">GCash</button>
                    <button class="registerDropdownOption" type="button" data-value="Bank Transfer">Bank Transfer</button>
                    <button class="registerDropdownOption" type="button" data-value="Other">Other</button>
                </div><input id="athletePaymentMethod" name="method" type="hidden" value=""></div></div>
            <div class="sales-field"><label for="athletePaymentReference">Reference Number</label><input class="form-control" id="athletePaymentReference" name="reference" type="text" maxlength="100" placeholder="e.g. GCash reference number"></div>
            <div class="sales-field" id="athletePaymentReasonGroup" hidden><label for="athletePaymentReason">Reason for change <span class="sales-required">*</span></label><input class="form-control" id="athletePaymentReason" name="reason" type="text" maxlength="255" placeholder="e.g. Wrong amount typed"><small>Saving archives the original entry (it stays on file) and records a corrected one.</small></div>
            <div class="sales-balance-line"><strong>New payment status</strong><span class="sales-badge" id="athletePaymentStatus"></span></div>
            <div class="sales-dialog-actions"><button class="btn btn-outline-primary" type="button" data-dialog-close>Cancel</button><button class="btn btn-primary" type="submit" id="athletePaymentSubmit">Record</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog" id="feeDialog" aria-labelledby="feeDialogTitle">
    <div class="sales-dialog-inner">
        <h2 id="feeDialogTitle">Adding Athlete Fee</h2><p class="sales-dialog-subtitle" id="feeDialogSubtitle">What athletes need to pay</p>
        <form class="sales-form" id="feeForm">
            <input type="hidden" name="id">
            <div class="sales-field"><label for="feeName">Fee name *</label><input class="form-control" id="feeName" name="name" maxlength="100" placeholder="e.g. Monthly training fee" required></div>
            <div class="sales-field"><label for="feeDescription">Description</label><input class="form-control" id="feeDescription" name="description" maxlength="255"></div>
            <div class="sales-form-grid">
                <div class="sales-field"><label for="feeAmount">Amount *</label><input class="form-control" id="feeAmount" name="amount" type="number" min="0.01" step="0.01" required></div>
                <div class="sales-field"><label for="feeCycleToggle">Billing *</label><div class="registerDropdown ddBlock" id="feeCycleDropdown" data-fbss-dropdown><button class="registerDropdownToggle" id="feeCycleToggle" type="button" aria-expanded="false" aria-controls="feeCycleOptions"><span>Monthly</span><i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i></button><div class="registerDropdownOptions" id="feeCycleOptions" hidden>
                    <button class="registerDropdownOption" type="button" data-value="one_time">One-time</button>
                    <button class="registerDropdownOption active" type="button" data-value="monthly">Monthly</button>
                    <button class="registerDropdownOption" type="button" data-value="yearly">Yearly</button>
                </div><input id="feeCycle" name="cycle" type="hidden" value="monthly"></div></div>
            </div>
            <label class="sales-check"><input type="checkbox" name="active" checked> Active (every athlete owes it each period)</label>
            <div class="sales-field"><small>Changing the amount only affects dues nobody has paid yet. Dues that already took a payment keep their old amount.</small></div>
            <div class="sales-dialog-actions"><button class="btn btn-outline-primary" type="button" data-dialog-close>Cancel</button><button class="btn btn-primary" type="submit" id="feeSubmit">Add</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog sales-receipt-dialog" id="receiptDialog" aria-label="Invoice">
    <article class="sales-receipt" id="receiptContent"></article>
    <div class="sales-receipt-actions"><button class="btn btn-outline-secondary btn-sm" type="button" data-dialog-close>Close</button></div>
</dialog>

<dialog class="sales-dialog" id="confirmDialog" aria-labelledby="confirmTitle">
    <div class="sales-dialog-inner"><h2 id="confirmTitle">Remove this record?</h2><p class="sales-dialog-subtitle" id="confirmMessage">This action only changes this preview until the page is reloaded.</p><div class="sales-field" id="confirmReasonGroup" hidden><label for="confirmReason">Reason <span class="sales-required">*</span></label><input class="form-control" id="confirmReason" type="text" maxlength="255" placeholder="Why is this being archived?"></div><div class="sales-dialog-actions"><button class="btn btn-outline-primary" type="button" data-dialog-close>Cancel</button><button class="btn btn-danger" type="button" id="confirmDelete">Remove</button></div></div>
</dialog>

<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    const page = document.getElementById('salesPage');
    if (!page) return;

    // Filled by loadData() from GET /sales/data (SalesInvoiceController).
    let courseRows = [];
    let archivedCourseRows = [];
    let archivedAthleteRows = [];
    let items = [];
    let logs = [];
    let athleteRows = [];
    let athleteFees = [];
    let activeTab = 'course';
    let pendingRemoval = null;
    let pendingOptions = {};
    const money = value => `₱${Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 })}`;
    const nowStamp = () => { const d = new Date(), p = n => String(n).padStart(2, '0'); return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`; };
    const dateLabel = () => {
        const date = new Date();
        const month = date.toLocaleString('en-US', { month: 'short' }).toUpperCase();
        return `${month}-${String(date.getDate()).padStart(2, '0')}-${date.getFullYear()}`;
    };
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
    const statusFor = (paid, total) => Number(paid) <= 0 ? 'unpaid' : Number(paid) >= Number(total) ? 'paid' : 'partial';
    const statusLabel = status => ({ paid: 'Paid', unpaid: 'Unpaid', partial: 'Partial', archived: 'Archived' }[status] || 'Unpaid');
    const archivedNote = row => `Archived ${escapeHtml(row.archivedAt || '--')}${row.archivedBy ? ` by ${escapeHtml(row.archivedBy)}` : ''}${row.archiveReason ? `<span class="sales-reason" title="${escapeHtml(row.archiveReason)}">${escapeHtml(row.archiveReason)}</span>` : ''}`;
    const badge = status => `<span class="sales-badge sales-badge--${status}">${statusLabel(status)}</span>`;
    const byId = (list, id) => list.find(row => Number(row.id) === Number(id));
    const search = document.getElementById('salesSearch');
    const statusFilter = document.getElementById('salesStatusFilter');            // hidden input holding the value
    const statusFilterRoot = document.getElementById('salesStatusFilterDropdown'); // the shared dropdown
    const recordFilter = document.getElementById('salesRecordFilter');             // 'active' | 'archived' (course + athlete payment tabs)
    const recordFilterRoot = document.getElementById('salesRecordFilterDropdown');
    // Item logs tab filters (month / item / payment method). Month is read from sortAt ("YYYY-MM-DD hh:mm:ss").
    const itemFiltersRoot = document.getElementById('salesItemFilters');
    const itemMonthRoot = document.getElementById('itemMonthFilterDropdown');
    const itemNameRoot = document.getElementById('itemNameFilterDropdown');
    const itemMethodRoot = document.getElementById('itemMethodFilterDropdown');
    const itemMonthFilter = document.getElementById('itemMonthFilter');
    const itemNameFilter = document.getElementById('itemNameFilter');
    const itemMethodFilter = document.getElementById('itemMethodFilter');
    const itemFilterClear = document.getElementById('itemFilterClear');
    const monthKey = row => String(row.sortAt || '').slice(0, 7);
    const monthLabel = key => { const [y, m] = key.split('-').map(Number); return new Date(y, m - 1, 1).toLocaleString('en-US', { month: 'long', year: 'numeric' }); };
    const itemFiltersActive = () => Boolean(itemMonthFilter.value || itemNameFilter.value || itemMethodFilter.value);
    const matchesItemFilters = row =>
        (!itemMonthFilter.value || monthKey(row) === itemMonthFilter.value)
        && (!itemNameFilter.value || String(row.itemId) === itemNameFilter.value)
        && (!itemMethodFilter.value || (row.method || '') === itemMethodFilter.value);
    // Totals on the Item logs tab follow the month / item / method filters (not search or status).
    const itemScope = () => activeTab === 'logs' ? logs.filter(matchesItemFilters) : logs;
    let itemFilterSignature = '';
    // Rebuilds the three dropdowns from what actually exists in the logs (silent). Only touches the DOM when the lists change.
    function refreshItemFilterOptions() {
        const months = [...new Set(logs.map(monthKey).filter(Boolean))].sort().reverse();
        const names = new Map(); logs.forEach(row => names.set(String(row.itemId), row.item));
        const nameList = [...names].sort((a, b) => a[1].localeCompare(b[1]));
        const methods = [...new Set(['Cash', 'GCash', 'Bank Transfer', 'Other'].concat(logs.map(row => row.method).filter(Boolean)))];
        const signature = JSON.stringify([months, nameList, methods]);
        if (signature === itemFilterSignature) return;
        itemFilterSignature = signature;
        window.FBSSDropdown.setOptions(itemMonthRoot, [{ value: '', label: 'All months' }].concat(months.map(k => ({ value: k, label: monthLabel(k) }))), itemMonthFilter.value);
        window.FBSSDropdown.setOptions(itemNameRoot, [{ value: '', label: 'All items' }].concat(nameList.map(([id, name]) => ({ value: id, label: name }))), itemNameFilter.value);
        window.FBSSDropdown.setOptions(itemMethodRoot, [{ value: '', label: 'All methods' }].concat(methods.map(m => ({ value: m, label: m }))), itemMethodFilter.value);
    }
    const clearItemFilters = () => { [itemMonthRoot, itemNameRoot, itemMethodRoot].forEach(root => setDropdown(root, '')); };
    const viewingArchived = () => (activeTab === 'course' || activeTab === 'athletes') && recordFilter.value === 'archived';

    // Selects `value` on a shared dropdown (silent). Resets to the first option
    // first, so an unknown value (e.g. an old payment method) can't leave a stale pick.
    function setDropdown(root, value) {
        const first = root.querySelector('.registerDropdownOption');
        if (first) window.FBSSDropdown.set(root, first.dataset.value ?? '');
        if (value !== '' && value != null) window.FBSSDropdown.set(root, value);
    }
    // Searchable single-choice picker. Keeps the same ids / 'dropdown:change' event as the old dropdown,
    // so the dialogs' existing handlers keep working. Items: { value, inputLabel, label, sub, right }.
    function makePicker(root, emptyText) {
        const input = root.querySelector('.sales-picker-input');
        const list = root.querySelector('.sales-picker-list');
        const hidden = root.querySelector('input[type="hidden"]');
        const clearButton = root.querySelector('.sales-picker-clear');
        let tapping = false;   // a finger / mouse is on the list: don't treat the input's blur as 'closed'
        const syncClear = () => { clearButton.hidden = !input.value; };
        const MAX = 50;   // render cap, so a long roster stays fast
        let items = [], shown = [], active = -1;
        const emit = () => root.dispatchEvent(new CustomEvent('dropdown:change', { bubbles: true }));
        const open = () => { list.hidden = false; input.setAttribute('aria-expanded', 'true'); };
        const close = () => { list.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; };
        function paint(showAll = false) {
            const tokens = showAll ? [] : input.value.trim().toLocaleLowerCase().split(/\s+/).filter(Boolean);
            const matches = items.filter(item => tokens.every(token => item.search.includes(token)));
            shown = matches.slice(0, MAX);
            active = shown.length ? 0 : -1;
            list.innerHTML = shown.length
                ? shown.map((item, n) => `<button class="sales-picker-option${n === active ? ' active' : ''}" type="button" role="option" data-index="${n}"><span class="sales-picker-main"><strong>${escapeHtml(item.label)}</strong><small>${escapeHtml(item.sub || '')}</small></span><span class="sales-picker-right">${escapeHtml(item.right || '')}</span></button>`).join('')
                    + (matches.length > MAX ? `<p class="sales-picker-more">Showing ${MAX} of ${matches.length}. Keep typing to narrow it down.</p>` : '')
                : `<p class="sales-picker-empty">${escapeHtml(emptyText)}</p>`;
        }
        function highlight(next) {
            if (!shown.length) return;
            active = (next + shown.length) % shown.length;
            [...list.querySelectorAll('.sales-picker-option')].forEach((el, n) => el.classList.toggle('active', n === active));
            list.querySelectorAll('.sales-picker-option')[active].scrollIntoView({ block: 'nearest' });
        }
        function pick(item) {
            hidden.value = item.value;
            input.value = item.inputLabel;
            root.classList.remove('isInvalid');
            close(); syncClear();
            emit();
        }
        input.addEventListener('input', () => {
            if (hidden.value) { hidden.value = ''; emit(); }   // editing the text drops the earlier choice
            paint(); open(); syncClear();
        });
        input.addEventListener('focus', () => {
            if (hidden.value) input.select();
            paint(Boolean(hidden.value)); open();
            // On a phone the keyboard covers the lower half: bring the box into view once it has opened.
            setTimeout(() => input.scrollIntoView({ block: 'nearest', behavior: 'smooth' }), 250);
        });
        input.addEventListener('blur', () => { if (tapping) return; close(); if (!hidden.value) input.value = ''; syncClear(); });
        // X: forget the choice (or the half-typed text) and go straight back to the full list.
        clearButton.addEventListener('mousedown', event => event.preventDefault());
        clearButton.addEventListener('click', () => {
            const had = Boolean(hidden.value);
            hidden.value = ''; input.value = ''; syncClear();
            if (had) emit();
            input.focus(); paint(true); open();
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown') { event.preventDefault(); if (list.hidden) { paint(Boolean(hidden.value)); open(); } else highlight(active + 1); }
            else if (event.key === 'ArrowUp') { event.preventDefault(); highlight(active - 1); }
            else if (event.key === 'Enter') { event.preventDefault(); if (!list.hidden && shown[active]) pick(shown[active]); }
            else if (event.key === 'Escape' && !list.hidden) { event.preventDefault(); event.stopPropagation(); close(); }
        });
        // Choose on click/tap (so scrolling the list with a finger never picks by accident).
        // mousedown is cancelled so the input keeps focus; `tapping` covers touch screens, where blur can come first.
        list.addEventListener('pointerdown', () => { tapping = true; });
        document.addEventListener('pointerup', () => setTimeout(() => { tapping = false; }, 300));
        list.addEventListener('mousedown', event => event.preventDefault());
        list.addEventListener('click', event => {
            const option = event.target.closest('.sales-picker-option');
            if (option) pick(shown[Number(option.dataset.index)]);
        });
        return {
            setItems(next) {   // silent, like the old setOptions: clears any earlier choice
                items = next.map(item => ({ ...item, search: `${item.label} ${item.sub || ''} ${item.inputLabel}`.toLocaleLowerCase() }));
                hidden.value = ''; input.value = '';
                root.classList.remove('isInvalid');
                close(); syncClear();
            }
        };
    }
    const addButton = document.getElementById('salesAddButton');
    // Uses the app-wide toast (layouts.toast), same as every other page.
    function notify(message, type = 'success') {
        if (typeof showToast === 'function') {
            showToast(message, type);
        } else {
            console.log('[' + type + ']', message);
        }
    }

    function currentFilter() {
        return { text: search.value.trim().toLocaleLowerCase(), status: statusFilter.value };
    }

    // Each box means the same thing on every tab:
    //   Total collected = money received      Outstanding = still owed
    //   Paid            = value of fully paid  Unpaid      = value of untouched (nothing paid yet)
    function courseStats() {
        // Course rows are one per payment, so the balance-based totals are
        // taken once per enrollment (not once per payment row).
        const enrollments = new Map();
        courseRows.forEach(row => enrollments.set(row.enrollmentId, { toPay: Number(row.toPay), paid: Number(row.enrollmentPaid) }));
        const list = [...enrollments.values()];
        return {
            collected: courseRows.reduce((total, row) => total + Number(row.paid), 0),
            outstanding: list.reduce((total, e) => total + Math.max(e.toPay - e.paid, 0), 0),
            paid: list.reduce((total, e) => total + (e.paid > 0 && e.paid >= e.toPay ? e.paid : 0), 0),
            unpaid: list.reduce((total, e) => total + (e.paid <= 0 ? e.toPay : 0), 0)
        };
    }

    function itemStats() {
        // One item log = one sale: to pay = unit price x quantity.
        const sales = itemScope().map(row => ({ total: Number(row.price) * Number(row.quantity), paid: Number(row.paid) }));
        return {
            collected: sales.reduce((total, s) => total + s.paid, 0),
            outstanding: sales.reduce((total, s) => total + Math.max(s.total - s.paid, 0), 0),
            paid: sales.reduce((total, s) => total + (s.paid > 0 && s.paid >= s.total ? s.paid : 0), 0),
            unpaid: sales.reduce((total, s) => total + (s.paid <= 0 ? s.total : 0), 0)
        };
    }

    function athleteStats() {
        // Rows are one per payment; balance-based totals are taken once per due
        // (athlete + fee + period), not once per payment row.
        const dues = new Map();
        athleteRows.forEach(row => dues.set(row.dueKey, { toPay: Number(row.toPay), paid: Number(row.duePaid) }));
        const list = [...dues.values()];
        return {
            collected: athleteRows.reduce((total, row) => total + Number(row.paid), 0),
            outstanding: list.reduce((total, d) => total + Math.max(d.toPay - d.paid, 0), 0),
            paid: list.reduce((total, d) => total + (d.paid > 0 && d.paid >= d.toPay ? d.paid : 0), 0),
            unpaid: list.reduce((total, d) => total + (d.paid <= 0 ? d.toPay : 0), 0)
        };
    }

    function updateStats() {
        document.getElementById('salesStats').hidden = activeTab === 'items' || activeTab === 'fees' || viewingArchived();
        const course = courseStats();
        const item = itemStats();
        const athlete = athleteStats();
        const stats = activeTab === 'course' ? course
            : activeTab === 'logs' ? item
            : activeTab === 'athletes' ? athlete
            : {
                collected: course.collected + item.collected + athlete.collected,
                outstanding: course.outstanding + item.outstanding + athlete.outstanding,
                paid: course.paid + item.paid + athlete.paid,
                unpaid: course.unpaid + item.unpaid + athlete.unpaid
            };
        document.getElementById('salesTotalCollected').textContent = money(stats.collected);
        document.getElementById('salesOutstanding').textContent = money(stats.outstanding);
        document.getElementById('salesPaid').textContent = money(stats.paid);
        document.getElementById('salesUnpaid').textContent = money(stats.unpaid);
    }

    function renderArchivedCourseRows() {
        const { text } = currentFilter();
        const visible = archivedCourseRows.filter(row =>
            !text || `${row.course} ${row.name} ${row.method} ${row.date} ${row.archivedAt} ${row.receipt}`.toLocaleLowerCase().includes(text));
        document.getElementById('coursePaymentRows').innerHTML = visible.length ? visible.map(row =>
            `<tr><td class="sales-name">${escapeHtml(row.course)}</td><td>${escapeHtml(row.name)}</td><td>${money(row.price)}</td><td>${money(row.paid)}</td><td>--</td><td>${escapeHtml(row.method || '--')}</td><td>${badge('archived')}</td><td>${escapeHtml(row.date)}</td><td>${archivedNote(row)}</td><td>${row.replaced ? '<span class="sales-reason">Replaced by a newer entry</span>' : `<span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-course-relog="${row.id}" aria-label="Re-log payment of ${escapeHtml(row.name)}" title="Re-log payment"><i class="bi bi-arrow-counterclockwise"></i></button></span>`}</td><td>--</td></tr>`
        ).join('') : '<tr><td class="sales-empty" colspan="11">No archived course payments.</td></tr>';
    }

    function renderCourseRows() {
        if (viewingArchived()) { renderArchivedCourseRows(); return; }
        const { text, status } = currentFilter();
        const visible = courseRows.filter(row => {
            const rowStatus = row.status;
            const haystack = `${row.course} ${row.name} ${row.method} ${row.date}`.toLocaleLowerCase();
            return (!status || status === rowStatus) && (!text || haystack.includes(text));
        });
        document.getElementById('coursePaymentRows').innerHTML = visible.length ? visible.map(row => {
            const balance = Number(row.balance);
            return `<tr><td class="sales-name">${escapeHtml(row.course)}</td><td>${escapeHtml(row.name)}</td><td>${money(row.price)}</td><td>${money(row.paid)}</td><td>${money(balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(row.status)}</td><td>${escapeHtml(row.date)}</td><td>${escapeHtml(row.updatedAt || '--')}</td><td><span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-course-edit="${row.id}" aria-label="Record payment for ${escapeHtml(row.name)}" title="${Number(row.id) > 0 ? 'Edit payment' : 'Record payment'}"><i class="bi bi-pencil"></i></button>${Number(row.id) > 0 ? `<button class="btn btn-icon btn-icon-sm btn-outline-danger" type="button" data-course-archive="${row.id}" aria-label="Archive payment of ${escapeHtml(row.name)}" title="Archive"><i class="bi bi-archive"></i></button>` : ''}</span></td><td><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-course-receipt="${row.id}" aria-label="View invoice for ${escapeHtml(row.name)}" title="View invoice"><i class="bi bi-eye"></i></button></td></tr>`;
        }).join('') : '<tr><td class="sales-empty" colspan="11">No course payments match your search.</td></tr>';
    }

    function renderLogs() {
        const { text, status } = currentFilter();
        const visible = logs.filter(row => {
            const total = Number(row.price) * Number(row.quantity);
            const rowStatus = statusFor(row.paid, total);
            const haystack = `${row.item} ${row.description} ${row.client} ${row.method} ${row.date} ${row.receipt || ''}`.toLocaleLowerCase();
            return matchesItemFilters(row) && (!status || status === rowStatus) && (!text || haystack.includes(text));
        });
        document.getElementById('itemLogRows').innerHTML = visible.length ? visible.map(row => {
            const total = Number(row.price) * Number(row.quantity);
            const balance = Math.max(total - Number(row.paid), 0);
            return `<tr><td class="sales-name">${escapeHtml(row.item)}</td><td>${escapeHtml(row.description || '--')}</td><td>${Number(row.quantity)}</td><td>${money(row.price)}</td><td>${money(total)}</td><td>${money(row.paid)}</td><td>${money(balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(statusFor(row.paid, total))}</td><td>${escapeHtml(row.date)}</td><td><span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-log-edit="${row.id}" aria-label="Edit ${escapeHtml(row.item)} sale" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn btn-icon btn-icon-sm btn-outline-danger" type="button" data-log-delete="${row.id}" aria-label="Remove ${escapeHtml(row.item)} sale" title="Remove"><i class="bi bi-trash3"></i></button></span></td><td><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-log-receipt="${row.id}" aria-label="View invoice for ${escapeHtml(row.item)}" title="View invoice"><i class="bi bi-eye"></i></button></td></tr>`;
        }).join('') : '<tr><td class="sales-empty" colspan="12">No item logs match your search or filters.</td></tr>';
    }

    function renderItems() {
        const text = search.value.trim().toLocaleLowerCase();
        const visible = items.filter(item => `${item.name} ${item.description}`.toLocaleLowerCase().includes(text));
        document.getElementById('itemCatalogRows').innerHTML = visible.length ? visible.map(item => `<tr><td class="sales-name">${escapeHtml(item.name)}</td><td>${escapeHtml(item.description || '--')}</td><td>${money(item.price)}</td><td>${Number(item.stock)}</td><td><span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-item-edit="${item.id}" aria-label="Edit ${escapeHtml(item.name)}" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn btn-icon btn-icon-sm btn-outline-danger" type="button" data-item-delete="${item.id}" aria-label="Remove ${escapeHtml(item.name)}" title="Remove"><i class="bi bi-trash3"></i></button></span></td></tr>`).join('') : '<tr><td class="sales-empty" colspan="5">No items match your search.</td></tr>';
    }

    // "All": course payments and item sales merged, newest first. Read-only; edit from their own tab.
    function renderAll() {
        const { text, status } = currentFilter();
        const rows = courseRows.map(row => ({
            kind: 'course', id: row.id, type: 'Course', name: row.course, client: row.name,
            total: Number(row.toPay), paid: Number(row.paid), balance: Number(row.balance),
            method: row.method, status: row.status, date: row.date, sortAt: row.sortAt || ''
        })).concat(logs.map(row => {
            const total = Number(row.price) * Number(row.quantity);
            return {
                kind: 'item', id: row.id, type: 'Item', name: row.item, client: row.client || 'Walk-in',
                total, paid: Number(row.paid), balance: Math.max(total - Number(row.paid), 0),
                method: row.method, status: statusFor(row.paid, total), date: row.date, sortAt: row.sortAt || ''
            };
        })).concat(athleteRows.map(row => ({
            kind: 'athlete', id: row.id, type: 'Athlete', name: `${row.fee} (${row.period})`, client: row.name,
            total: Number(row.toPay), paid: Number(row.paid), balance: Number(row.balance),
            method: row.method, status: row.status, date: row.date, sortAt: row.sortAt || ''
        }))).sort((a, b) => String(b.sortAt).localeCompare(String(a.sortAt)));

        const visible = rows.filter(row => {
            const haystack = `${row.type} ${row.name} ${row.client} ${row.method} ${row.date}`.toLocaleLowerCase();
            return (!status || status === row.status) && (!text || haystack.includes(text));
        });
        document.getElementById('allRows').innerHTML = visible.length ? visible.map(row =>
            `<tr><td>${row.type}</td><td class="sales-name">${escapeHtml(row.name)}</td><td>${escapeHtml(row.client)}</td><td>${money(row.total)}</td><td>${money(row.paid)}</td><td>${money(row.balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(row.status)}</td><td>${escapeHtml(row.date)}</td><td><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-all-receipt="${row.id}" data-all-kind="${row.kind}" aria-label="View invoice for ${escapeHtml(row.name)}" title="View invoice"><i class="bi bi-eye"></i></button></td></tr>`
        ).join('') : '<tr><td class="sales-empty" colspan="10">No records match your search.</td></tr>';
    }

    function render() { refreshItemFilterOptions(); itemFilterClear.hidden = !itemFiltersActive(); renderAll(); renderCourseRows(); renderLogs(); renderItems(); renderAthleteRows(); renderFees(); updateStats(); }

    function setTab(tab) {
        activeTab = tab;
        document.querySelectorAll('[data-sales-tab]').forEach(button => {
            const isSelected = button.dataset.salesTab === tab;
            button.setAttribute('aria-selected', String(isSelected));
            button.classList.toggle('active', isSelected);
        });
        document.querySelectorAll('[data-sales-panel]').forEach(panel => { panel.hidden = panel.dataset.salesPanel !== tab; });
        search.value = '';
        setDropdown(statusFilterRoot, '');
        const labels = { all: '', course: 'Record Course Payment', logs: 'Add New Purchase', items: 'Add New Item', athletes: 'Record Athlete Payment', fees: 'Add Athlete Fee' };
        addButton.querySelector('span').textContent = labels[tab];
        addButton.hidden = tab === 'all';   // nothing to add from the combined view
        setDropdown(recordFilterRoot, '');   // back to "Active"
        recordFilterRoot.hidden = tab !== 'course' && tab !== 'athletes';
        statusFilterRoot.hidden = tab === 'items' || tab === 'fees';
        clearItemFilters();
        itemFiltersRoot.hidden = tab !== 'logs';
        render();   // new tab: re-apply the (now cleared) search/filter and refresh the totals
    }

    // State of the open payment dialog. `others` = everything already paid on
    // this enrollment EXCLUDING the payment log being edited, so the most this
    // payment can be is  toPay (course_price_at_enrollment) - others.
    let paymentDialog = { toPay: 0, others: 0, available: 0 };
    let paymentSubmitLabel = 'Update';
    const round2 = value => Math.round(Number(value) * 100) / 100;

    function showCoursePayment(row) {
        const form = document.getElementById('coursePaymentForm');
        const editing = Number(row.id) > 0;   // negative id = enrollment with no payment yet
        const others = editing ? round2(Number(row.enrollmentPaid) - Number(row.paid)) : round2(row.enrollmentPaid);
        const available = Math.max(round2(Number(row.toPay) - others), 0);

        paymentDialog = { toPay: Number(row.toPay), others, available };
        paymentSubmitLabel = editing ? 'Update' : 'Record';
        form.elements.relog.value = '';

        document.getElementById('coursePaymentEnrollmentGroup').hidden = true;
        document.getElementById('coursePaymentSubmit').textContent = paymentSubmitLabel;
        form.elements.id.value = row.enrollmentId;
        form.elements.paymentId.value = editing ? row.id : '';
        document.getElementById('coursePaymentReasonGroup').hidden = !editing;
        form.elements.reason.value = '';
        form.elements.amount.value = editing ? row.paid : '';
        setDropdown(document.getElementById('coursePaymentMethodDropdown'), editing ? (row.method || '') : '');
        document.getElementById('coursePaymentMethodDropdown').classList.remove('isInvalid');
        form.elements.reference.value = editing ? (row.reference || '') : '';

        document.getElementById('coursePaymentTitle').textContent = editing ? 'Updating course payment' : 'Recording course payment';
        document.getElementById('coursePaymentStudent').textContent = `Student name: ${row.name}`;
        document.getElementById('coursePaymentLimit').textContent = editing
            ? `Up to ${money(available)} for this payment (course price minus the other payments).`
            : `Up to ${money(available)} (the remaining balance).`;

        refreshPaymentPreview();
        document.getElementById('coursePaymentDialog').showModal();
        if (matchMedia('(pointer: coarse)').matches) document.activeElement?.blur();   // phones: tap the field to open the keyboard
    }

    // "Re-log" an archived payment: same dialog, prefilled, and checked against
    // the ACTIVE payments only (course price minus what is currently counted).
    function showRelogPayment(row) {
        const form = document.getElementById('coursePaymentForm');
        const others = round2(row.enrollmentPaid);
        const available = Math.max(round2(Number(row.toPay) - others), 0);

        if (available <= 0) {
            notify(`${row.name} is already fully paid, so this payment can't be re-logged.`, 'danger');
            return;
        }

        paymentDialog = { toPay: Number(row.toPay), others, available };
        paymentSubmitLabel = 'Re-log';

        document.getElementById('coursePaymentEnrollmentGroup').hidden = true;
        document.getElementById('coursePaymentSubmit').textContent = paymentSubmitLabel;
        form.elements.id.value = row.enrollmentId;
        form.elements.paymentId.value = row.id;
        form.elements.relog.value = '1';
        form.elements.amount.value = row.paid;
        setDropdown(document.getElementById('coursePaymentMethodDropdown'), row.method || '');
        document.getElementById('coursePaymentMethodDropdown').classList.remove('isInvalid');
        form.elements.reference.value = row.reference || '';

        document.getElementById('coursePaymentReasonGroup').hidden = true;
        document.getElementById('coursePaymentTitle').textContent = 'Re-logging course payment';
        document.getElementById('coursePaymentStudent').textContent = `Student name: ${row.name}`;
        document.getElementById('coursePaymentLimit').textContent = `Up to ${money(available)} (course price minus the payments currently counted).`;

        refreshPaymentPreview();
        document.getElementById('coursePaymentDialog').showModal();
    }

    // Enrollments that still owe something. Course rows are one per payment,
    // so take each enrollment once (every row of it carries the same totals).
    const studentPicker = makePicker(document.getElementById('coursePaymentEnrollmentDropdown'), 'No student with a balance matches.');
    function outstandingEnrollments() {
        const seen = new Map();
        courseRows.forEach(row => { if (!seen.has(row.enrollmentId)) seen.set(row.enrollmentId, row); });
        return [...seen.values()]
            .map(row => ({
                enrollmentId: row.enrollmentId,
                name: row.name,
                course: row.course,
                toPay: Number(row.toPay),
                paid: Number(row.enrollmentPaid),
                remaining: Math.max(round2(Number(row.toPay) - Number(row.enrollmentPaid)), 0)
            }))
            .filter(e => e.remaining > 0)
            .sort((a, b) => a.name.localeCompare(b.name));
    }

    // "Record Course Payment" (top button): pick a student, then log a new payment.
    function showRecordPayment() {
        const owing = outstandingEnrollments();
        if (!owing.length) { notify('Every enrolled student is already fully paid.'); return; }

        const form = document.getElementById('coursePaymentForm');
        paymentDialog = { toPay: 0, others: 0, available: 0 };
        paymentSubmitLabel = 'Record';
        form.elements.relog.value = '';

        form.elements.id.value = '';
        form.elements.paymentId.value = '';
        form.elements.amount.value = '';
        setDropdown(document.getElementById('coursePaymentMethodDropdown'), '');
        document.getElementById('coursePaymentMethodDropdown').classList.remove('isInvalid');
        form.elements.reference.value = '';

        document.getElementById('coursePaymentEnrollmentGroup').hidden = false;
        document.getElementById('coursePaymentEnrollmentDropdown').classList.remove('isInvalid');
        studentPicker.setItems(owing.map(e => ({
            value: e.enrollmentId, label: e.name, sub: e.course, inputLabel: `${e.name} - ${e.course}`,
            right: `${money(e.remaining)} left`
        })));

        document.getElementById('coursePaymentReasonGroup').hidden = true;
        document.getElementById('coursePaymentTitle').textContent = 'Recording course payment';
        document.getElementById('coursePaymentStudent').textContent = 'Choose the student who is paying.';
        document.getElementById('coursePaymentLimit').textContent = '';
        document.getElementById('coursePaymentSubmit').textContent = paymentSubmitLabel;

        refreshPaymentPreview();
        document.getElementById('coursePaymentDialog').showModal();
    }

    // The student picked in the "Record Course Payment" dialog: the most this
    // payment can be is that enrollment's remaining balance.
    function applyPickedEnrollment() {
        const form = document.getElementById('coursePaymentForm');
        const id = Number(document.getElementById('coursePaymentEnrollment').value) || 0;
        const picked = outstandingEnrollments().find(e => e.enrollmentId === id);

        form.elements.id.value = picked ? picked.enrollmentId : '';
        paymentDialog = picked
            ? { toPay: picked.toPay, others: round2(picked.paid), available: picked.remaining }
            : { toPay: 0, others: 0, available: 0 };

        document.getElementById('coursePaymentStudent').textContent = picked ? `Student name: ${picked.name} (${picked.course})` : 'Choose the student who is paying.';
        document.getElementById('coursePaymentLimit').textContent = picked ? `Up to ${money(picked.remaining)} (the remaining balance).` : '';
        refreshPaymentPreview();
    }

    // Live check while typing, like the Registers modal: remaining balance,
    // new status, and an inline error when the amount is over the limit.
    function refreshPaymentPreview() {
        const amount = Math.max(Number(document.getElementById('coursePaymentAmount').value) || 0, 0);
        const exceeds = round2(amount) > paymentDialog.available;
        const remaining = Math.max(round2(paymentDialog.available - amount), 0);
        const status = statusFor(paymentDialog.others + Math.min(amount, paymentDialog.available), paymentDialog.toPay);

        document.getElementById('coursePaymentBalance').textContent = money(remaining);

        const badgeElement = document.getElementById('coursePaymentStatus');
        badgeElement.className = `sales-badge sales-badge--${status}`;
        badgeElement.textContent = statusLabel(status);

        const error = document.getElementById('coursePaymentError');
        error.hidden = !exceeds;
        error.textContent = exceeds ? `Exceeds the limit of ${money(paymentDialog.available)}.` : '';

        document.getElementById('coursePaymentSubmit').disabled = exceeds || amount <= 0;
    }

    function refreshPurchasePrice() {
        const selected = byId(items, document.getElementById('purchaseItem').value);
        const quantity = Math.max(Number(document.getElementById('purchaseQuantity').value) || 1, 1);
        document.getElementById('purchaseUnitPrice').value = selected ? money(selected.price) : money(0);
        document.getElementById('purchaseToPay').textContent = money(selected ? selected.price * quantity : 0);
    }

    function fillItemOptions(selectedId = null) {
        // Rebuilds the shared dropdown's option list from the catalog (silent).
        window.FBSSDropdown.setOptions(
            document.getElementById('purchaseItemDropdown'),
            items.map(item => ({ value: item.id, label: item.name })),
            selectedId ?? ''
        );
    }

    function showPurchase(row = null) {
        if (!items.length) { notify('Add an item to the catalog before recording a purchase.'); return; }
        const form = document.getElementById('purchaseForm');
        form.reset();
        form.elements.id.value = row?.id ?? '';
        fillItemOptions(row?.itemId ?? items[0]?.id);
        form.elements.quantity.value = row?.quantity ?? 1;
        form.elements.paid.value = row?.paid ?? 0;
        setDropdown(document.getElementById('purchaseMethodDropdown'), row?.method || 'Cash');
        document.getElementById('purchaseDescription').value = row?.description || '';
        const editing = Boolean(row);
        document.getElementById('purchaseDialogTitle').textContent = editing ? 'Updating Purchase' : 'Adding New Purchase';
        document.getElementById('purchaseDialogSubtitle').textContent = editing ? 'Purchase' : 'New Purchase';
        document.getElementById('purchaseSubmit').textContent = editing ? 'Update' : 'Add';
        refreshPurchasePrice();
        document.getElementById('purchaseDialog').showModal();
    }

    function showItem(item = null) {
        const form = document.getElementById('itemForm');
        form.reset();
        form.elements.id.value = item?.id ?? '';
        form.elements.name.value = item?.name ?? '';
        form.elements.description.value = item?.description ?? '';
        form.elements.price.value = item?.price ?? '';
        form.elements.stock.value = item?.stock ?? 0;
        const editing = Boolean(item);
        document.getElementById('itemDialogTitle').textContent = editing ? 'Updating Item' : 'Adding New Item';
        document.getElementById('itemDialogSubtitle').textContent = editing ? 'Item' : 'New Item';
        document.getElementById('itemSubmit').textContent = editing ? 'Update' : 'Add';
        document.getElementById('itemDialog').showModal();
    }

    function showReceipt(row, type) {
        const isCourse = type === 'course';
        const isAthlete = type === 'athlete';
        const isPlan = isCourse || isAthlete;   // one payment toward a bigger amount due
        const noPaymentYet = isPlan && Number(row.id) < 0;   // an unpaid bill: nothing recorded yet
        const total = isPlan ? Number(row.toPay) : Number(row.price) * Number(row.quantity);
        const paid = Number(row.paid);
        const balance = isPlan ? Number(row.balance) : Math.max(total - paid, 0);
        const status = isPlan ? row.status : statusFor(paid, total);
        const client = isPlan ? row.name : (row.client || 'Walk-in customer');
        const description = isCourse ? row.course : isAthlete ? row.fee : row.item;
        const quantityLine = isCourse ? 'Course enrollment' : isAthlete ? `${cycleLabel(row.cycle)} fee - ${row.period}` : `${row.quantity} × ${money(row.price)}`;
        const prefix = isCourse ? 'INV-C' : isAthlete ? 'INV-A' : 'INV-I';
        const invoice = row.receipt || `${prefix}-${new Date().getFullYear()}-${String(row.id).padStart(4, '0')}`;
        const labels = isCourse ? ['Course price', noPaymentYet ? 'Paid so far' : 'This payment', 'Balance due']
            : isAthlete ? ['Fee amount', noPaymentYet ? 'Paid so far' : 'This payment', 'Balance due']
            : ['To pay', 'Amount paid', 'Balance due'];

        const metaRows = [['Invoice #', invoice], [noPaymentYet ? 'Date issued' : 'Date paid', row.date || '--'], ['Client', client]];
        if (!isPlan && row.description) metaRows.push(['Description', row.description]);
        if (isPlan && row.reference) metaRows.push(['Reference #', row.reference]);
        if (isPlan && row.receivedBy) metaRows.push(['Recorded by', row.receivedBy]);
        if (isPlan && row.replacesReceipt) metaRows.push(['Corrects', row.replacesReceipt]);
        const meta = metaRows.map(([label, value]) => `<span>${label}</span><strong>${escapeHtml(value)}</strong>`).join('');

        const paidToDate = isPlan && !noPaymentYet
            ? `<div class="sales-receipt-line"><span>Total paid to date</span><strong>${money(row.paidToDate)}</strong></div>`
            : '';

        const thanks = status === 'paid' ? 'Thank you for your payment! This invoice is fully settled.'
            : status === 'partial' ? `Thank you for your payment. Remaining balance: ${money(balance)}`
            : `Amount due: ${money(balance)}. Please settle it at the school.`;

        document.getElementById('receiptContent').innerHTML =
            `<header class="sales-receipt-head"><div class="sales-receipt-brand"><img src="{{ asset('assets/images/icon_fbss.png') }}" alt="" width="22" height="22" style="border-radius:50%"> FBSS SwimOS ${badge(status)}</div><strong>Froy Bartolome Swimming School</strong><small>R3MG+2X9, Del Monte - Norzagaray Rd, SJDM, Bulacan<br>(+63) 966 203 1918</small></header>` +
            `<div class="sales-receipt-body"><div class="sales-receipt-meta">${meta}</div>` +
            `<p class="sales-receipt-section">${isCourse ? 'Course' : isAthlete ? 'Athlete fee' : 'Items'}</p>` +
            `<div class="sales-receipt-line"><span>${escapeHtml(description)}<small>${escapeHtml(quantityLine)}</small></span><strong>${money(total)}</strong></div>` +
            paidToDate +
            `<div class="sales-receipt-amounts"><div class="sales-receipt-amount"><small>${labels[0]}</small><strong>${money(total)}</strong></div><div class="sales-receipt-amount"><small>${labels[1]}</small><strong>${money(noPaymentYet ? 0 : paid)}</strong></div><div class="sales-receipt-amount"><small>${labels[2]}</small><strong>${money(balance)}</strong></div></div>` +
            `<div class="sales-receipt-method">Payment Method: <strong>${escapeHtml(row.method || '--')}</strong></div>` +
            `<p class="sales-receipt-thanks">${escapeHtml(thanks)}</p><p class="sales-receipt-footer">We look forward to seeing you at the pool</p><p class="sales-receipt-notice">This is a billing invoice, not an official receipt.</p></div>`;
        document.getElementById('receiptDialog').showModal();
    }

    // ------------------------------------------------------------------
    // Athlete payments + athlete fees (the "payment setting")
    // ------------------------------------------------------------------
    const cycleLabel = cycle => ({ one_time: 'One-time', monthly: 'Monthly', yearly: 'Yearly' }[cycle] || cycle || '--');
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

    async function sendJson(url, method, body) {
        const response = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            body: body ? JSON.stringify(body) : undefined
        });
        const data = await response.json().catch(() => ({}));
        return { ok: response.ok && data.success, status: response.status, data };
    }

    const errorText = (result, fallback) =>
        (result.data.errors ? Object.values(result.data.errors)[0][0] : null) || result.data.message || `${fallback} (error ${result.status}).`;

    function renderArchivedAthleteRows() {
        const { text } = currentFilter();
        const visible = archivedAthleteRows.filter(row =>
            !text || `${row.fee} ${row.name} ${row.period} ${row.method} ${row.date} ${row.archivedAt} ${row.receipt}`.toLocaleLowerCase().includes(text));
        document.getElementById('athletePaymentRows').innerHTML = visible.length ? visible.map(row =>
            `<tr><td class="sales-name">${escapeHtml(row.fee)}</td><td>${escapeHtml(row.name)}</td><td>${escapeHtml(row.period)}</td><td>${money(row.toPay)}</td><td>${money(row.paid)}</td><td>--</td><td>${escapeHtml(row.method || '--')}</td><td>${badge('archived')}</td><td>${escapeHtml(row.date || '--')}</td><td>${archivedNote(row)}</td><td>${row.replaced ? '<span class="sales-reason">Replaced by a newer entry</span>' : `<span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-athlete-relog="${row.id}" aria-label="Re-log payment of ${escapeHtml(row.name)}" title="Re-log payment"><i class="bi bi-arrow-counterclockwise"></i></button></span>`}</td><td>--</td></tr>`
        ).join('') : '<tr><td class="sales-empty" colspan="12">No archived athlete payments.</td></tr>';
    }

    function renderAthleteRows() {
        if (viewingArchived()) { renderArchivedAthleteRows(); return; }
        const { text, status } = currentFilter();
        const visible = athleteRows.filter(row => {
            const haystack = `${row.fee} ${row.name} ${row.period} ${row.method} ${row.date}`.toLocaleLowerCase();
            return (!status || status === row.status) && (!text || haystack.includes(text));
        });
        document.getElementById('athletePaymentRows').innerHTML = visible.length ? visible.map(row =>
            `<tr><td class="sales-name">${escapeHtml(row.fee)}</td><td>${escapeHtml(row.name)}</td><td>${escapeHtml(row.period)}</td><td>${money(row.toPay)}</td><td>${money(row.paid)}</td><td>${money(row.balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(row.status)}</td><td>${escapeHtml(row.date || '--')}</td><td>${escapeHtml(row.updatedAt || '--')}</td><td><span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-athlete-edit="${row.id}" aria-label="Record payment for ${escapeHtml(row.name)}" title="${Number(row.id) > 0 ? 'Edit payment' : 'Record payment'}"><i class="bi bi-pencil"></i></button>${Number(row.id) > 0 ? `<button class="btn btn-icon btn-icon-sm btn-outline-danger" type="button" data-athlete-archive="${row.id}" aria-label="Archive payment of ${escapeHtml(row.name)}" title="Archive"><i class="bi bi-archive"></i></button>` : ''}</span></td><td><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-athlete-receipt="${row.id}" aria-label="View invoice for ${escapeHtml(row.name)}" title="View invoice"><i class="bi bi-eye"></i></button></td></tr>`
        ).join('') : '<tr><td class="sales-empty" colspan="12">No athlete payments match your search.</td></tr>';
    }

    function renderFees() {
        const text = search.value.trim().toLocaleLowerCase();
        const visible = athleteFees.filter(fee => `${fee.name} ${fee.description} ${cycleLabel(fee.cycle)}`.toLocaleLowerCase().includes(text));
        document.getElementById('athleteFeeRows').innerHTML = visible.length ? visible.map(fee =>
            `<tr><td class="sales-name">${escapeHtml(fee.name)}</td><td>${escapeHtml(fee.description || '--')}</td><td>${money(fee.amount)}</td><td>${cycleLabel(fee.cycle)}</td><td><span class="sales-badge sales-badge--${fee.active ? 'paid' : 'unpaid'}">${fee.active ? 'Active' : 'Archived'}</span></td><td><span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-fee-edit="${fee.id}" aria-label="Edit ${escapeHtml(fee.name)}" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn btn-icon btn-icon-sm btn-outline-danger" type="button" data-fee-delete="${fee.id}" aria-label="Remove ${escapeHtml(fee.name)}" title="Remove"><i class="bi bi-trash3"></i></button></span></td></tr>`
        ).join('') : '<tr><td class="sales-empty" colspan="6">No athlete fees yet. Add one to start billing athletes.</td></tr>';
    }

    // Same idea as the course dialog: `others` = paid on this due EXCLUDING the
    // payment being edited, so the most it can be is toPay - others.
    let athleteDialog = { toPay: 0, others: 0, available: 0 };
    let athleteSubmitLabel = 'Record';

    function showAthletePayment(row) {
        const form = document.getElementById('athletePaymentForm');
        const editing = Number(row.id) > 0;   // negative id = a due with no payment yet
        const others = editing ? round2(Number(row.duePaid) - Number(row.paid)) : round2(row.duePaid);
        const available = Math.max(round2(Number(row.toPay) - others), 0);

        athleteDialog = { toPay: Number(row.toPay), others, available };
        athleteSubmitLabel = editing ? 'Update' : 'Record';
        form.elements.relog.value = '';

        document.getElementById('athletePaymentDueGroup').hidden = true;
        document.getElementById('athletePaymentSubmit').textContent = athleteSubmitLabel;
        form.elements.paymentId.value = editing ? row.id : '';
        form.elements.userId.value = row.userId;
        form.elements.feeId.value = row.feeId;
        form.elements.periodKey.value = row.periodKey;
        document.getElementById('athletePaymentReasonGroup').hidden = !editing;
        form.elements.reason.value = '';
        form.elements.amount.value = editing ? row.paid : '';
        setDropdown(document.getElementById('athletePaymentMethodDropdown'), editing ? (row.method || '') : '');
        document.getElementById('athletePaymentMethodDropdown').classList.remove('isInvalid');
        form.elements.reference.value = editing ? (row.reference || '') : '';

        document.getElementById('athletePaymentTitle').textContent = editing ? 'Updating athlete payment' : 'Recording athlete payment';
        document.getElementById('athletePaymentSubtitle').textContent = `Athlete: ${row.name} - ${row.fee} (${row.period})`;
        document.getElementById('athletePaymentLimit').textContent = editing
            ? `Up to ${money(available)} for this payment (fee amount minus the other payments).`
            : `Up to ${money(available)} (the remaining balance).`;

        refreshAthletePreview();
        document.getElementById('athletePaymentDialog').showModal();
        if (matchMedia('(pointer: coarse)').matches) document.activeElement?.blur();
    }

    // "Re-log" an archived athlete payment: same dialog, prefilled, checked
    // against the ACTIVE payments of that due only.
    function showRelogAthletePayment(row) {
        const form = document.getElementById('athletePaymentForm');
        const others = round2(row.duePaid);
        const available = Math.max(round2(Number(row.toPay) - others), 0);

        if (available <= 0) {
            notify(`${row.name} already paid this fee in full, so this payment can't be re-logged.`, 'danger');
            return;
        }

        athleteDialog = { toPay: Number(row.toPay), others, available };
        athleteSubmitLabel = 'Re-log';

        document.getElementById('athletePaymentDueGroup').hidden = true;
        document.getElementById('athletePaymentSubmit').textContent = athleteSubmitLabel;
        form.elements.paymentId.value = row.id;
        form.elements.userId.value = row.userId;
        form.elements.feeId.value = row.feeId;
        form.elements.periodKey.value = row.periodKey;
        form.elements.relog.value = '1';
        form.elements.amount.value = row.paid;
        setDropdown(document.getElementById('athletePaymentMethodDropdown'), row.method || '');
        document.getElementById('athletePaymentMethodDropdown').classList.remove('isInvalid');
        form.elements.reference.value = row.reference || '';

        document.getElementById('athletePaymentReasonGroup').hidden = true;
        document.getElementById('athletePaymentTitle').textContent = 'Re-logging athlete payment';
        document.getElementById('athletePaymentSubtitle').textContent = `Athlete: ${row.name} - ${row.fee} (${row.period})`;
        document.getElementById('athletePaymentLimit').textContent = `Up to ${money(available)} (fee amount minus the payments currently counted).`;

        refreshAthletePreview();
        document.getElementById('athletePaymentDialog').showModal();
    }

    // Dues that still owe something (one entry per athlete + fee + period).
    const athletePicker = makePicker(document.getElementById('athletePaymentDueDropdown'), 'No athlete with a balance matches.');
    function outstandingDues() {
        const seen = new Map();
        athleteRows.forEach(row => { if (!seen.has(row.dueKey)) seen.set(row.dueKey, row); });
        return [...seen.values()]
            .map(row => ({
                dueKey: row.dueKey, userId: row.userId, feeId: row.feeId, periodKey: row.periodKey,
                name: row.name, fee: row.fee, period: row.period,
                toPay: Number(row.toPay), paid: Number(row.duePaid),
                remaining: Math.max(round2(Number(row.toPay) - Number(row.duePaid)), 0)
            }))
            .filter(d => d.remaining > 0)
            .sort((a, b) => a.name.localeCompare(b.name) || a.fee.localeCompare(b.fee));
    }

    // "Record Athlete Payment" (top button): pick an athlete's due, then log a payment.
    function showRecordAthletePayment() {
        if (!athleteFees.some(fee => fee.active)) { notify('Add an athlete fee first (Athlete fees tab).'); return; }
        const owing = outstandingDues();
        if (!owing.length) { notify('Every athlete is already fully paid.'); return; }

        const form = document.getElementById('athletePaymentForm');
        athleteDialog = { toPay: 0, others: 0, available: 0 };
        athleteSubmitLabel = 'Record';

        ['paymentId', 'userId', 'feeId', 'periodKey', 'relog', 'amount', 'reference'].forEach(name => { form.elements[name].value = ''; });
        setDropdown(document.getElementById('athletePaymentMethodDropdown'), '');
        document.getElementById('athletePaymentMethodDropdown').classList.remove('isInvalid');

        document.getElementById('athletePaymentDueGroup').hidden = false;
        document.getElementById('athletePaymentDueDropdown').classList.remove('isInvalid');
        athletePicker.setItems(owing.map(d => ({
            value: d.dueKey, label: d.name, sub: `${d.fee} (${d.period})`, inputLabel: `${d.name} - ${d.fee} (${d.period})`,
            right: `${money(d.remaining)} left`
        })));

        document.getElementById('athletePaymentReasonGroup').hidden = true;
        document.getElementById('athletePaymentTitle').textContent = 'Recording athlete payment';
        document.getElementById('athletePaymentSubtitle').textContent = 'Choose the athlete who is paying.';
        document.getElementById('athletePaymentLimit').textContent = '';
        document.getElementById('athletePaymentSubmit').textContent = athleteSubmitLabel;

        refreshAthletePreview();
        document.getElementById('athletePaymentDialog').showModal();
    }

    function applyPickedDue() {
        const form = document.getElementById('athletePaymentForm');
        const key = document.getElementById('athletePaymentDue').value;
        const picked = outstandingDues().find(d => d.dueKey === key);

        form.elements.userId.value = picked ? picked.userId : '';
        form.elements.feeId.value = picked ? picked.feeId : '';
        form.elements.periodKey.value = picked ? picked.periodKey : '';
        athleteDialog = picked
            ? { toPay: picked.toPay, others: round2(picked.paid), available: picked.remaining }
            : { toPay: 0, others: 0, available: 0 };

        document.getElementById('athletePaymentSubtitle').textContent = picked ? `Athlete: ${picked.name} - ${picked.fee} (${picked.period})` : 'Choose the athlete who is paying.';
        document.getElementById('athletePaymentLimit').textContent = picked ? `Up to ${money(picked.remaining)} (the remaining balance).` : '';
        refreshAthletePreview();
    }

    function refreshAthletePreview() {
        const amount = Math.max(Number(document.getElementById('athletePaymentAmount').value) || 0, 0);
        const exceeds = round2(amount) > athleteDialog.available;
        const remaining = Math.max(round2(athleteDialog.available - amount), 0);
        const status = statusFor(athleteDialog.others + Math.min(amount, athleteDialog.available), athleteDialog.toPay);

        document.getElementById('athletePaymentBalance').textContent = money(remaining);

        const badgeElement = document.getElementById('athletePaymentStatus');
        badgeElement.className = `sales-badge sales-badge--${status}`;
        badgeElement.textContent = statusLabel(status);

        const error = document.getElementById('athletePaymentError');
        error.hidden = !exceeds;
        error.textContent = exceeds ? `Exceeds the limit of ${money(athleteDialog.available)}.` : '';

        document.getElementById('athletePaymentSubmit').disabled = exceeds || amount <= 0;
    }

    function showFee(fee = null) {
        const form = document.getElementById('feeForm');
        form.reset();
        form.elements.id.value = fee?.id ?? '';
        form.elements.name.value = fee?.name ?? '';
        form.elements.description.value = fee?.description ?? '';
        form.elements.amount.value = fee?.amount ?? '';
        form.elements.active.checked = fee ? fee.active : true;
        setDropdown(document.getElementById('feeCycleDropdown'), fee?.cycle || 'monthly');
        const editing = Boolean(fee);
        document.getElementById('feeDialogTitle').textContent = editing ? 'Updating Athlete Fee' : 'Adding Athlete Fee';
        document.getElementById('feeDialogSubtitle').textContent = editing ? 'Athlete fee' : 'What athletes need to pay';
        document.getElementById('feeSubmit').textContent = editing ? 'Update' : 'Add';
        document.getElementById('feeDialog').showModal();
    }

    function askRemove(message, callback, options = {}) {
        pendingRemoval = callback;
        pendingOptions = options;
        document.getElementById('confirmReasonGroup').hidden = !options.requireReason;
        document.getElementById('confirmReason').value = '';
        document.getElementById('confirmTitle').textContent = options.title || 'Remove this record?';
        document.getElementById('confirmDelete').textContent = options.label || 'Remove';
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmDialog').showModal();
    }

    document.querySelectorAll('[data-sales-tab]').forEach(button => button.addEventListener('click', () => setTab(button.dataset.salesTab)));
    search.addEventListener('input', render);
    statusFilterRoot.addEventListener('dropdown:change', render);
    [itemMonthRoot, itemNameRoot, itemMethodRoot].forEach(root => root.addEventListener('dropdown:change', render));
    itemFilterClear.addEventListener('click', () => { clearItemFilters(); render(); });
    recordFilterRoot.addEventListener('dropdown:change', () => {
        // Paid / Unpaid / Partial mean nothing for archived rows.
        const archived = viewingArchived();
        if (archived) setDropdown(statusFilterRoot, '');
        statusFilterRoot.hidden = archived;
        render();
    });
    addButton.addEventListener('click', () => activeTab === 'items' ? showItem() : activeTab === 'logs' ? showPurchase() : activeTab === 'athletes' ? showRecordAthletePayment() : activeTab === 'fees' ? showFee() : showRecordPayment());
    document.getElementById('purchaseItemDropdown').addEventListener('dropdown:change', refreshPurchasePrice);
    document.getElementById('purchaseQuantity').addEventListener('input', refreshPurchasePrice);

    page.addEventListener('click', event => {
        const target = event.target.closest('button');
        if (!target) return;
        if (target.matches('[data-course-edit]')) showCoursePayment(byId(courseRows, target.dataset.courseEdit));
        if (target.matches('[data-all-receipt]')) {
            const kind = target.dataset.allKind;   // 'course' | 'item' | 'athlete'
            const source = kind === 'course' ? courseRows : kind === 'athlete' ? athleteRows : logs;
            showReceipt(byId(source, target.dataset.allReceipt), kind);
        }
        if (target.matches('[data-course-receipt]')) showReceipt(byId(courseRows, target.dataset.courseReceipt), 'course');
        if (target.matches('[data-course-archive]')) {
            const row = byId(courseRows, target.dataset.courseArchive);
            askRemove(`Archive the ${money(row.paid)} payment (${row.receipt}) from ${row.name}? It will stop counting toward their balance, but it stays on file and can be re-logged from the Archived filter.`, async reason => {
                try {
                    const result = await sendJson(`{{ url('/sales/payments') }}/${row.id}/archive`, 'POST', { reason });
                    if (!result.ok) { notify(errorText(result, 'Could not archive the payment'), 'danger'); return false; }
                    await loadData();
                    return `Archived ${row.receipt} for ${row.name}.`;
                } catch (error) {
                    console.error(error);
                    notify('Something went wrong while archiving the payment.', 'danger');
                    return false;
                }
            }, { title: 'Archive this payment?', label: 'Archive', requireReason: true });
        }
        if (target.matches('[data-course-relog]')) showRelogPayment(byId(archivedCourseRows, target.dataset.courseRelog));
        if (target.matches('[data-log-edit]')) showPurchase(byId(logs, target.dataset.logEdit));
        if (target.matches('[data-log-receipt]')) showReceipt(byId(logs, target.dataset.logReceipt), 'item');
        if (target.matches('[data-log-delete]')) {
            const row = byId(logs, target.dataset.logDelete);
            askRemove(`Remove the ${row.item} sale from the current preview?`, () => { logs = logs.filter(item => item.id !== row.id); render(); });
        }
        if (target.matches('[data-item-edit]')) showItem(byId(items, target.dataset.itemEdit));
        if (target.matches('[data-item-delete]')) {
            const item = byId(items, target.dataset.itemDelete);
            askRemove(`Remove ${item.name} from the current catalog preview? Previous item logs will remain visible.`, () => { items = items.filter(row => row.id !== item.id); render(); });
        }
        if (target.matches('[data-athlete-edit]')) showAthletePayment(byId(athleteRows, target.dataset.athleteEdit));
        if (target.matches('[data-athlete-archive]')) {
            const row = byId(athleteRows, target.dataset.athleteArchive);
            askRemove(`Archive the ${money(row.paid)} payment (${row.receipt}) from ${row.name} for ${row.fee} (${row.period})? It will stop counting toward that fee, but it stays on file and can be re-logged from the Archived filter.`, async reason => {
                try {
                    const result = await sendJson(`{{ url('/sales/athlete-payments') }}/${row.id}/archive`, 'POST', { reason });
                    if (!result.ok) { notify(errorText(result, 'Could not archive the payment'), 'danger'); return false; }
                    await loadData();
                    return `Archived ${row.receipt} for ${row.name}.`;
                } catch (error) {
                    console.error(error);
                    notify('Something went wrong while archiving the payment.', 'danger');
                    return false;
                }
            }, { title: 'Archive this payment?', label: 'Archive', requireReason: true });
        }
        if (target.matches('[data-athlete-relog]')) showRelogAthletePayment(byId(archivedAthleteRows, target.dataset.athleteRelog));
        if (target.matches('[data-athlete-receipt]')) showReceipt(byId(athleteRows, target.dataset.athleteReceipt), 'athlete');
        if (target.matches('[data-fee-edit]')) showFee(byId(athleteFees, target.dataset.feeEdit));
        if (target.matches('[data-fee-delete]')) {
            const fee = byId(athleteFees, target.dataset.feeDelete);
            askRemove(`Remove ${fee.name}? If athletes already paid it, it is archived instead so the payment history stays intact.`, async () => {
                try {
                    const result = await sendJson(`{{ url('/sales/athlete-fees') }}/${fee.id}`, 'DELETE');
                    if (!result.ok) { notify(errorText(result, 'Could not remove the fee'), 'danger'); return false; }
                    await loadData();
                    return result.data.archived ? `${fee.name} was archived (it already has payments).` : `${fee.name} was removed.`;
                } catch (error) {
                    console.error(error);
                    notify('Something went wrong while removing the fee.', 'danger');
                    return false;
                }
            });
        }
    });

    document.getElementById('coursePaymentForm').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const submitButton = document.getElementById('coursePaymentSubmit');
        const enrollmentId = Number(form.elements.id.value);
        const paymentId = Number(form.elements.paymentId.value) || null;
        const relog = form.elements.relog.value === '1';
        const reason = form.elements.reason.value.trim();

        if (paymentId && !relog && reason.length < 3) {
            document.getElementById('coursePaymentReason').focus();
            notify('Enter the reason for this change.', 'danger');
            return;
        }

        if (!enrollmentId) {
            document.getElementById('coursePaymentEnrollmentDropdown').classList.add('isInvalid');
            document.getElementById('coursePaymentEnrollmentToggle').focus();
            notify('Select a student.', 'danger');
            return;
        }

        const row = courseRows.find(item => item.enrollmentId === enrollmentId);
        if (!row) { notify('This enrollment is no longer in the list.', 'danger'); return; }

        if (!form.elements.method.value) {
            document.getElementById('coursePaymentMethodDropdown').classList.add('isInvalid');
            document.getElementById('coursePaymentMethodToggle').focus();
            notify('Select a payment method.', 'danger');
            return;
        }

        const amount = Math.max(Number(form.elements.amount.value) || 0, 0);
        if (amount <= 0) { notify('Enter a payment amount greater than zero.', 'danger'); return; }
        if (round2(amount) > paymentDialog.available) { notify(`Payment exceeds the limit of ${money(paymentDialog.available)}.`, 'danger'); return; }

        submitButton.disabled = true;
        submitButton.textContent = relog ? 'Re-logging...' : paymentId ? 'Updating...' : 'Recording...';

        // Editing a payment log -> PUT that payment; a new payment ("Record
        // Course Payment" or an unpaid row) -> POST a new log row.
        const url = relog
            ? `{{ url('/sales/payments') }}/${paymentId}/restore`
            : paymentId
                ? `{{ url('/sales/payments') }}/${paymentId}`
                : `{{ url('/sales/enrollments') }}/${enrollmentId}/payments`;

        try {
            const response = await fetch(url, {
                method: paymentId && !relog ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    amount: amount,
                    payment_method: form.elements.method.value || null,
                    reference_number: form.elements.reference.value.trim() || null,
                    reason: paymentId && !relog ? reason : undefined
                })
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                document.getElementById('coursePaymentDialog').close();
                await loadData();
                notify(relog ? `Successfully re-logged the payment for ${row.name}.` : paymentId ? `Successfully updated the payment for ${row.name}.` : `Successfully recorded the payment for ${row.name}.`);
            } else {
                const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                notify(firstError || data.message || `Could not update the payment (error ${response.status}).`, 'danger');
            }
        } catch (error) {
            console.error(error);
            notify('Something went wrong while updating the payment.', 'danger');
        } finally {
            submitButton.textContent = paymentSubmitLabel;
            refreshPaymentPreview();
        }
    });

    document.getElementById('coursePaymentAmount').addEventListener('input', refreshPaymentPreview);
    document.getElementById('coursePaymentEnrollmentDropdown').addEventListener('dropdown:change', event => {
        event.currentTarget.classList.remove('isInvalid');
        applyPickedEnrollment();
    });
    document.getElementById('coursePaymentMethodDropdown').addEventListener('dropdown:change', event => event.currentTarget.classList.remove('isInvalid'));

    document.getElementById('purchaseForm').addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget;
        const item = byId(items, form.elements.itemId.value);
        const quantity = Number(form.elements.quantity.value);
        const paid = Number(form.elements.paid.value);
        if (!item) { notify('Choose an item from the catalog.'); return; }
        const total = Number(item.price) * quantity;
        if (!item || quantity < 1 || paid > total) { notify('Check quantity and payment amount.'); return; }
        const id = form.elements.id.value;
        const existing = id ? byId(logs, id) : null;
        const originalItem = existing ? byId(items, existing.itemId) : null;
        const previousQuantity = existing?.quantity || 0;
        const stockAfterReversal = Number(item.stock) + (originalItem?.id === item.id ? previousQuantity : 0);
        if (quantity > stockAfterReversal) { notify(`Only ${stockAfterReversal} ${item.name} available.`); return; }
        const record = { id: existing?.id || Date.now(), itemId: item.id, item: item.name, description: form.elements.description.value.trim(), quantity, price: Number(item.price), paid, method: form.elements.method.value, date: dateLabel(), client: existing?.client || '', sortAt: existing?.sortAt || nowStamp(), receipt: existing?.receipt || `ITEM-${new Date().getFullYear()}-${String(logs.length + 1).padStart(3, '0')}` };
        if (originalItem && originalItem.id !== item.id) originalItem.stock += previousQuantity;
        item.stock = stockAfterReversal - quantity;
        if (existing) Object.assign(existing, record); else logs.unshift(record);
        document.getElementById('purchaseDialog').close();
        render();
        notify(existing ? 'Purchase updated in this preview.' : 'Purchase added to this preview.');
    });

    document.getElementById('itemForm').addEventListener('submit', event => {
        event.preventDefault();
        const form = event.currentTarget;
        const id = form.elements.id.value;
        const item = { id: id ? Number(id) : Date.now(), name: form.elements.name.value.trim(), description: form.elements.description.value.trim(), price: Number(form.elements.price.value), stock: Number(form.elements.stock.value) };
        if (id) Object.assign(byId(items, id), item); else items.push(item);
        document.getElementById('itemDialog').close();
        render();
        notify(id ? 'Item updated in this preview.' : 'Item added to this preview.');
    });

    document.getElementById('athletePaymentForm').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const submitButton = document.getElementById('athletePaymentSubmit');
        const paymentId = Number(form.elements.paymentId.value) || null;
        const relog = form.elements.relog.value === '1';
        const reason = form.elements.reason.value.trim();
        const userId = Number(form.elements.userId.value);

        if (paymentId && !relog && reason.length < 3) {
            document.getElementById('athletePaymentReason').focus();
            notify('Enter the reason for this change.', 'danger');
            return;
        }
        const feeId = Number(form.elements.feeId.value);

        if (!userId || !feeId) {
            document.getElementById('athletePaymentDueDropdown').classList.add('isInvalid');
            document.getElementById('athletePaymentDueToggle').focus();
            notify('Select an athlete.', 'danger');
            return;
        }
        if (!form.elements.method.value) {
            document.getElementById('athletePaymentMethodDropdown').classList.add('isInvalid');
            document.getElementById('athletePaymentMethodToggle').focus();
            notify('Select a payment method.', 'danger');
            return;
        }

        const amount = Math.max(Number(form.elements.amount.value) || 0, 0);
        if (amount <= 0) { notify('Enter a payment amount greater than zero.', 'danger'); return; }
        if (round2(amount) > athleteDialog.available) { notify(`Payment exceeds the limit of ${money(athleteDialog.available)}.`, 'danger'); return; }

        const athleteName = [...athleteRows, ...archivedAthleteRows].find(row => row.userId === userId && row.feeId === feeId)?.name || 'the athlete';
        const body = {
            amount,
            payment_method: form.elements.method.value,
            reference_number: form.elements.reference.value.trim() || null
        };
        if (paymentId && !relog) body.reason = reason;
        if (!paymentId) {
            body.user_id = userId;
            body.athlete_fee_id = feeId;
            body.period_key = form.elements.periodKey.value || null;
        }

        submitButton.disabled = true;
        submitButton.textContent = relog ? 'Re-logging...' : paymentId ? 'Updating...' : 'Recording...';

        try {
            const result = await sendJson(
                relog ? `{{ url('/sales/athlete-payments') }}/${paymentId}/restore`
                    : paymentId ? `{{ url('/sales/athlete-payments') }}/${paymentId}` : `{{ url('/sales/athlete-payments') }}`,
                paymentId && !relog ? 'PUT' : 'POST',
                body
            );
            if (result.ok) {
                document.getElementById('athletePaymentDialog').close();
                await loadData();
                notify(relog ? `Successfully re-logged the payment for ${athleteName}.` : paymentId ? `Successfully updated the payment for ${athleteName}.` : `Successfully recorded the payment for ${athleteName}.`);
            } else {
                notify(errorText(result, 'Could not save the payment'), 'danger');
            }
        } catch (error) {
            console.error(error);
            notify('Something went wrong while saving the payment.', 'danger');
        } finally {
            submitButton.textContent = athleteSubmitLabel;
            refreshAthletePreview();
        }
    });

    document.getElementById('athletePaymentAmount').addEventListener('input', refreshAthletePreview);
    document.getElementById('athletePaymentDueDropdown').addEventListener('dropdown:change', event => {
        event.currentTarget.classList.remove('isInvalid');
        applyPickedDue();
    });
    document.getElementById('athletePaymentMethodDropdown').addEventListener('dropdown:change', event => event.currentTarget.classList.remove('isInvalid'));

    document.getElementById('feeForm').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const submitButton = document.getElementById('feeSubmit');
        const id = Number(form.elements.id.value) || null;
        const label = submitButton.textContent;

        submitButton.disabled = true;
        try {
            const result = await sendJson(
                id ? `{{ url('/sales/athlete-fees') }}/${id}` : `{{ url('/sales/athlete-fees') }}`,
                id ? 'PUT' : 'POST',
                {
                    fee_name: form.elements.name.value.trim(),
                    description: form.elements.description.value.trim() || null,
                    amount: Number(form.elements.amount.value),
                    billing_cycle: form.elements.cycle.value,
                    is_active: form.elements.active.checked
                }
            );
            if (result.ok) {
                document.getElementById('feeDialog').close();
                await loadData();
                notify(id ? 'Successfully updated the athlete fee.' : 'Successfully added the athlete fee.');
            } else {
                notify(errorText(result, 'Could not save the fee'), 'danger');
            }
        } catch (error) {
            console.error(error);
            notify('Something went wrong while saving the fee.', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = label;
        }
    });

    // The callback may be async (real server action). It returns the toast text,
    // or false when it already showed its own error. Preview-only callbacks return nothing.
    document.getElementById('confirmDelete').addEventListener('click', async () => {
        const reasonInput = document.getElementById('confirmReason');
        const reason = reasonInput.value.trim();
        if (pendingOptions.requireReason && reason.length < 3) {
            notify('Enter a reason (at least 3 characters).', 'danger');
            reasonInput.focus();
            return;
        }
        const action = pendingRemoval;
        pendingRemoval = null;
        document.getElementById('confirmDialog').close();
        const message = await action?.(reason);
        if (message === false) return;
        notify(message || 'Removed from this preview.');
    });
    document.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') pendingRemoval = null; });
    async function loadData() {
        try {
            const response = await fetch('{{ url('/sales/data') }}', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const data = await response.json();
            courseRows = data.coursePayments;
            archivedCourseRows = data.archivedCoursePayments || [];
            items = data.itemCatalog;
            logs = data.itemLogs;
            athleteRows = data.athletePayments || [];
            archivedAthleteRows = data.archivedAthletePayments || [];
            athleteFees = data.athleteFees || [];
            render();
        } catch (error) {
            console.error(error);
            notify('Could not load the sales data.', 'danger');
        }
    }

    setTab('course');
    render();
    loadData();
})();
</script>