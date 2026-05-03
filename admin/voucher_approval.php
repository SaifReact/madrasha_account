<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];

// Fetch all voucher_payments table with status 'I' (pending) and join with glac_mst for glac_id and glac_name

$stmtVoucher = $pdo->query("SELECT v.*, g.glac_name, g.glac_code, CASE WHEN v.drcr_code = 'D' THEN 'ডেবিট' ELSE 'ক্রেডিট' END AS drcr_code FROM voucher_payments v JOIN glac_mst g ON v.glac_id = g.id WHERE v.status = 'I' ORDER BY v.id DESC");
$voucherPayments = $stmtVoucher->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($method === 'POST' && isset($_POST['status'])) {

    $status = in_array($_POST['status'], ['A', 'I', 'R']) ? $_POST['status'] : 'I';

    // determine which voucher was submitted and resolve member info from it
    $submitted_voucher_id = (int)($_POST['vp_id'] ?? 0);

    if ($submitted_voucher_id > 0) {
        $stmtVoucherSingle = $pdo->prepare("SELECT * FROM voucher_payments WHERE id = ? LIMIT 1");
        $stmtVoucherSingle->execute([$submitted_voucher_id]);
        $voucherData = $stmtVoucherSingle->fetch(PDO::FETCH_ASSOC);
        if ($voucherData) {
            $glac_id = (int)$voucherData['glac_id'];
            $tran_amount = $voucherData['tran_amount'] ?? 0;
        }
    }

    // If the requested status is not approval, just update that single share row and skip approval processing.
    if ($submitted_voucher_id > 0 && $status !== 'A') {
        $stmtSimpleMark = $pdo->prepare("UPDATE voucher_payments SET status = ? WHERE id = ?");
        $stmtSimpleMark->execute([$status, $submitted_voucher_id]);
    }

        // If approving, generate project_share rows and update member_share when project_id = 1
        if ($status === 'A') { 
            // if the glac_id in gl_summary table then sum of tran_amount Update debit or credit wise  otherwise Insert new row in gl_summary table
            $stmtCheckGlSummary = $pdo->prepare("SELECT * FROM gl_summary WHERE glac_id = ? LIMIT 1");
            $stmtCheckGlSummary->execute([$glac_id]);   
            $glSummaryData = $stmtCheckGlSummary->fetch(PDO::FETCH_ASSOC);

            if ($glSummaryData) {
                $current_debit = $glSummaryData['debit_amount'] ?? 0;
                $current_credit = $glSummaryData['credit_amount'] ?? 0;
                
                if (isset($voucherData['drcr_code']) && $voucherData['drcr_code'] === 'D') {
                    $new_debit = ($tran_amount && $tran_amount != 0) ? ($current_debit + $tran_amount) : $current_debit;
                    $new_credit = $current_credit;
                } elseif (isset($voucherData['drcr_code']) && $voucherData['drcr_code'] === 'C') {
                    $new_credit = ($tran_amount && $tran_amount != 0) ? ($current_credit + $tran_amount) : $current_credit;
                    $new_debit = $current_debit;
                }

                $stmtUpdateGlSummary = $pdo->prepare("UPDATE gl_summary SET tran_date = ?, debit_amount = ?, credit_amount = ?, created_by = ? WHERE glac_id = ?");
                $stmtUpdateGlSummary->execute([date('Y-m-d'), $new_debit, $new_credit, $user_id, $glac_id]);
            } else {
                $debit_amount = 0;
                $credit_amount = 0;
                if (isset($voucherData['drcr_code']) && $voucherData['drcr_code'] === 'D') {
                    $debit_amount = $tran_amount ?? 0;
                } elseif (isset($voucherData['drcr_code']) && $voucherData['drcr_code'] === 'C') {
                    $credit_amount = $tran_amount ?? 0;
                }
                $stmtInsertGlSummary = $pdo->prepare("INSERT INTO gl_summary (glac_id, tran_date, debit_amount, credit_amount, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmtInsertGlSummary->execute([$glac_id, date('Y-m-d'), $debit_amount, $credit_amount, $user_id]);
            }       
                $stmtApprove = $pdo->prepare("UPDATE voucher_payments SET status = ? WHERE id = ?");
                $stmtApprove->execute([$status, $submitted_voucher_id]);                 
        }
    if ($status === 'A') {
        $_SESSION['success_msg'] = "✅ ভাউচার পোস্টিং অনুমোদন দেয়া হলো !";
    } elseif ($status === 'I') {
        $_SESSION['success_msg'] = "⚠️ ভাউচার পোস্টিং নিষ্ক্রিয় করে রাখা হইলো !";
    } elseif ($status === 'R') {
        $_SESSION['success_msg'] = "❌ ভাউচার পোস্টিং বাতিল করা হইলো !";
    }

    // Stay on the same page with success message
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
?>

<?php 
include_once __DIR__ . '/../includes/open.php';
include_once __DIR__ . '/../includes/side_bar.php'; 
?>
    <main class="col-12 col-md-10 col-lg-10 col-xl-10 px-md-3">
        <div class="row px-2">
            <div class="card shadow-lg rounded-3 border-0">
                <div class="card-body p-4">
                    <h3 class="mb-3 text-primary fw-bold">Voucher Posting Approval <span class="text-secondary">( ভাউচার পোস্টিং অনুমোদন )</span></h3> 
                    <hr class="mb-4" />
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>নং </th>
                                        <th>জি.এল নাম </th>
                                        <th>তারিখ</th>
                                        <th>টাকার পরিমান</th>
                                        <th>ডেবিট/ক্রেডিট</th>
                                        <th>মন্তব্য</th>
                                        <th colspan="1">অবস্থা</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($voucherPayments as $voucherPayment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($voucherPayment['id']) ?></td>
                                        <td><?= htmlspecialchars($voucherPayment['glac_id']) ?></br>
                                            <?= htmlspecialchars($voucherPayment['glac_name']) ?></br>
                                            <?= htmlspecialchars($voucherPayment['glac_code']) ?></td>
                                        <td><?= htmlspecialchars($voucherPayment['tran_date']) ?></td>
                                        <td><?= htmlspecialchars($voucherPayment['tran_amount']) ?></td>
                                        <td><?= htmlspecialchars($voucherPayment['drcr_code']) ?></td>
                                        <td><?= htmlspecialchars($voucherPayment['remarks']) ?></td>
                                        <td>
                                            <form method="post" class="d-flex align-items-center">
                                                <input type="hidden" name="vp_id" value="<?= $voucherPayment['id'] ?>">
                                                <select name="status" class="form-select form-select-sm me-2">
                                                    <option value="A" <?= $voucherPayment['status'] === 'A' ? 'selected' : '' ?>>✅ Approved</option>
                                                    <option value="I" <?= $voucherPayment['status'] === 'I' ? 'selected' : '' ?>>⏸️ Inactive</option>
                                                    <option value="R" <?= $voucherPayment['status'] === 'R' ? 'selected' : '' ?>>❌ Rejected</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary btn-sm">Update (হালনাগাদ)</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </div>
    </main>
  </div>
</div>
<!-- Hero End -->

<?php include_once __DIR__ . '/../includes/end.php'; ?>


