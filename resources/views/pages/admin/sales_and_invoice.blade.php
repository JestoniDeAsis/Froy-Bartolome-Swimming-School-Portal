<style>
.sales-page {
    --sales-blue: #0757a5;
    --sales-blue-dark: #03447f;
    --sales-ink: #16202c;
    --sales-muted: #64748b;
    --sales-line: #dce5ed;
    --sales-canvas: #f4f7f6;
    color: var(--sales-ink);
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 0;
    padding: 1.25rem 1.5rem 2rem;
}

.sales-page *, .sales-page *::before, .sales-page *::after {
    box-sizing: border-box;
}

.sales-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
}

.sales-heading h1 {
    margin: 0;
    font-size: 1.4rem;
    line-height: 1.2;
    font-weight: 700;
}

.sales-heading p {
    color: var(--sales-muted);
    margin: .25rem 0 0;
    font-size: .82rem;
}

.sales-add-button, .sales-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    min-height: 40px;
    border: 1px solid transparent;
    border-radius: 7px;
    padding: .55rem .9rem;
    font: inherit;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
}

.sales-add-button, .sales-button-primary {
    color: #fff;
    background: var(--sales-blue);
}

.sales-add-button:hover, .sales-button-primary:hover {
    background: var(--sales-blue-dark);
}

.sales-button-secondary {
    background: #fff;
    color: var(--sales-blue);
    border-color: var(--sales-blue);
}

.sales-button-danger {
    background: #fff;
    color: #c73535;
    border-color: #eccaca;
}

.sales-button:disabled {
    opacity: .55;
    cursor: not-allowed;
}

.sales-tabs {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}

.sales-tab {
    min-width: 0;
    width: min(259px, calc((100vw - 340px) / 3));
    height: 46px;
    border: 1px solid var(--sales-line);
    border-radius: 7px;
    background: #fff;
    color: var(--sales-ink);
    font: inherit;
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    cursor: pointer;
}

.sales-tab[aria-selected="true"] {
    color: #fff;
    border-color: var(--sales-blue);
    background: var(--sales-blue);
}

.sales-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
}

.sales-stat {
    min-height: 88px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .75rem;
    padding: 1rem 1.2rem;
    background: #fff;
    border: 1px solid #edf0f2;
    border-radius: 7px;
}

.sales-stat-label {
    margin: 0 0 .25rem;
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #303946;
}

