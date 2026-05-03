<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

function englishToBanglaNumber($number) {
    $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', ','];
    $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯', '.', ','];
    return str_replace($en, $bn, $number);
}

$stmt = $pdo->query("SELECT gs.glac_id, gm.glac_name, gm.glac_code, gs.debit_amount, gs.credit_amount FROM gl_summary gs LEFT JOIN glac_mst gm ON gm.id = gs.glac_id ORDER BY gm.glac_code ASC, gm.glac_name ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_debit = 0;
$total_credit = 0;
foreach ($rows as $row) {
    $total_debit += (float)($row['debit_amount'] ?? 0);
    $total_credit += (float)($row['credit_amount'] ?? 0);
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="text-primary fw-bold mb-0">লেনদেন সারসংক্ষেপ <span class="text-secondary">(Transaction Summary)</span></h3>
                </div>
                <hr class="mb-4" />

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>জেনারেল লেজার (GL)</th>
                                <th class="text-end">ডেবিট (Debit)</th>
                                <th class="text-end">ক্রেডিট (Credit)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($rows) > 0): ?>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($row['glac_name'] ?? 'N/A'); ?>
                                            <?php if (!empty($row['glac_code'])): ?>
                                                <span class="text-muted">(<?= htmlspecialchars($row['glac_code']); ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">৳ <?= englishToBanglaNumber(number_format((float)($row['debit_amount'] ?? 0), 2)); ?></td>
                                        <td class="text-end">৳ <?= englishToBanglaNumber(number_format((float)($row['credit_amount'] ?? 0), 2)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="fw-bold">
                                    <td class="text-end">মোট</td>
                                    <td class="text-end">৳ <?= englishToBanglaNumber(number_format($total_debit, 2)); ?></td>
                                    <td class="text-end">৳ <?= englishToBanglaNumber(number_format($total_credit, 2)); ?></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">কোনো তথ্য পাওয়া যায়নি।</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
</div>
</div>

<?php include_once __DIR__ . '/../includes/end.php'; ?>
