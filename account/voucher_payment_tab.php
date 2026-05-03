<?php
// Prepare GL lists for Credit and Debit dropdowns
$creditGLs = [];
$debitGLs = [];
try {
    if (!isset($pdo)) {
        include __DIR__ . '/../config/config.php';
    }

    $stmt = $pdo->prepare("SELECT g.id, g.glac_name, g.glac_code, g.level_code, p.glac_name AS parent_name FROM glac_mst g LEFT JOIN glac_mst p ON g.parent_id = p.id WHERE g.parent_child = 'C' AND g.gl_nature = 'C' AND g.status = 'A' ORDER BY g.id ASC");
    $stmt->execute();
    $debitGLs  = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT g.id, g.glac_name, g.glac_code, g.level_code, p.glac_name AS parent_name FROM glac_mst g LEFT JOIN glac_mst p ON g.parent_id = p.id WHERE g.parent_child = 'C' AND g.gl_nature = 'D' AND g.status = 'A' AND ( COALESCE(g.is_bank_balance, FALSE) = TRUE OR COALESCE(g.is_cash_in_hand, FALSE) = TRUE ) ORDER BY g.id ASC");
    $stmt->execute();
    $creditGLs = $stmt->fetchAll();
} catch (Exception $e) {
    // Fail silently — leave lists empty
}
?>

<form id="paymentVoucherForm" method="post" action="../process/payment_voucher_process.php">
    <div id="paymentVoucherRows">
        <div class="row g-2 mb-3 payment-row">
            <div class="col-md-3">
                <select class="form-select" name="debit_gl[]">
                    <option value="">ডেবিট জি.এল</option>
                    <?php if (!empty($debitGLs)) : ?>
                        <?php foreach ($debitGLs as $g) : ?>
                            <?php
                                $label = htmlspecialchars($g['parent_name']);
                                if (!empty($g['level_code']) && intval($g['level_code']) === 4) {
                                    $parent = htmlspecialchars($g['glac_name'] ?? '');
                                    $code = htmlspecialchars($g['glac_code'] ?? '');
                                    $label .= ' (' . $parent . ' - ' . $code . ')';
                                }
                            ?>
                            <option value="<?= htmlspecialchars($g['id']) ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="credit_gl[]">
                    <option value="">ক্রেডিট জি.এল</option>
                    <?php if (!empty($creditGLs)) : ?>
                        <?php foreach ($creditGLs as $g) : ?>
                            <?php
                                $label = htmlspecialchars($g['parent_name']);
                                if (!empty($g['level_code']) && intval($g['level_code']) === 4) {
                                    $parent = htmlspecialchars($g['glac_name'] ?? '');
                                    $code = htmlspecialchars($g['glac_code'] ?? '');
                                    $label .= ' (' . $parent . ' - ' . $code . ')';
                                }
                            ?>
                            <option value="<?= htmlspecialchars($g['id']) ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" class="form-control" name="amount[]" placeholder="টাকার পরিমাণ">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" name="narration[]" placeholder="হিসাবের বিবরণ">
            </div>
            <div class="col-md-1 d-flex align-items-center">
                <button type="button" class="btn btn-outline-primary add-row" onclick="addPaymentRow(this)">+</button>
                <button type="button" class="btn btn-outline-danger ms-1 remove-row" style="display:none;" onclick="removePaymentRow(this)">×</button>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <span>মোট ডেবিট টাকা : <span id="payment_total_debit">0.00</span></span>
        </div>
        <div class="col-md-6">
            <span>মোট ক্রেডিট টাকা : <span id="payment_total_credit">0.00</span></span>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">ব্যালেন্স</label>
        <div id="payment_balance"></div>
    </div>
    <div class="mt-4 text-end">
        <button type="submit" name="payment_submit" class="btn btn-primary btn-lg px-4 shadow-sm" disabled>পেমেন্ট ভাউচার জমা দিন</button>
    </div>
</form>
<script>
function addPaymentRow(btn) {
    var row = btn.closest('.payment-row');
    var newRow = row.cloneNode(true);
    // Clear input values
    newRow.querySelectorAll('input').forEach(function(input) { input.value = ''; });
    newRow.querySelectorAll('select').forEach(function(select) { select.selectedIndex = 0; });
    // Show remove button for all but first row
    newRow.querySelector('.remove-row').style.display = '';
    document.getElementById('paymentVoucherRows').appendChild(newRow);
    // Show remove button for this row too (if not first)
    row.querySelector('.remove-row').style.display = '';
    recalcTotals();
}
function removePaymentRow(btn) {
    var row = btn.closest('.payment-row');
    var container = document.getElementById('paymentVoucherRows');
    if (container.children.length > 1) {
        row.remove();
        recalcTotals();
    }
}
</script>

<script>
// Calculate totals for credit/debit and update UI
function parseAmount(str) {
    if (!str) return 0;
    // Remove commas and non-numeric except dot and minus
    str = String(str).replace(/,/g, '').replace(/[^0-9.\-]/g, '');
    var v = parseFloat(str);
    return isNaN(v) ? 0 : v;
}

function recalcTotals() {
    var totalCredit = 0;
    var totalDebit = 0;
    document.querySelectorAll('#paymentVoucherRows .payment-row').forEach(function(row) {
        var amtInput = row.querySelector('input[name="amount[]"]');
        var amount = parseAmount(amtInput ? amtInput.value : 0);
        var creditSel = row.querySelector('select[name="credit_gl[]"]');
        var debitSel = row.querySelector('select[name="debit_gl[]"]');
        if (creditSel && creditSel.value) totalCredit += amount;
        if (debitSel && debitSel.value) totalDebit += amount;
    });
    var td = document.getElementById('payment_total_debit');
    var tc = document.getElementById('payment_total_credit');
    if (td) td.textContent = totalDebit.toFixed(2);
    if (tc) tc.textContent = totalCredit.toFixed(2);
    var balanceEl = document.getElementById('payment_balance');
    if (balanceEl) {
        var bal = totalCredit - totalDebit;
        balanceEl.textContent = bal.toFixed(2);
    }

    // Enable submit only if balanced and totals > 0
    var submitBtn = document.querySelector('#paymentVoucherForm button[type="submit"]');
    if (submitBtn) {
        var diff = Math.abs(totalCredit - totalDebit);
        if (diff < 0.005 && totalCredit > 0) {
            submitBtn.removeAttribute('disabled');
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
        }
    }
}

// Event delegation for inputs/selects inside paymentVoucherRows
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('paymentVoucherRows');
    if (!container) return;
    container.addEventListener('input', function(e) {
        if (e.target && e.target.matches('input[name="amount[]"]')) {
            recalcTotals();
        }
    });
    container.addEventListener('change', function(e) {
        if (e.target && (e.target.matches('select[name="credit_gl[]"]') || e.target.matches('select[name="debit_gl[]"]'))) {
            recalcTotals();
        }
    });
    // Initial calc
    recalcTotals();
});
</script>