.sales-stat-value {
    margin: 0;
    font-size: 1.55rem;
    font-weight: 700;
    line-height: 1;
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

.sales-stat-icon--green {
    background: #a4ffbc;
    color: #157347;
}

.sales-stat-icon--red {
    background: #ff807e;
    color: #fff;
}

.sales-stat-icon--blue {
    background: #93d8ff;
    color: #045199;
}

.sales-stat-icon--amber {
    background: #ffd58d;
    color: #6a4e00;
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

.sales-search {
    position: relative;
    width: min(320px, 100%);
}

.sales-search i {
    position: absolute;
    left: .8rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--sales-muted);
    pointer-events: none;
}

.sales-search input, .sales-field input, .sales-field select, .sales-field textarea {
    width: 100%;
    min-height: 42px;
    border: 1px solid var(--sales-line);
    border-radius: 7px;
    background: #fff;
    color: var(--sales-ink);
    padding: .65rem .75rem;
    font: inherit;
    font-size: .86rem;
}

.sales-search input {
    height: 40px;
    border-radius: 20px;
    padding-left: 2.25rem;
}

.sales-field textarea {
    min-height: 68px;
    resize: vertical;
}

.sales-field input:focus, .sales-field select:focus, .sales-field textarea:focus, .sales-search input:focus {
    outline: 2px solid #9ac6ec;
    outline-offset: 1px;
}

.sales-filter {
    min-width: 142px;
    height: 40px;
    border: 1px solid var(--sales-line);
    border-radius: 6px;
    padding: 0 .7rem;
    background: #fff;
    font: inherit;
    font-size: .84rem;
}

.sales-table-wrap {
    overflow: auto;
    background: #fff;
    border: 1px solid var(--sales-line);
    border-radius: 12px;
}

.sales-table {
    width: 100%;
    border-collapse: collapse;
    white-space: nowrap;
}

.sales-table thead {
    background: #f7f9fb;
}

.sales-table th {
    height: 42px;
    padding: .6rem .7rem;
    border-bottom: 1px solid var(--sales-line);
    color: #5e6f81;
    font-size: .76rem;
    font-weight: 700;
    text-align: center;
}

.sales-table td {
    min-height: 50px;
    padding: .65rem .7rem;
    border-bottom: 1px solid #e8edf1;
    font-size: .8rem;
    text-align: center;
    vertical-align: middle;
}

.sales-table tbody tr:last-child td {
    border-bottom: 0;
}

.sales-table tbody tr:nth-child(even) {
    background: #fafbfd;
}

.sales-table .sales-name {
    font-weight: 600;
}

.sales-row-actions {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .35rem;
}

.sales-icon-button {
    width: 30px;
    height: 30px;
    display: inline-grid;
    place-items: center;
    border: 1px solid #e2e8ee;
    border-radius: 6px;
    background: #fff;
    color: #40556a;
    cursor: pointer;
}

.sales-icon-button:hover {
    color: var(--sales-blue);
    border-color: #8fb8df;
}

.sales-icon-button--danger:hover {
    color: #c73535;
    border-color: #e2a4a4;
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
    color: #157347;
    background: #e1f5e9;
}

.sales-badge--unpaid {
    color: #b42318;
    background: #fde8e7;
}

.sales-badge--outstanding {
    color: #805b08;
    background: #fff2cf;
}

.sales-empty {
    padding: 2rem !important;
    color: var(--sales-muted);
    text-align: center !important;
    white-space: normal;
}

.sales-notice {
    position: fixed;
    z-index: 2100;
    top: 1rem;
    left: 50%;
    transform: translateX(-50%);
    max-width: min(420px, calc(100vw - 2rem));
    padding: .75rem 1rem;
    border-radius: 6px;
    background: #157347;
    color: #fff;
    box-shadow: 0 5px 20px #172b3a33;
    font-size: .9rem;
}

.sales-dialog {
    width: min(520px, calc(100vw - 2rem));
    max-height: min(90vh, 850px);
    padding: 0;
    border: 0;
    border-radius: 12px;
    box-shadow: 0 18px 50px #16202c33;
    color: var(--sales-ink);
}

.sales-dialog::backdrop {
    background: #17212a75;
}

.sales-dialog-inner {
    padding: 1.35rem 1.5rem 1.2rem;
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

.sales-balance-line {
    display: flex;
    justify-content: space-between;
    font-size: .83rem;
}

.sales-receipt-dialog {
    width: min(465px, calc(100vw - 1.25rem));
}

.sales-receipt-toolbar {
    position: absolute;
    top: -3rem;
    right: 0;
    display: flex;
    gap: .5rem;
}

.sales-receipt {
    overflow: hidden;
    background: #fff;
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
    background: #2dd4bf;
    color: #064f82;
    font-size: .6rem;
}

.sales-receipt-head strong {
    display: block;
    font-size: .93rem;
}

.sales-receipt-head small {
    display: block;
    margin-top: .2rem;
    color: #d3e9fa;
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
    border: 1px solid #d5e0f0;
    border-radius: 7px;
    background: #f2f5fc;
    color: #214cbd;
}

.sales-receipt-amount:nth-child(2) {
    background: #effaf2;
    border-color: #bee5c8;
    color: #17834b;
}

.sales-receipt-amount:nth-child(3) {
    background: #fff0f0;
    border-color: #f2c3c3;
    color: #d93434;
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
    border-radius: 6px;
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
        padding: 1rem .75rem 1.5rem;
    }

    .sales-heading {
        align-items: flex-start;
    }

    .sales-tabs {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .sales-tab {
        width: auto;
        height: 42px;
        font-size: .68rem;
    }

    .sales-toolbar, .sales-toolbar-start, .sales-search {
        width: 100%;
    }

    .sales-toolbar-start {
        align-items: stretch;
    }

    .sales-filter {
        flex: 1;
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

@php
    $coursePayments = \DB::table('tbl_students as s')
        ->leftJoin('tbl_users as u', 'u.user_id', '=', 's.user_id')
        ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
        ->leftJoin('tbl_courses as c', 'c.course_id', '=', 's.course_id')
        ->select('s.student_id as id')
        ->selectRaw("COALESCE(c.course_name, 'Course') as course")
        ->selectRaw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.name_suffix)), ''), u.username, '') as name")
        ->selectRaw('COALESCE(s.course_price_at_enrollment, c.course_price, 0) as price')
        ->selectRaw('COALESCE(s.course_price_at_enrollment, c.course_price, 0) as toPay')
        ->selectRaw('(SELECT COALESCE(SUM(cp.amount), 0) FROM tbl_course_payments cp WHERE cp.student_id = s.student_id) as paid')
        ->selectRaw("(SELECT cp.payment_method FROM tbl_course_payments cp WHERE cp.student_id = s.student_id ORDER BY cp.paid_at DESC, cp.course_payment_id DESC LIMIT 1) as method")
        ->selectRaw("COALESCE(DATE_FORMAT((SELECT MAX(cp.paid_at) FROM tbl_course_payments cp WHERE cp.student_id = s.student_id), '%b-%d-%Y'), DATE_FORMAT(s.enrollment_at, '%b-%d-%Y')) as date")
        ->selectRaw("COALESCE((SELECT cp.receipt_number FROM tbl_course_payments cp WHERE cp.student_id = s.student_id ORDER BY cp.paid_at DESC, cp.course_payment_id DESC LIMIT 1), CONCAT('ENR-', s.student_id)) as receipt")
        ->orderByDesc('s.enrollment_at')
        ->orderByDesc('s.student_id')
        ->get()
        ->map(fn ($row) => [
            'id' => (int) $row->id,
            'course' => $row->course,
            'name' => $row->name,
            'price' => (float) $row->price,
            'toPay' => (float) $row->toPay,
            'paid' => (float) $row->paid,
            'method' => $row->method ?? '',
            'date' => $row->date ?? '',
            'receipt' => $row->receipt,
        ]);

    $itemCatalog = \DB::table('tbl_items')
        ->where('is_active', 1)
        ->orderBy('item_name')
        ->get(['item_id', 'item_name', 'description', 'unit_price', 'stock_quantity'])
        ->map(fn ($item) => [
            'id' => (int) $item->item_id,
            'name' => $item->item_name,
            'description' => $item->description ?? '',
            'price' => (float) $item->unit_price,
            'stock' => (int) $item->stock_quantity,
        ]);

    $itemLogs = \DB::table('tbl_item_logs as l')
        ->join('tbl_items as i', 'i.item_id', '=', 'l.item_id')
        ->leftJoin('tbl_users as u', 'u.user_id', '=', 'l.user_id')
        ->leftJoin('tbl_user_profile as p', 'p.user_id', '=', 'u.user_id')
        ->select('l.item_log_id as id', 'l.item_id as itemId', 'i.item_name as item', 'l.quantity', 'l.unit_price as price', 'l.amount_paid as paid', 'l.payment_method as method', 'l.receipt_number as receipt')
        ->selectRaw("COALESCE(NULLIF(l.description, ''), i.description, '') as description")
        ->selectRaw("DATE_FORMAT(l.sold_at, '%b-%d-%Y') as date")
        ->selectRaw("COALESCE(NULLIF(TRIM(CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.name_suffix)), ''), u.username, '') as client")
        ->orderByDesc('l.sold_at')
        ->orderByDesc('l.item_log_id')
        ->get()
        ->map(fn ($row) => [
            'id' => (int) $row->id,
            'itemId' => (int) $row->itemId,
            'item' => $row->item,
            'description' => $row->description ?? '',
            'quantity' => (int) $row->quantity,
            'price' => (float) $row->price,
            'paid' => (float) $row->paid,
            'method' => $row->method ?? '',
            'date' => $row->date ?? '',
            'client' => $row->client,
            'receipt' => $row->receipt,
        ]);
