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
    $creditGLs = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT g.id, g.glac_name, g.glac_code, g.level_code, p.glac_name AS parent_name FROM glac_mst g LEFT JOIN glac_mst p ON g.parent_id = p.id WHERE g.parent_child = 'C' AND g.gl_nature = 'D' AND g.status = 'A' AND ( COALESCE(g.is_bank_balance, FALSE) = TRUE OR COALESCE(g.is_cash_in_hand, FALSE) = TRUE ) ORDER BY g.id ASC");
    $stmt->execute();
    $debitGLs = $stmt->fetchAll();
} catch (Exception $e) {
    // Fail silently — leave lists empty
}
?>

<form id="receiptVoucherForm" method="post" action="../process/receipt_voucher_process.php">
    <div id="receiptVoucherRows">
        <div class="row g-2 mb-3 receipt-row">
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
                <button type="button" class="btn btn-outline-primary add-row" onclick="addReceiptRow(this)">+</button>
                <button type="button" class="btn btn-outline-danger ms-1 remove-row" style="display:none;" onclick="removeReceiptRow(this)">×</button>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <span>মোট ডেবিট টাকা : <span id="receipt_total_debit">0.00</span></span>
        </div>
        <div class="col-md-6">
            <span>মোট ক্রেডিট টাকা : <span id="receipt_total_credit">0.00</span></span>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">ব্যালেন্স</label>
        <div id="receipt_balance"></div>
    </div>
    <div class="mt-4 text-end">
        <button type="submit" name="receipt_submit" class="btn btn-primary btn-lg px-4 shadow-sm" disabled>রিসিভ ভাউচার জমা দিন</button>
    </div>
</form>
<script>
function addReceiptRow(btn) {
    var row = btn.closest('.receipt-row');
    var newRow = row.cloneNode(true);
    // Clear input values
    newRow.querySelectorAll('input').forEach(function(input) { input.value = ''; });
    newRow.querySelectorAll('select').forEach(function(select) { select.selectedIndex = 0; });
    // Show remove button for all but first row
    newRow.querySelector('.remove-row').style.display = '';
    document.getElementById('receiptVoucherRows').appendChild(newRow);
    // Show remove button for this row too (if not first)
    row.querySelector('.remove-row').style.display = '';
    recalcReceiptTotals();
}
function removeReceiptRow(btn) {
    var row = btn.closest('.receipt-row');
    var container = document.getElementById('receiptVoucherRows');
    if (container.children.length > 1) {
        row.remove();
        recalcReceiptTotals();
    }
}
</script>

<script>
// Calculate totals for receipt form (debit first, credit second)
function parseAmountReceipt(str) {
    if (!str) return 0;
    str = String(str).replace(/,/g, '').replace(/[^0-9.\-]/g, '');
    var v = parseFloat(str);
    return isNaN(v) ? 0 : v;
}

function recalcReceiptTotals() {
    var totalDebit = 0;
    var totalCredit = 0;
    document.querySelectorAll('#receiptVoucherRows .receipt-row').forEach(function(row) {
        var amtInput = row.querySelector('input[name="amount[]"]');
        var amount = parseAmountReceipt(amtInput ? amtInput.value : 0);
        var debitSel = row.querySelector('select[name="debit_gl[]"]');
        var creditSel = row.querySelector('select[name="credit_gl[]"]');
        if (debitSel && debitSel.value) totalDebit += amount;
        if (creditSel && creditSel.value) totalCredit += amount;
    });

    var td = document.getElementById('receipt_total_debit');
    var tc = document.getElementById('receipt_total_credit');
    if (td) td.textContent = totalDebit.toFixed(2);
    if (tc) tc.textContent = totalCredit.toFixed(2);

    var balanceEl = document.getElementById('receipt_balance');
    if (balanceEl) balanceEl.textContent = (totalDebit - totalCredit).toFixed(2);

    var submitBtn = document.querySelector('#receiptVoucherForm button[type="submit"]');
    if (submitBtn) {
        var diff = Math.abs(totalDebit - totalCredit);
        if (diff < 0.005 && totalDebit > 0) {
            submitBtn.removeAttribute('disabled');
        } else {
            submitBtn.setAttribute('disabled', 'disabled');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('receiptVoucherRows');
    if (!container) return;
    container.addEventListener('input', function(e) {
        if (e.target && e.target.matches('input[name="amount[]"]')) {
            recalcReceiptTotals();
        }
    });
    container.addEventListener('change', function(e) {
        if (e.target && (e.target.matches('select[name="debit_gl[]"]') || e.target.matches('select[name="credit_gl[]"]'))) {
            recalcReceiptTotals();
        }
    });
    // initial
    recalcReceiptTotals();
});
</script>
