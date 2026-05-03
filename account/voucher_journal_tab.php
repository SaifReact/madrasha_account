<?php
// Prepare GL lists for Credit and Debit dropdowns
$creditGLs = [];
$debitGLs = [];
try {
    if (!isset($pdo)) {
        include __DIR__ . '/../config/config.php';
    }

    $stmt = $pdo->prepare("SELECT g.id, g.glac_name, g.glac_code, g.level_code, p.glac_name AS parent_name FROM glac_mst g LEFT JOIN glac_mst p ON g.parent_id = p.id WHERE g.parent_child = 'C' AND g.gl_nature in ('D','C') AND g.status = 'A' ORDER BY g.id ASC");
    $stmt->execute();
    $creditGLs = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT g.id, g.glac_name, g.glac_code, g.level_code, p.glac_name AS parent_name FROM glac_mst g LEFT JOIN glac_mst p ON g.parent_id = p.id WHERE g.parent_child = 'C' AND g.gl_nature in ('D','C') AND g.status = 'A'  ORDER BY g.id ASC");
    $stmt->execute();
    $debitGLs = $stmt->fetchAll();
} catch (Exception $e) {
    // Fail silently — leave lists empty
}
?>

<form id="journalVoucherForm" method="post" action="../process/journal_voucher_process.php">
    <div class="border rounded p-3 mb-3 bg-light">
        <div class="fw-bold mb-2">ডেবিট অংশ</div>
        <div id="journalDebitRows">
            <div class="row g-2 mb-3 journal-debit-row">
                <div class="col-md-4">
                    <select class="form-select" name="debit_gl[]">
                        <option>ডেবিট জি.এল</option>
                        <?php foreach ($debitGLs as $gl): ?>
                            <?php
                                $label = htmlspecialchars($gl['parent_name']);
                                if (!empty($gl['level_code']) && intval($gl['level_code']) === 4) {
                                    $parent = htmlspecialchars($gl['glac_name'] ?? '');
                                    $code = htmlspecialchars($gl['glac_code'] ?? '');
                                    $label .= ' (' . $parent . ' - ' . $code . ')';
                                }   
                            ?>
                            <option value="<?= htmlspecialchars($gl['id']) ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="debit_amount[]" placeholder="টাকার পরিমাণ (ডেবিট)">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="debit_narration[]" placeholder="হিসাবের বিবরণ (ডেবিট)">
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <button type="button" class="btn btn-outline-primary add-debit-row" onclick="addJournalDebitRow(this)" type="button">+</button>
                    <button type="button" class="btn btn-outline-danger ms-1 remove-debit-row" style="display:none;" onclick="removeJournalDebitRow(this)" type="button">×</button>
                </div>
            </div>
        </div>
    </div>
    <div class="border rounded p-3 mb-3 bg-light">
        <div class="fw-bold mb-2">ক্রেডিট অংশ</div>
        <div id="journalCreditRows">
            <div class="row g-2 mb-3 journal-credit-row">
                <div class="col-md-4">
                    <select class="form-select" name="credit_gl[]">
                        <option>ক্রেডিট জি.এল</option>
                        <?php foreach ($creditGLs as $gl): ?>
                            <?php
                                $label = htmlspecialchars($gl['parent_name']);
                                if (!empty($gl['level_code']) && intval($gl['level_code']) === 4) {
                                    $parent = htmlspecialchars($gl['glac_name'] ?? '');
                                    $code = htmlspecialchars($gl['glac_code'] ?? '');
                                    $label .= ' (' . $parent . ' - ' . $code . ')';
                                }   
                            ?>
                            <option value="<?= htmlspecialchars($gl['id']) ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" class="form-control" name="credit_amount[]" placeholder="টাকার পরিমাণ (ক্রেডিট)">
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="credit_narration[]" placeholder="হিসাবের বিবরণ (ক্রেডিট)">
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <button type="button" class="btn btn-outline-primary add-credit-row" onclick="addJournalCreditRow(this)" type="button">+</button>
                    <button type="button" class="btn btn-outline-danger ms-1 remove-credit-row" style="display:none;" onclick="removeJournalCreditRow(this)" type="button">×</button>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <span>মোট ডেবিট টাকা : <span id="journal_total_debit">0.00</span></span>
        </div>
        <div class="col-md-6">
            <span>মোট ক্রেডিট টাকা : <span id="journal_total_credit">0.00</span></span>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label">ব্যালেন্স</label>
        <div id="journal_balance"></div>
    </div>
    <div class="mt-4 text-end">
        <button type="submit" name="journal_submit" class="btn btn-primary btn-lg px-4 shadow-sm" disabled>জার্নাল ভাউচার জমা দিন</button>
    </div>