@endphp

<section class="sales-page" id="salesPage" aria-labelledby="salesTitle">
    <header class="sales-heading">
        <div>
            <h1 id="salesTitle">Purchases</h1>
            <p>Track fees, collections, and balances</p>
        </div>
        <button class="sales-add-button" id="salesAddButton" type="button"><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add New Purchase</span></button>
    </header>

    <nav class="sales-tabs" aria-label="Purchase sections" role="tablist">
        <button class="sales-tab" id="salesTabCourse" type="button" role="tab" aria-selected="true" aria-controls="salesPanelCourse" data-sales-tab="course">Course payment</button>
        <button class="sales-tab" id="salesTabLogs" type="button" role="tab" aria-selected="false" aria-controls="salesPanelLogs" data-sales-tab="logs">Item logs</button>
        <button class="sales-tab" id="salesTabItems" type="button" role="tab" aria-selected="false" aria-controls="salesPanelItems" data-sales-tab="items">Items</button>
    </nav>

    <div class="sales-stats" aria-label="Payment totals">
        <article class="sales-stat"><div><p class="sales-stat-label">Total collected</p><p class="sales-stat-value" id="salesTotalCollected">₱0</p></div><span class="sales-stat-icon sales-stat-icon--green"><i class="bi bi-check-lg"></i></span></article>
        <article class="sales-stat"><div><p class="sales-stat-label">Outstanding</p><p class="sales-stat-value" id="salesOutstanding">₱0</p></div><span class="sales-stat-icon sales-stat-icon--red"><i class="bi bi-circle-fill"></i></span></article>
        <article class="sales-stat"><div><p class="sales-stat-label">Paid</p><p class="sales-stat-value" id="salesPaid">₱0</p></div><span class="sales-stat-icon sales-stat-icon--blue"><i class="bi bi-circle"></i></span></article>
        <article class="sales-stat"><div><p class="sales-stat-label">Unpaid</p><p class="sales-stat-value" id="salesUnpaid">₱0</p></div><span class="sales-stat-icon sales-stat-icon--amber"><i class="bi bi-circle"></i></span></article>
    </div>

    <div class="sales-toolbar">
        <div class="sales-toolbar-start">
            <label class="sales-search"><i class="bi bi-search" aria-hidden="true"></i><input id="salesSearch" type="search" placeholder="Search" autocomplete="off" aria-label="Search rows"></label>
            <select class="sales-filter" id="salesStatusFilter" aria-label="Filter by payment status">
                <option value="">All Status</option><option value="paid">Paid</option><option value="unpaid">Unpaid</option><option value="outstanding">Outstanding</option>
            </select>
        </div>
    </div>

    <section class="sales-panel" id="salesPanelCourse" role="tabpanel" aria-labelledby="salesTabCourse" data-sales-panel="course">
        <div class="sales-table-wrap"><table class="sales-table"><thead><tr><th>Course</th><th>Name</th><th>Price</th><th>To pay</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Actions</th><th>Receipt</th></tr></thead><tbody id="coursePaymentRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelLogs" role="tabpanel" aria-labelledby="salesTabLogs" data-sales-panel="logs" hidden>
        <div class="sales-table-wrap"><table class="sales-table"><thead><tr><th>Item name</th><th>Description</th><th>Quantity</th><th>Price</th><th>To pay</th><th>Paid</th><th>Balance</th><th>Method</th><th>Status</th><th>Date</th><th>Actions</th><th>Receipt</th></tr></thead><tbody id="itemLogRows"></tbody></table></div>
    </section>
    <section class="sales-panel" id="salesPanelItems" role="tabpanel" aria-labelledby="salesTabItems" data-sales-panel="items" hidden>
        <div class="sales-table-wrap"><table class="sales-table"><thead><tr><th>Item name</th><th>Description</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead><tbody id="itemCatalogRows"></tbody></table></div>
    </section>
    <div class="sales-notice" id="salesNotice" role="status" aria-live="polite" hidden></div>
