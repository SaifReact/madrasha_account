<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}
include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../includes/head.php';
include_once __DIR__ . '/../includes/open.php';
include_once __DIR__ . '/../includes/side_bar.php';
?>
<main class="col-12 col-md-10 col-lg-10 col-xl-10 px-md-3">
    <div class="row px-2">
        <div class="card shadow-lg rounded-3 border-0">
            <div class="card-body p-4">
                <h3 class="mb-3 text-primary fw-bold">Voucher Entry</h3>
                <hr class="mb-4" />
                <ul class="nav nav-tabs mb-3" id="voucherTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab">পেমেন্ট ভাউচার</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="receipt-tab" data-bs-toggle="tab" data-bs-target="#receipt" type="button" role="tab">রিসিভ ভাউচার</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="journal-tab" data-bs-toggle="tab" data-bs-target="#journal" type="button" role="tab">জার্নাল ভাউচার</button>
                    </li>
                </ul>
                <div class="tab-content" id="voucherTabsContent">
                    <!-- Payment Voucher Tab -->
                    <div class="tab-pane fade show active" id="payment" role="tabpanel">
                        <?php include 'voucher_payment_tab.php'; ?>
                    </div>
                    <!-- Receipt Voucher Tab -->
                    <div class="tab-pane fade" id="receipt" role="tabpanel">
                        <?php include 'voucher_receipt_tab.php'; ?>
                    </div>
                    <!-- Journal Voucher Tab -->
                    <div class="tab-pane fade" id="journal" role="tabpanel">
                        <?php include 'voucher_journal_tab.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
</div>
<?php include_once __DIR__ . '/../includes/end.php'; ?>

<script>
// Clear other voucher forms when switching tabs
document.addEventListener('DOMContentLoaded', function() {
    var tabButtons = document.querySelectorAll('#voucherTabs [data-bs-toggle="tab"]');
    tabButtons.forEach(function(btn) {
        btn.addEventListener('shown.bs.tab', function(e) {
            var targetId = e.target.getAttribute('data-bs-target') || e.target.getAttribute('href');
            // Normalize id (may be #payment etc)
            if (targetId && targetId.charAt(0) === '#') targetId = targetId.substring(1);

            // Clear payment if not active
            if (targetId !== 'payment') clearPaymentForm();
            // Clear receipt if not active
            if (targetId !== 'receipt') clearReceiptForm();
            // Clear journal if not active
            if (targetId !== 'journal') clearJournalForm();
        });
    });

    function clearPaymentForm() {
        try {
            var container = document.getElementById('paymentVoucherRows');
            if (!container) return;
            var rows = container.querySelectorAll('.payment-row');
            // remove extra rows, keep first
            for (var i = rows.length - 1; i >= 1; i--) rows[i].remove();
            var first = container.querySelector('.payment-row');
            if (first) {
                first.querySelectorAll('input').forEach(function(inp){ inp.value = ''; });
                first.querySelectorAll('select').forEach(function(sel){ sel.selectedIndex = 0; });
                var rem = first.querySelector('.remove-row'); if (rem) rem.style.display = 'none';
            }
            var td = document.getElementById('payment_total_debit'); if (td) td.textContent = '0.00';
            var tc = document.getElementById('payment_total_credit'); if (tc) tc.textContent = '0.00';
            var bal = document.getElementById('payment_balance'); if (bal) bal.textContent = '';
            var submit = document.querySelector('#paymentVoucherForm button[type="submit"]'); if (submit) submit.setAttribute('disabled','disabled');
            if (typeof recalcTotals === 'function') recalcTotals();
        } catch (e) { console.error(e); }
    }

    function clearReceiptForm() {
        try {
            var container = document.getElementById('receiptVoucherRows');
            if (!container) return;
            var rows = container.querySelectorAll('.receipt-row');
            for (var i = rows.length - 1; i >= 1; i--) rows[i].remove();
            var first = container.querySelector('.receipt-row');
            if (first) {
                first.querySelectorAll('input').forEach(function(inp){ inp.value = ''; });
                first.querySelectorAll('select').forEach(function(sel){ sel.selectedIndex = 0; });
                var rem = first.querySelector('.remove-row'); if (rem) rem.style.display = 'none';
            }
            var td = document.getElementById('receipt_total_debit'); if (td) td.textContent = '0.00';
            var tc = document.getElementById('receipt_total_credit'); if (tc) tc.textContent = '0.00';
            var bal = document.getElementById('receipt_balance'); if (bal) bal.textContent = '';
            var submit = document.querySelector('#receiptVoucherForm button[type="submit"]'); if (submit) submit.setAttribute('disabled','disabled');
            if (typeof recalcReceiptTotals === 'function') recalcReceiptTotals();
        } catch (e) { console.error(e); }
    }

    function clearJournalForm() {
        try {
            var dCont = document.getElementById('journalDebitRows');
            var cCont = document.getElementById('journalCreditRows');
            if (dCont) {
                var dRows = dCont.querySelectorAll('.journal-debit-row');
                for (var i = dRows.length - 1; i >= 1; i--) dRows[i].remove();
                var firstD = dCont.querySelector('.journal-debit-row');
                if (firstD) {
                    firstD.querySelectorAll('input').forEach(function(inp){ inp.value = ''; });
                    firstD.querySelectorAll('select').forEach(function(sel){ sel.selectedIndex = 0; });
                    var rem = firstD.querySelector('.remove-debit-row'); if (rem) rem.style.display = 'none';
                }
            }
            if (cCont) {
                var cRows = cCont.querySelectorAll('.journal-credit-row');
                for (var i = cRows.length - 1; i >= 1; i--) cRows[i].remove();
                var firstC = cCont.querySelector('.journal-credit-row');
                if (firstC) {
                    firstC.querySelectorAll('input').forEach(function(inp){ inp.value = ''; });
                    firstC.querySelectorAll('select').forEach(function(sel){ sel.selectedIndex = 0; });
                    var rem = firstC.querySelector('.remove-credit-row'); if (rem) rem.style.display = 'none';
                }
            }
            var td = document.getElementById('journal_total_debit'); if (td) td.textContent = '0.00';
            var tc = document.getElementById('journal_total_credit'); if (tc) tc.textContent = '0.00';
            var bal = document.getElementById('journal_balance'); if (bal) bal.textContent = '';
            var submit = document.querySelector('#journalVoucherForm button[type="submit"]'); if (submit) submit.setAttribute('disabled','disabled');
            if (typeof recalcJournalTotals === 'function') recalcJournalTotals();
        } catch (e) { console.error(e); }
    }

    // Optionally clear all on initial load (only if first tab not payment)
    // clearReceiptForm(); clearJournalForm();
});
</script>