</form>
<script>
function addJournalDebitRow(btn) {
    var row = btn.closest('.journal-debit-row');
    var newRow = row.cloneNode(true);
    newRow.querySelectorAll('input').forEach(function(input) { input.value = ''; });
    newRow.querySelectorAll('select').forEach(function(select) { select.selectedIndex = 0; });
    newRow.querySelector('.remove-debit-row').style.display = '';
    document.getElementById('journalDebitRows').appendChild(newRow);
    row.querySelector('.remove-debit-row').style.display = '';
    recalcJournalTotals();
}
function removeJournalDebitRow(btn) {
    var row = btn.closest('.journal-debit-row');
    var container = document.getElementById('journalDebitRows');
    if (container.children.length > 1) {
        row.remove();
        recalcJournalTotals();
    }
}
function addJournalCreditRow(btn) {
    var row = btn.closest('.journal-credit-row');
    var newRow = row.cloneNode(true);
    newRow.querySelectorAll('input').forEach(function(input) { input.value = ''; });
    newRow.querySelectorAll('select').forEach(function(select) { select.selectedIndex = 0; });
    newRow.querySelector('.remove-credit-row').style.display = '';
    document.getElementById('journalCreditRows').appendChild(newRow);
    row.querySelector('.remove-credit-row').style.display = '';
    recalcJournalTotals();
}
function removeJournalCreditRow(btn) {
    var row = btn.closest('.journal-credit-row');
    var container = document.getElementById('journalCreditRows');
    if (container.children.length > 1) {
        row.remove();
        recalcJournalTotals();
    }
}
</script>

<script>
function parseJournalAmount(str) {
    if (!str) return 0;
    str = String(str).replace(/,/g, '').replace(/[^0-9.\-]/g, '');
    var v = parseFloat(str);
    return isNaN(v) ? 0 : v;
}

function recalcJournalTotals() {
    var totalDebit = 0;
    var totalCredit = 0;
    document.querySelectorAll('#journalDebitRows input[name="debit_amount[]"]').forEach(function(input) {
        totalDebit += parseJournalAmount(input.value || 0);
    });
    document.querySelectorAll('#journalCreditRows input[name="credit_amount[]"]').forEach(function(input) {
        totalCredit += parseJournalAmount(input.value || 0);
    });

    var td = document.getElementById('journal_total_debit');
    var tc = document.getElementById('journal_total_credit');
    if (td) td.textContent = totalDebit.toFixed(2);
    if (tc) tc.textContent = totalCredit.toFixed(2);

    var balanceEl = document.getElementById('journal_balance');
    if (balanceEl) balanceEl.textContent = (totalDebit - totalCredit).toFixed(2);

    var submitBtn = document.querySelector('#journalVoucherForm button[type="submit"]');
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
    var dContainer = document.getElementById('journalDebitRows');
    var cContainer = document.getElementById('journalCreditRows');
    if (dContainer) {
        dContainer.addEventListener('input', function(e) {
            if (e.target && e.target.matches('input[name="debit_amount[]"]')) recalcJournalTotals();
        });
    }
    if (cContainer) {
        cContainer.addEventListener('input', function(e) {
            if (e.target && e.target.matches('input[name="credit_amount[]"]')) recalcJournalTotals();
        });
    }
    recalcJournalTotals();
});
</script>