</section>

<dialog class="sales-dialog" id="coursePaymentDialog" aria-labelledby="coursePaymentTitle">
    <div class="sales-dialog-inner">
        <h2 id="coursePaymentTitle">Updating course payment</h2><p class="sales-dialog-subtitle" id="coursePaymentStudent"></p>
        <form class="sales-form" id="coursePaymentForm">
            <input type="hidden" name="id">
            <div class="sales-balance-line"><strong>Balance</strong><strong id="coursePaymentBalance">₱0</strong></div>
            <div class="sales-field"><label for="coursePaymentAmount">Payment *</label><input id="coursePaymentAmount" name="amount" type="number" min="0.01" step="0.01" placeholder="₱2,000" required></div>
            <div class="sales-field"><label for="coursePaymentMethod">Mode of payment *</label><select id="coursePaymentMethod" name="method" required><option value="Cash">Cash</option><option value="GCash">GCash</option><option value="Bank Transfer">Bank Transfer</option></select></div>
            <div class="sales-balance-line"><strong>New payment status</strong><span class="sales-badge" id="coursePaymentStatus"></span></div>
            <div class="sales-dialog-actions"><button class="sales-button sales-button-secondary" type="button" data-dialog-close>Cancel</button><button class="sales-button sales-button-primary" type="submit">Update</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog" id="purchaseDialog" aria-labelledby="purchaseDialogTitle">
    <div class="sales-dialog-inner">
        <h2 id="purchaseDialogTitle">Adding New Purchase</h2><p class="sales-dialog-subtitle" id="purchaseDialogSubtitle">New Purchase</p>
        <form class="sales-form" id="purchaseForm">
            <input type="hidden" name="id">
            <div class="sales-field"><label for="purchaseItem">Item *</label><select id="purchaseItem" name="itemId" required></select></div>
            <div class="sales-form-grid"><div class="sales-field"><label for="purchaseQuantity">Quantity *</label><input id="purchaseQuantity" name="quantity" type="number" min="1" step="1" value="1" required></div><div class="sales-field"><label for="purchaseUnitPrice">Price</label><input id="purchaseUnitPrice" type="text" readonly></div></div>
            <div class="sales-balance-line"><strong>To Pay</strong><strong id="purchaseToPay">₱0</strong></div>
            <div class="sales-form-grid"><div class="sales-field"><label for="purchasePaid">Payment *</label><input id="purchasePaid" name="paid" type="number" min="0" step="0.01" value="0" required></div><div class="sales-field"><label for="purchaseMethod">Mode of Payment *</label><select id="purchaseMethod" name="method" required><option value="Cash">Cash</option><option value="GCash">GCash</option><option value="Bank Transfer">Bank Transfer</option></select></div></div>
            <div class="sales-field"><label for="purchaseDescription">Description</label><input id="purchaseDescription" name="description" maxlength="255" placeholder="Color and stuff"></div>
            <div class="sales-dialog-actions"><button class="sales-button sales-button-secondary" type="button" data-dialog-close>Cancel</button><button class="sales-button sales-button-primary" type="submit" id="purchaseSubmit">Add</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog" id="itemDialog" aria-labelledby="itemDialogTitle">
    <div class="sales-dialog-inner">
        <h2 id="itemDialogTitle">Adding New Item</h2><p class="sales-dialog-subtitle" id="itemDialogSubtitle">New Item</p>
        <form class="sales-form" id="itemForm">
            <input type="hidden" name="id">
            <div class="sales-field"><label for="catalogItemName">Item name *</label><input id="catalogItemName" name="name" maxlength="150" required></div>
            <div class="sales-field"><label for="catalogItemDescription">Description</label><input id="catalogItemDescription" name="description" maxlength="255"></div>
            <div class="sales-form-grid"><div class="sales-field"><label for="catalogItemPrice">Price *</label><input id="catalogItemPrice" name="price" type="number" min="0" step="0.01" required></div><div class="sales-field"><label for="catalogItemStock">Stock *</label><input id="catalogItemStock" name="stock" type="number" min="0" step="1" required></div></div>
            <div class="sales-dialog-actions"><button class="sales-button sales-button-secondary" type="button" data-dialog-close>Cancel</button><button class="sales-button sales-button-primary" type="submit" id="itemSubmit">Add</button></div>
        </form>
    </div>
