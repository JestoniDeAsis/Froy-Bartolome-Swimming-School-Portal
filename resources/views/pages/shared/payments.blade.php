@if ((int) Auth::user()->role_id === 3)
    <style>
        .student-page{width:100%;max-width:1320px;margin:0 auto;padding:28px 32px 40px;color:#202b36}.student-page-header{margin:0 0 24px}.student-page-header h1{font-size:28px;font-weight:700;margin:0 0 5px;color:#173b63}.student-page-header p{margin:0;color:#657382;font-size:14px}.student-card{background:#fff;border:1px solid #dce3ea;border-radius:8px;padding:20px 22px;box-shadow:0 2px 8px rgba(26,51,77,.06)}.student-card-title{font-size:18px;font-weight:700;margin:0;color:#263d55}.student-divider{height:1px;background:#e8edf2;margin:14px 0 16px}.student-overview-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.student-overview-label{display:block;color:#74818e;font-size:12px;margin-bottom:7px}.student-overview-value{display:block;color:#263d55;font-size:15px}.student-overview-value.is-success{color:#21834b}.student-status-badge{display:inline-block;border-radius:20px;padding:4px 10px;font-size:12px;font-weight:700}.student-status-badge.is-paid{background:#e7f6ed;color:#217344}.student-status-badge.is-partial{background:#fff3df;color:#9a6411}.student-table-wrap{width:100%;overflow:auto}.student-table{width:100%;border-collapse:collapse;font-size:13px;white-space:nowrap}.student-table th{font-weight:700;color:#526273;background:#f5f8fa;text-align:left;padding:11px 12px}.student-table td{padding:12px;border-bottom:1px solid #e9eef2;color:#344353}.student-icon-button{border:1px solid #ced8e1;border-radius:5px;background:#fff;color:#176da8;width:34px;height:32px}.student-dialog{width:min(560px,calc(100% - 28px));max-height:90vh;padding:0;border:0;border-radius:8px;box-shadow:0 18px 60px rgba(13,35,55,.3)}.student-dialog::backdrop{background:rgba(10,25,40,.55)}.student-receipt-head{background:#176da8;color:white;padding:22px 26px}.student-receipt-head-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}.student-receipt-head h2{font-size:18px;margin:0 0 5px}.student-receipt-head p{font-size:12px;margin:3px 0;color:#e6f1f8}.student-receipt-body{padding:22px 26px}.student-receipt-line,.student-receipt-total{display:flex;justify-content:space-between;gap:16px;padding:9px 0;border-bottom:1px solid #e9eef2;font-size:13px}.student-receipt-total{font-size:16px;font-weight:700;color:#176da8}.student-receipt-amounts{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:16px 0;padding:12px 0;border-block:1px solid #e9eef2}.student-receipt-amounts div{display:grid;gap:4px}.student-receipt-amounts small{color:#74818e}.student-receipt-method{font-size:12px;padding:14px 0}.student-receipt-actions{display:flex;justify-content:flex-end;gap:8px;padding:0 26px 20px}@media(max-width:800px){.student-page{padding:22px 18px}.student-overview-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.student-page{padding:18px 12px}.student-card{padding:16px}.student-page-header h1{font-size:24px}.student-overview-grid{gap:14px}.student-receipt-body{padding:18px}.student-receipt-head{padding:18px}}
    </style>
    <style>
        #displayContainer:has(> .student-page){padding:50px}.student-page{width:100%;max-width:none;margin:0;padding:0}
    </style>
    <main class="student-page student-payments-page">
        <header class="student-page-header">
            <h1>Payments</h1>
            <p>Track fees, collections, and balances</p>
        </header>

        <section class="student-card mb-4" aria-labelledby="studentPaymentOverviewTitle">
            <h2 class="student-card-title" id="studentPaymentOverviewTitle">Payment Overview</h2>
            <div class="student-divider"></div>
            <div class="student-overview-grid">
                <div><span class="student-overview-label">Course Enrolled</span><strong class="student-overview-value">Beginner Swim Program</strong></div>
                <div><span class="student-overview-label">Total Package Fee</span><strong class="student-overview-value">₱4,000</strong></div>
                <div><span class="student-overview-label">Outstanding Balance</span><strong class="student-overview-value is-success">₱0</strong></div>
                <div><span class="student-overview-label">Payment Status</span><span class="student-status-badge is-paid">Paid</span></div>
            </div>
        </section>

        <section class="student-card" aria-labelledby="studentTransactionTitle">
            <h2 class="student-card-title" id="studentTransactionTitle">Transaction History</h2>
            <div class="student-divider"></div>
            <div class="student-table-wrap">
                <table class="student-table student-payment-table">
                    <thead><tr><th>Reference No.</th><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Receipt</th></tr></thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold">TXN-88092</td><td>August 1, 2026</td><td class="text-success fw-bold">₱1,000</td><td>Cash</td>
                            <td><span class="student-status-badge is-partial">Partial</span></td>
                            <td><button class="btn btn-icon btn-icon-square btn-outline-primary btn-icon-sm" type="button" data-student-receipt aria-label="View receipt for TXN-88092"><i class="bi bi-eye" aria-hidden="true"></i></button></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">TXN-88092</td><td>August 1, 2026</td><td class="text-success fw-bold">₱3,000</td><td>GCash</td>
                            <td><span class="student-status-badge is-paid">Paid</span></td>
                            <td><button class="btn btn-icon btn-icon-square btn-outline-primary btn-icon-sm" type="button" data-student-receipt aria-label="View receipt for TXN-88092"><i class="bi bi-eye" aria-hidden="true"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <dialog class="student-dialog" id="studentReceiptDialog" aria-labelledby="studentReceiptTitle">
            <div class="student-receipt-head">
                <div class="student-receipt-head-top">
                    <a class="navbar-brand d-flex align-items-center gap-2 mb-0 text-white" href="{{ route('home') }}">
                        <img src="{{ asset('assets/images/icon_fbss.png') }}" alt="" width="28" height="28" class="rounded-circle">
                        <strong>FBSS SwimOS</strong>
                    </a>
                    <span class="student-status-badge is-partial">Partial</span>
                </div>
                <h2 id="studentReceiptTitle">Froy Bartolome Swimming School</h2>
                <p>R3MG+2X9, Del Monte - Norzagaray Rd, SJDM, Bulacan</p>
                <p>(+63) 966 203 1918</p>
            </div>
            <div class="student-receipt-body">
                <div class="student-receipt-line"><span>Invoice #</span><strong>INV-2024-0847</strong></div>
                <div class="student-receipt-line"><span>Date</span><strong>August 11, 2026</strong></div>
                <div class="student-receipt-line"><span>Client</span><strong>Jestoni E. De Asis</strong></div>
                <strong class="text-primary small text-uppercase">Items Purchased</strong>
                <div class="student-receipt-line"><span>Group Swim Lessons (LTS 1)<small class="d-block text-muted">1 × ₱2,000</small></span><strong>₱4,000</strong></div>
                <div class="student-receipt-line"><span>Subtotal</span><strong>₱4,000</strong></div>
                <div class="student-receipt-total"><span>Total</span><span>₱4,000</span></div>
                <div class="student-receipt-amounts">
                    <div><small>To Pay</small><strong>₱3,000</strong></div>
                    <div><small>Amount Paid</small><strong>₱3,000</strong></div>
                    <div><small>Balance Due</small><strong>₱0</strong></div>
                </div>
                <div class="student-receipt-method"><i class="bi bi-circle-fill text-primary me-1" aria-hidden="true"></i>Payment Method: <strong>GCash</strong><span class="float-end">Ref#: GC-202413</span></div>
                <p class="text-center text-primary fw-bold mb-0">Thank You for Your Payment!</p>
                <p class="text-center text-muted small mb-0">We look forward to seeing you at the pool!</p>
            </div>
            <div class="student-receipt-actions">
                <button class="btn btn-primary btn-sm" type="button" data-student-print><i class="bi bi-printer me-1" aria-hidden="true"></i>Print</button>
                <button class="btn btn-icon btn-icon-square btn-outline-secondary btn-icon-sm" type="button" data-student-close aria-label="Close receipt"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
        </dialog>
    </main>

    <script>
        (() => {
            const receiptDialog = document.getElementById('studentReceiptDialog');
            document.querySelectorAll('[data-student-receipt]').forEach((button) => {
                button.addEventListener('click', () => receiptDialog.showModal());
            });
            document.querySelector('[data-student-close]').addEventListener('click', () => receiptDialog.close());
            document.querySelector('[data-student-print]').addEventListener('click', () => window.print());
        })();
    </script>
@else
    PAYMENT NI STUDENT AND ATHLETE
@endif
