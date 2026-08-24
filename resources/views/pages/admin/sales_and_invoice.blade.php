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

/* Long student names must not push the dropdown out of the dialog. */
#coursePaymentEnrollmentDropdown .registerDropdownToggle span { flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: left; }
#coursePaymentEnrollmentDropdown .registerDropdownOptions { max-height: 220px; overflow-y: auto; }
#coursePaymentEnrollmentDropdown .registerDropdownOption { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

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

.sales-receipt-toolbar {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .5rem;
    padding: .6rem .75rem;
    background: #fff;
    border-bottom: 1px solid var(--sales-line);
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

@media print {
    body * {
        visibility: hidden !important;
    }

    .sales-receipt-dialog[open], .sales-receipt-dialog[open] * {
        visibility: visible !important;
    }

    .sales-receipt-dialog[open] {
        position: fixed;
        inset: 0 auto auto 0;
        width: 100%;
        max-width: 465px;
        max-height: none;
        overflow: visible;
        box-shadow: none;
    }

    .sales-receipt-toolbar {
        display: none !important;
    }

}
</style>

<section class="sales-page" id="salesPage" aria-labelledby="salesTitle">
    <header class="sales-heading">
        <div>
            <h2 class="mb-1" id="salesTitle">Billing &amp; Receipts</h2>
            <p class="text-muted mb-0">Course payments, item sales, and receipts in one place</p>
        </div>
        <button class="btn btn-primary btn-icon-label" id="salesAddButton" type="button"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add New Purchase</span></button>
    </header>

    <nav class="btn-tab-group sales-tabs" aria-label="Billing sections" role="tablist">
        <button class="btn btn-outline-primary btn-tab" id="salesTabAll" type="button" role="tab" aria-selected="false" aria-controls="salesPanelAll" data-sales-tab="all">All</button>
        <button class="btn btn-outline-primary btn-tab active" id="salesTabCourse" type="button" role="tab" aria-selected="true" aria-controls="salesPanelCourse" data-sales-tab="course">Course payment</button>
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
        </div>
    </div>

    <section class="sales-panel" id="salesPanelAll" role="tabpanel" aria-labelledby="salesTabAll" data-sales-panel="all" hidden>
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Type</th><th>Name</th><th>Client</th><th>Total</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Receipt</th></tr></thead><tbody id="allRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelCourse" role="tabpanel" aria-labelledby="salesTabCourse" data-sales-panel="course">
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Course</th><th>Name</th><th>Price</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Updated</th><th>Actions</th><th>Receipt</th></tr></thead><tbody id="coursePaymentRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelLogs" role="tabpanel" aria-labelledby="salesTabLogs" data-sales-panel="logs" hidden>
        <div class="sales-table-wrap"><table class="table sales-table"><thead><tr><th>Item name</th><th>Description</th><th>Quantity</th><th>Price</th><th>To pay</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Actions</th><th>Receipt</th></tr></thead><tbody id="itemLogRows"></tbody></table></div>
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
            <div class="sales-field" id="coursePaymentEnrollmentGroup" hidden><label for="coursePaymentEnrollmentToggle">Student <span class="sales-required">*</span></label><div class="registerDropdown ddBlock" id="coursePaymentEnrollmentDropdown" data-fbss-dropdown><button class="registerDropdownToggle" id="coursePaymentEnrollmentToggle" type="button" aria-expanded="false" aria-controls="coursePaymentEnrollmentOptions"><span>Select student</span><i class="bi bi-chevron-down biChevronDown" aria-hidden="true"></i></button><div class="registerDropdownOptions" id="coursePaymentEnrollmentOptions" hidden></div><input id="coursePaymentEnrollment" type="hidden" value=""></div></div>
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

<dialog class="sales-dialog sales-receipt-dialog" id="receiptDialog" aria-label="Receipt">
    <div class="sales-receipt-toolbar"><button class="btn btn-primary btn-sm btn-icon-label" type="button" id="printReceipt"><i class="bi bi-printer" aria-hidden="true"></i> Print</button><button class="btn btn-icon btn-icon-ghost btn-icon-sm" type="button" data-dialog-close aria-label="Close receipt"><i class="bi bi-x-lg"></i></button></div>
    <article class="sales-receipt" id="receiptContent"></article>
</dialog>

<dialog class="sales-dialog" id="confirmDialog" aria-labelledby="confirmTitle">
    <div class="sales-dialog-inner"><h2 id="confirmTitle">Remove this record?</h2><p class="sales-dialog-subtitle" id="confirmMessage">This action only changes this preview until the page is reloaded.</p><div class="sales-dialog-actions"><button class="btn btn-outline-primary" type="button" data-dialog-close>Cancel</button><button class="btn btn-danger" type="button" id="confirmDelete">Remove</button></div></div>
</dialog>

<script src="{{ asset('assets/js/fbss-dropdown.js') }}"></script>
<script>
(function () {
    const page = document.getElementById('salesPage');
    if (!page) return;

    // Filled by loadData() from GET /sales/data (SalesInvoiceController).
    let courseRows = [];
    let items = [];
    let logs = [];
    let activeTab = 'course';
    let pendingRemoval = null;
    const money = value => `₱${Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 })}`;
    const nowStamp = () => { const d = new Date(), p = n => String(n).padStart(2, '0'); return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`; };
    const dateLabel = () => {
        const date = new Date();
        const month = date.toLocaleString('en-US', { month: 'short' }).toUpperCase();
        return `${month}-${String(date.getDate()).padStart(2, '0')}-${date.getFullYear()}`;
    };
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
    const statusFor = (paid, total) => Number(paid) <= 0 ? 'unpaid' : Number(paid) >= Number(total) ? 'paid' : 'partial';
    const statusLabel = status => ({ paid: 'Paid', unpaid: 'Unpaid', partial: 'Partial' }[status] || 'Unpaid');
    const badge = status => `<span class="sales-badge sales-badge--${status}">${statusLabel(status)}</span>`;
    const byId = (list, id) => list.find(row => Number(row.id) === Number(id));
    const search = document.getElementById('salesSearch');
    const statusFilter = document.getElementById('salesStatusFilter');            // hidden input holding the value
    const statusFilterRoot = document.getElementById('salesStatusFilterDropdown'); // the shared dropdown

    // Selects `value` on a shared dropdown (silent). Resets to the first option
    // first, so an unknown value (e.g. an old payment method) can't leave a stale pick.
    function setDropdown(root, value) {
        const first = root.querySelector('.registerDropdownOption');
        if (first) window.FBSSDropdown.set(root, first.dataset.value ?? '');
        if (value !== '' && value != null) window.FBSSDropdown.set(root, value);
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
        const sales = logs.map(row => ({ total: Number(row.price) * Number(row.quantity), paid: Number(row.paid) }));
        return {
            collected: sales.reduce((total, s) => total + s.paid, 0),
            outstanding: sales.reduce((total, s) => total + Math.max(s.total - s.paid, 0), 0),
            paid: sales.reduce((total, s) => total + (s.paid > 0 && s.paid >= s.total ? s.paid : 0), 0),
            unpaid: sales.reduce((total, s) => total + (s.paid <= 0 ? s.total : 0), 0)
        };
    }

    function updateStats() {
        document.getElementById('salesStats').hidden = activeTab === 'items';
        const course = courseStats();
        const item = itemStats();
        const stats = activeTab === 'course' ? course
            : activeTab === 'logs' ? item
            : {
                collected: course.collected + item.collected,
                outstanding: course.outstanding + item.outstanding,
                paid: course.paid + item.paid,
                unpaid: course.unpaid + item.unpaid
            };
        document.getElementById('salesTotalCollected').textContent = money(stats.collected);
        document.getElementById('salesOutstanding').textContent = money(stats.outstanding);
        document.getElementById('salesPaid').textContent = money(stats.paid);
        document.getElementById('salesUnpaid').textContent = money(stats.unpaid);
    }

    function renderCourseRows() {
        const { text, status } = currentFilter();
        const visible = courseRows.filter(row => {
            const rowStatus = row.status;
            const haystack = `${row.course} ${row.name} ${row.method} ${row.date}`.toLocaleLowerCase();
            return (!status || status === rowStatus) && (!text || haystack.includes(text));
        });
        document.getElementById('coursePaymentRows').innerHTML = visible.length ? visible.map(row => {
            const balance = Number(row.balance);
            return `<tr><td class="sales-name">${escapeHtml(row.course)}</td><td>${escapeHtml(row.name)}</td><td>${money(row.price)}</td><td>${money(row.paid)}</td><td>${money(balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(row.status)}</td><td>${escapeHtml(row.date)}</td><td>${escapeHtml(row.updatedAt || '--')}</td><td><span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-course-edit="${row.id}" aria-label="Record payment for ${escapeHtml(row.name)}" title="Record payment"><i class="bi bi-pencil"></i></button><button class="btn btn-icon btn-icon-sm btn-outline-danger" type="button" data-course-delete="${row.id}" aria-label="Remove ${escapeHtml(row.name)}" title="Remove"><i class="bi bi-trash3"></i></button></span></td><td><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-course-receipt="${row.id}" aria-label="View receipt for ${escapeHtml(row.name)}" title="View receipt"><i class="bi bi-eye"></i></button></td></tr>`;
        }).join('') : '<tr><td class="sales-empty" colspan="11">No course payments match your search.</td></tr>';
    }

    function renderLogs() {
        const { text, status } = currentFilter();
        const visible = logs.filter(row => {
            const total = Number(row.price) * Number(row.quantity);
            const rowStatus = statusFor(row.paid, total);
            const haystack = `${row.item} ${row.description} ${row.client} ${row.method} ${row.date}`.toLocaleLowerCase();
            return (!status || status === rowStatus) && (!text || haystack.includes(text));
        });
        document.getElementById('itemLogRows').innerHTML = visible.length ? visible.map(row => {
            const total = Number(row.price) * Number(row.quantity);
            const balance = Math.max(total - Number(row.paid), 0);
            return `<tr><td class="sales-name">${escapeHtml(row.item)}</td><td>${escapeHtml(row.description || '--')}</td><td>${Number(row.quantity)}</td><td>${money(row.price)}</td><td>${money(total)}</td><td>${money(row.paid)}</td><td>${money(balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(statusFor(row.paid, total))}</td><td>${escapeHtml(row.date)}</td><td><span class="btn-row-actions"><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-log-edit="${row.id}" aria-label="Edit ${escapeHtml(row.item)} sale" title="Edit"><i class="bi bi-pencil"></i></button><button class="btn btn-icon btn-icon-sm btn-outline-danger" type="button" data-log-delete="${row.id}" aria-label="Remove ${escapeHtml(row.item)} sale" title="Remove"><i class="bi bi-trash3"></i></button></span></td><td><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-log-receipt="${row.id}" aria-label="View receipt for ${escapeHtml(row.item)}" title="View receipt"><i class="bi bi-eye"></i></button></td></tr>`;
        }).join('') : '<tr><td class="sales-empty" colspan="12">No item logs match your search.</td></tr>';
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
        })).sort((a, b) => String(b.sortAt).localeCompare(String(a.sortAt)));

        const visible = rows.filter(row => {
            const haystack = `${row.type} ${row.name} ${row.client} ${row.method} ${row.date}`.toLocaleLowerCase();
            return (!status || status === row.status) && (!text || haystack.includes(text));
        });
        document.getElementById('allRows').innerHTML = visible.length ? visible.map(row =>
            `<tr><td>${row.type}</td><td class="sales-name">${escapeHtml(row.name)}</td><td>${escapeHtml(row.client)}</td><td>${money(row.total)}</td><td>${money(row.paid)}</td><td>${money(row.balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(row.status)}</td><td>${escapeHtml(row.date)}</td><td><button class="btn btn-icon btn-icon-sm btn-outline-primary" type="button" data-all-receipt="${row.id}" data-all-kind="${row.kind}" aria-label="View receipt for ${escapeHtml(row.name)}" title="View receipt"><i class="bi bi-eye"></i></button></td></tr>`
        ).join('') : '<tr><td class="sales-empty" colspan="10">No records match your search.</td></tr>';
    }

    function render() { renderAll(); renderCourseRows(); renderLogs(); renderItems(); updateStats(); }

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
        const labels = { all: '', course: 'Record Course Payment', logs: 'Add New Purchase', items: 'Add New Item' };
        addButton.querySelector('span').textContent = labels[tab];
        addButton.hidden = tab === 'all';   // nothing to add from the combined view
        statusFilterRoot.hidden = tab === 'items';
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

        document.getElementById('coursePaymentEnrollmentGroup').hidden = true;
        document.getElementById('coursePaymentSubmit').textContent = paymentSubmitLabel;
        form.elements.id.value = row.enrollmentId;
        form.elements.paymentId.value = editing ? row.id : '';
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
    }

    // Enrollments that still owe something. Course rows are one per payment,
    // so take each enrollment once (every row of it carries the same totals).
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

        form.elements.id.value = '';
        form.elements.paymentId.value = '';
        form.elements.amount.value = '';
        setDropdown(document.getElementById('coursePaymentMethodDropdown'), '');
        document.getElementById('coursePaymentMethodDropdown').classList.remove('isInvalid');
        form.elements.reference.value = '';

        document.getElementById('coursePaymentEnrollmentGroup').hidden = false;
        document.getElementById('coursePaymentEnrollmentDropdown').classList.remove('isInvalid');
        window.FBSSDropdown.setOptions(
            document.getElementById('coursePaymentEnrollmentDropdown'),
            [{ value: '', label: 'Select student' }].concat(owing.map(e => ({
                value: e.enrollmentId,
                // Just the name. Only when two people share a name, add the course to tell them apart.
                label: owing.filter(o => o.name === e.name).length > 1 ? `${e.name} (${e.course})` : e.name
            }))),
            ''
        );

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
        const total = isCourse ? Number(row.toPay) : Number(row.price) * Number(row.quantity);
        const paid = Number(row.paid);
        const balance = isCourse ? Number(row.balance) : Math.max(total - paid, 0);
        const status = isCourse ? row.status : statusFor(paid, total);
        const client = isCourse ? row.name : (row.client || 'Walk-in customer');
        const description = isCourse ? row.course : row.item;
        const quantityLine = isCourse ? 'Course enrollment' : `${row.quantity} \u00d7 ${money(row.price)}`;
        const invoice = row.receipt || `${isCourse ? 'INV' : 'ITEM'}-${new Date().getFullYear()}-${String(row.id).padStart(4, '0')}`;
        const labels = isCourse ? ['Course price', 'This payment', 'Balance due'] : ['To pay', 'Amount paid', 'Balance due'];

        const metaRows = [['Receipt #', invoice], ['Date', row.date], ['Client', client]];
        if (isCourse && row.reference) metaRows.push(['Reference #', row.reference]);
        const meta = metaRows.map(([label, value]) => `<span>${label}</span><strong>${escapeHtml(value)}</strong>`).join('');

        const paidToDate = isCourse
            ? `<div class="sales-receipt-line"><span>Total paid to date</span><strong>${money(row.paidToDate)}</strong></div>`
            : '';

        document.getElementById('receiptContent').innerHTML =
            `<header class="sales-receipt-head"><div class="sales-receipt-brand"><span class="sales-receipt-brand-mark">FB</span> FBSS SwimOS ${badge(status)}</div><strong>Froy Bartolome Swimming School</strong><small>R3M+2Q9, Del Monte - Norzagaray Rd, SJDM, Bulacan<br>(+63) 966 203 1918</small></header>` +
            `<div class="sales-receipt-body"><div class="sales-receipt-meta">${meta}</div>` +
            `<p class="sales-receipt-section">${isCourse ? 'Course purchased' : 'Items purchased'}</p>` +
            `<div class="sales-receipt-line"><span>${escapeHtml(description)}<small>${escapeHtml(quantityLine)}</small></span><strong>${money(total)}</strong></div>` +
            paidToDate +
            `<div class="sales-receipt-amounts"><div class="sales-receipt-amount"><small>${labels[0]}</small><strong>${money(total)}</strong></div><div class="sales-receipt-amount"><small>${labels[1]}</small><strong>${money(paid)}</strong></div><div class="sales-receipt-amount"><small>${labels[2]}</small><strong>${money(balance)}</strong></div></div>` +
            `<div class="sales-receipt-method">Payment Method: <strong>${escapeHtml(row.method || '--')}</strong></div>` +
            `<p class="sales-receipt-thanks">Thank You for Your Payment!</p><p class="sales-receipt-footer">We look forward to seeing you at the pool</p></div>`;
        document.getElementById('receiptDialog').showModal();
    }

    function askRemove(message, callback) {
        pendingRemoval = callback;
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmDialog').showModal();
    }

    document.querySelectorAll('[data-sales-tab]').forEach(button => button.addEventListener('click', () => setTab(button.dataset.salesTab)));
    search.addEventListener('input', render);
    statusFilterRoot.addEventListener('dropdown:change', render);
    addButton.addEventListener('click', () => activeTab === 'items' ? showItem() : activeTab === 'logs' ? showPurchase() : showRecordPayment());
    document.getElementById('purchaseItemDropdown').addEventListener('dropdown:change', refreshPurchasePrice);
    document.getElementById('purchaseQuantity').addEventListener('input', refreshPurchasePrice);

    page.addEventListener('click', event => {
        const target = event.target.closest('button');
        if (!target) return;
        if (target.matches('[data-course-edit]')) showCoursePayment(byId(courseRows, target.dataset.courseEdit));
        if (target.matches('[data-all-receipt]')) {
            const isCourse = target.dataset.allKind === 'course';
            showReceipt(byId(isCourse ? courseRows : logs, target.dataset.allReceipt), isCourse ? 'course' : 'item');
        }
        if (target.matches('[data-course-receipt]')) showReceipt(byId(courseRows, target.dataset.courseReceipt), 'course');
        if (target.matches('[data-course-delete]')) {
            const row = byId(courseRows, target.dataset.courseDelete);
            askRemove(`Remove ${row.name} and this course payment row from the current preview?`, () => { courseRows = courseRows.filter(item => item.id !== row.id); render(); });
        }
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
    });

    document.getElementById('coursePaymentForm').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        const submitButton = document.getElementById('coursePaymentSubmit');
        const enrollmentId = Number(form.elements.id.value);
        const paymentId = Number(form.elements.paymentId.value) || null;

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
        submitButton.textContent = paymentId ? 'Updating...' : 'Recording...';

        // Editing a payment log -> PUT that payment; a new payment ("Record
        // Course Payment" or an unpaid row) -> POST a new log row.
        const url = paymentId
            ? `{{ url('/sales/payments') }}/${paymentId}`
            : `{{ url('/sales/enrollments') }}/${enrollmentId}/payments`;

        try {
            const response = await fetch(url, {
                method: paymentId ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    amount: amount,
                    payment_method: form.elements.method.value || null,
                    reference_number: form.elements.reference.value.trim() || null
                })
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                document.getElementById('coursePaymentDialog').close();
                await loadData();
                notify(paymentId ? `Successfully updated the payment for ${row.name}.` : `Successfully recorded the payment for ${row.name}.`);
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

    document.getElementById('confirmDelete').addEventListener('click', () => {
        pendingRemoval?.();
        pendingRemoval = null;
        document.getElementById('confirmDialog').close();
        notify('Removed from this preview.');
    });
    document.getElementById('printReceipt').addEventListener('click', () => window.print());
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
            items = data.itemCatalog;
            logs = data.itemLogs;
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