</dialog>

<dialog class="sales-dialog sales-receipt-dialog" id="receiptDialog" aria-label="Receipt">
    <div class="sales-receipt-toolbar"><button class="sales-button sales-button-primary" type="button" id="printReceipt"><i class="bi bi-printer" aria-hidden="true"></i> Print</button><button class="sales-icon-button" type="button" data-dialog-close aria-label="Close receipt"><i class="bi bi-x-lg"></i></button></div>
    <article class="sales-receipt" id="receiptContent"></article>
</dialog>

<dialog class="sales-dialog" id="confirmDialog" aria-labelledby="confirmTitle">
    <div class="sales-dialog-inner"><h2 id="confirmTitle">Remove this record?</h2><p class="sales-dialog-subtitle" id="confirmMessage">This action only changes this preview until the page is reloaded.</p><div class="sales-dialog-actions"><button class="sales-button sales-button-secondary" type="button" data-dialog-close>Cancel</button><button class="sales-button sales-button-danger" type="button" id="confirmDelete">Remove</button></div></div>
</dialog>

<script>
(function () {
    const page = document.getElementById('salesPage');
    if (!page) return;

    let courseRows = @json($coursePayments);
    let items = @json($itemCatalog);
    let logs = @json($itemLogs);
    let activeTab = 'course';
    let pendingRemoval = null;
    const money = value => `₱${Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 })}`;
    const dateLabel = () => {
        const date = new Date();
        const month = date.toLocaleString('en-US', { month: 'short' }).toUpperCase();
        return `${month}-${String(date.getDate()).padStart(2, '0')}-${date.getFullYear()}`;
    };
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
    const statusFor = (paid, total) => Number(paid) <= 0 ? 'unpaid' : Number(paid) >= Number(total) ? 'paid' : 'outstanding';
    const badge = status => `<span class="sales-badge sales-badge--${status}">${status === 'paid' ? 'Paid' : status === 'unpaid' ? 'Unpaid' : 'Outstanding'}</span>`;
    const byId = (list, id) => list.find(row => Number(row.id) === Number(id));
    const search = document.getElementById('salesSearch');
    const statusFilter = document.getElementById('salesStatusFilter');
    const addButton = document.getElementById('salesAddButton');
    const notice = document.getElementById('salesNotice');

    function notify(message) {
        notice.textContent = message;
        notice.hidden = false;
        window.clearTimeout(notify.timer);
        notify.timer = window.setTimeout(() => { notice.hidden = true; }, 2800);
    }

    function currentFilter() {
        return { text: search.value.trim().toLocaleLowerCase(), status: statusFilter.value };
    }

    function updateStats() {
        const paidTotal = courseRows.reduce((total, row) => total + Number(row.paid), 0);
        const outstanding = courseRows.reduce((total, row) => total + Math.max(Number(row.toPay) - Number(row.paid), 0), 0);
        const paidAmount = courseRows.reduce((total, row) => total + (statusFor(row.paid, row.toPay) === 'paid' ? Number(row.paid) : 0), 0);
        const unpaidAmount = courseRows.reduce((total, row) => total + (statusFor(row.paid, row.toPay) === 'unpaid' ? Number(row.toPay) : 0), 0);
        document.getElementById('salesTotalCollected').textContent = money(paidTotal);
        document.getElementById('salesOutstanding').textContent = money(outstanding);
        document.getElementById('salesPaid').textContent = money(paidAmount);
        document.getElementById('salesUnpaid').textContent = money(unpaidAmount);
    }

    function renderCourseRows() {
        const { text, status } = currentFilter();
        const visible = courseRows.filter(row => {
            const rowStatus = statusFor(row.paid, row.toPay);
            const haystack = `${row.course} ${row.name} ${row.method} ${row.date}`.toLocaleLowerCase();
            return (!status || status === rowStatus) && (!text || haystack.includes(text));
        });
        document.getElementById('coursePaymentRows').innerHTML = visible.length ? visible.map(row => {
            const balance = Math.max(Number(row.toPay) - Number(row.paid), 0);
            return `<tr><td class="sales-name">${escapeHtml(row.course)}</td><td>${escapeHtml(row.name)}</td><td>${money(row.price)}</td><td>${money(row.toPay)}</td><td>${money(row.paid)}</td><td>${money(balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(statusFor(row.paid, row.toPay))}</td><td>${escapeHtml(row.date)}</td><td><span class="sales-row-actions"><button class="sales-icon-button" type="button" data-course-edit="${row.id}" aria-label="Record payment for ${escapeHtml(row.name)}" title="Record payment"><i class="bi bi-pencil"></i></button><button class="sales-icon-button sales-icon-button--danger" type="button" data-course-delete="${row.id}" aria-label="Remove ${escapeHtml(row.name)}" title="Remove"><i class="bi bi-trash3"></i></button></span></td><td><button class="sales-icon-button" type="button" data-course-receipt="${row.id}" aria-label="View receipt for ${escapeHtml(row.name)}" title="View receipt"><i class="bi bi-eye"></i></button></td></tr>`;
        }).join('') : '<tr><td class="sales-empty" colspan="11">No course payments match your search.</td></tr>';
        updateStats();
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
            return `<tr><td class="sales-name">${escapeHtml(row.item)}</td><td>${escapeHtml(row.description || '--')}</td><td>${Number(row.quantity)}</td><td>${money(row.price)}</td><td>${money(total)}</td><td>${money(row.paid)}</td><td>${money(balance)}</td><td>${escapeHtml(row.method || '--')}</td><td>${badge(statusFor(row.paid, total))}</td><td>${escapeHtml(row.date)}</td><td><span class="sales-row-actions"><button class="sales-icon-button" type="button" data-log-edit="${row.id}" aria-label="Edit ${escapeHtml(row.item)} sale" title="Edit"><i class="bi bi-pencil"></i></button><button class="sales-icon-button sales-icon-button--danger" type="button" data-log-delete="${row.id}" aria-label="Remove ${escapeHtml(row.item)} sale" title="Remove"><i class="bi bi-trash3"></i></button></span></td><td><button class="sales-icon-button" type="button" data-log-receipt="${row.id}" aria-label="View receipt for ${escapeHtml(row.item)}" title="View receipt"><i class="bi bi-eye"></i></button></td></tr>`;
        }).join('') : '<tr><td class="sales-empty" colspan="12">No item logs match your search.</td></tr>';
    }

    function renderItems() {
        const text = search.value.trim().toLocaleLowerCase();
        const visible = items.filter(item => `${item.name} ${item.description}`.toLocaleLowerCase().includes(text));
        document.getElementById('itemCatalogRows').innerHTML = visible.length ? visible.map(item => `<tr><td class="sales-name">${escapeHtml(item.name)}</td><td>${escapeHtml(item.description || '--')}</td><td>${money(item.price)}</td><td>${Number(item.stock)}</td><td><span class="sales-row-actions"><button class="sales-icon-button" type="button" data-item-edit="${item.id}" aria-label="Edit ${escapeHtml(item.name)}" title="Edit"><i class="bi bi-pencil"></i></button><button class="sales-icon-button sales-icon-button--danger" type="button" data-item-delete="${item.id}" aria-label="Remove ${escapeHtml(item.name)}" title="Remove"><i class="bi bi-trash3"></i></button></span></td></tr>`).join('') : '<tr><td class="sales-empty" colspan="5">No items match your search.</td></tr>';
    }

    function render() { renderCourseRows(); renderLogs(); renderItems(); }

    function setTab(tab) {
        activeTab = tab;
        document.querySelectorAll('[data-sales-tab]').forEach(button => button.setAttribute('aria-selected', String(button.dataset.salesTab === tab)));
        document.querySelectorAll('[data-sales-panel]').forEach(panel => { panel.hidden = panel.dataset.salesPanel !== tab; });
        search.value = '';
        statusFilter.value = '';
        const labels = { course: 'Add New Purchase', logs: 'Add New Purchase', items: 'Add New Item' };
        addButton.querySelector('span').textContent = labels[tab];
        addButton.hidden = tab === 'course';
        statusFilter.hidden = tab === 'items';
    }

    function showCoursePayment(row) {
        const form = document.getElementById('coursePaymentForm');
        form.elements.id.value = row.id;
        form.elements.amount.value = '';
        form.elements.method.value = row.method || 'Cash';
        form.elements.amount.max = Math.max(Number(row.toPay) - Number(row.paid), 0);
        document.getElementById('coursePaymentStudent').textContent = `Student name: ${row.name}`;
        document.getElementById('coursePaymentBalance').textContent = money(Math.max(row.toPay - row.paid, 0));
        const status = document.getElementById('coursePaymentStatus');
        status.className = `sales-badge sales-badge--${statusFor(row.paid, row.toPay)}`;
        status.textContent = statusFor(row.paid, row.toPay) === 'paid' ? 'Paid' : statusFor(row.paid, row.toPay) === 'unpaid' ? 'Unpaid' : 'Outstanding';
        document.getElementById('coursePaymentDialog').showModal();
    }

    function refreshPurchasePrice() {
        const selected = byId(items, document.getElementById('purchaseItem').value);
        const quantity = Math.max(Number(document.getElementById('purchaseQuantity').value) || 1, 1);
        document.getElementById('purchaseUnitPrice').value = selected ? money(selected.price) : money(0);
        document.getElementById('purchaseToPay').textContent = money(selected ? selected.price * quantity : 0);
    }

    function fillItemOptions(selectedId = null) {
        const select = document.getElementById('purchaseItem');
        select.innerHTML = items.map(item => `<option value="${item.id}" ${Number(selectedId) === Number(item.id) ? 'selected' : ''}>${escapeHtml(item.name)}</option>`).join('');
    }

    function showPurchase(row = null) {
        if (!items.length) { notify('Add an item to the catalog before recording a purchase.'); return; }
        const form = document.getElementById('purchaseForm');
        form.reset();
        form.elements.id.value = row?.id ?? '';
        fillItemOptions(row?.itemId ?? items[0]?.id);
        form.elements.quantity.value = row?.quantity ?? 1;
        form.elements.paid.value = row?.paid ?? 0;
        form.elements.method.value = row?.method || 'Cash';
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
        const balance = Math.max(total - paid, 0);
        const client = isCourse ? row.name : (row.client || 'Walk-in customer');
        const description = isCourse ? row.course : row.item;
        const quantityLine = isCourse ? 'Course enrollment' : `${row.quantity} × ${money(row.price)}`;
        const invoice = row.receipt || `${isCourse ? 'INV' : 'ITEM'}-${new Date().getFullYear()}-${String(row.id).padStart(4, '0')}`;
        const status = statusFor(paid, total);
        document.getElementById('receiptContent').innerHTML = `<header class="sales-receipt-head"><div class="sales-receipt-brand"><span class="sales-receipt-brand-mark">FB</span> FBSS SwimOS ${badge(status)}</div><strong>Froy Bartolome Swimming School</strong><small>R3M+2Q9, Del Monte - Norzagaray Rd, SJDM, Bulacan<br>(+63) 966 203 1918</small></header><div class="sales-receipt-body"><div class="sales-receipt-meta"><span>Invoice #</span><strong>${escapeHtml(invoice)}</strong><span>Date</span><strong>${escapeHtml(row.date)}</strong><span>Client</span><strong>${escapeHtml(client)}</strong></div><p class="sales-receipt-section">${isCourse ? 'Course purchased' : 'Items purchased'}</p><div class="sales-receipt-line"><span>${escapeHtml(description)}<small>${escapeHtml(quantityLine)}</small></span><strong>${money(total)}</strong></div><div class="sales-receipt-line sales-receipt-total"><span>Subtotal<br>Total</span><strong>${money(total)}<br>${money(total)}</strong></div><div class="sales-receipt-amounts"><div class="sales-receipt-amount"><small>To pay</small><strong>${money(total)}</strong></div><div class="sales-receipt-amount"><small>Amount paid</small><strong>${money(paid)}</strong></div><div class="sales-receipt-amount"><small>Balance due</small><strong>${money(balance)}</strong></div></div><div class="sales-receipt-method">● &nbsp;Payment Method: <strong>${escapeHtml(row.method || '--')}</strong></div><p class="sales-receipt-thanks">Thank You for Your Payment!</p><p class="sales-receipt-footer">We look forward to seeing you at the pool</p></div>`;
        document.getElementById('receiptDialog').showModal();
    }

    function askRemove(message, callback) {
        pendingRemoval = callback;
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmDialog').showModal();
    }

    document.querySelectorAll('[data-sales-tab]').forEach(button => button.addEventListener('click', () => setTab(button.dataset.salesTab)));
    search.addEventListener('input', render);
    statusFilter.addEventListener('change', render);
    addButton.addEventListener('click', () => activeTab === 'items' ? showItem() : activeTab === 'logs' ? showPurchase() : notify('Record a payment from a course row.'));
    document.getElementById('purchaseItem').addEventListener('change', refreshPurchasePrice);
    document.getElementById('purchaseQuantity').addEventListener('input', refreshPurchasePrice);

    page.addEventListener('click', event => {
        const target = event.target.closest('button');
        if (!target) return;
        if (target.matches('[data-course-edit]')) showCoursePayment(byId(courseRows, target.dataset.courseEdit));
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

    document.getElementById('coursePaymentForm').addEventListener('submit', event => {
        event.preventDefault();
        const row = byId(courseRows, event.currentTarget.elements.id.value);
        if (!row) { notify('This enrollment is no longer in the preview.'); return; }
        const amount = Number(event.currentTarget.elements.amount.value);
        const balance = Math.max(Number(row.toPay) - Number(row.paid), 0);
        if (amount <= 0 || amount > balance) { notify('Enter a payment greater than zero and no more than the balance.'); return; }
        row.paid = Number(row.paid) + amount;
        row.method = event.currentTarget.elements.method.value;
        row.date = dateLabel();
        document.getElementById('coursePaymentDialog').close();
        render();
        notify('Payment updated in this preview.');
    });

    document.getElementById('coursePaymentAmount').addEventListener('input', event => {
        const row = byId(courseRows, document.getElementById('coursePaymentForm').elements.id.value);
        if (!row) return;
        const paid = Math.min(Number(row.paid) + (Number(event.target.value) || 0), Number(row.toPay));
        const status = statusFor(paid, row.toPay);
        const badgeElement = document.getElementById('coursePaymentStatus');
        badgeElement.className = `sales-badge sales-badge--${status}`;
        badgeElement.textContent = status === 'paid' ? 'Paid' : status === 'unpaid' ? 'Unpaid' : 'Outstanding';
    });

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
        const record = { id: existing?.id || Date.now(), itemId: item.id, item: item.name, description: form.elements.description.value.trim(), quantity, price: Number(item.price), paid, method: form.elements.method.value, date: dateLabel(), client: existing?.client || '', receipt: existing?.receipt || `ITEM-${new Date().getFullYear()}-${String(logs.length + 1).padStart(3, '0')}` };
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
    document.querySelectorAll('.sales-dialog').forEach(dialog => dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); }));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') pendingRemoval = null; });
    setTab('course');
    render();
})();
</script>
