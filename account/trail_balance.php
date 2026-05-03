
<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

// Helper function to convert English numbers to Bangla
function bn_number(
    $number
) {
     $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', ','];
     $bn = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯', '.', ','];
    return str_replace($en, $bn, $number);
}

// Fetch all GL summary data with type
$stmt = $pdo->query("SELECT g.glac_code, g.glac_name, g.glac_type, s.debit_amount, s.credit_amount FROM gl_summary s JOIN glac_mst g ON s.glac_id = g.id ORDER BY g.glac_type ASC, g.glac_code ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group mapping
$typeMap = [
    '1' => ['title' => 'সম্পদ (Assets)'],
    '2' => ['title' => 'দায় (Liabilities)'],
    '3' => ['title' => 'আয় (Income)'],
    '4' => ['title' => 'ব্যয় (Expenses)'],
    '5' => ['title' => 'মূলধন (Capital)'],
];

// Group rows by type
$grouped = [];
foreach ($rows as $row) {
    $type = $row['glac_type'] ?? '0';
    $grouped[$type][] = $row;
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
                    <h3 class="text-primary fw-bold mb-0">লেনদেন সারসংক্ষেপ <span class="text-secondary">(Trial Balance)</span></h3>
                </div>
                <hr class="mb-4" />

                <?php 
                    $grand_debit = 0;
                    $grand_credit = 0;
                    foreach ($typeMap as $type => $info):
                        if (empty($grouped[$type])) continue;
                        $type_debit = 0;
                        $type_credit = 0;
                ?>

                <div class="table-responsive">
                    <h4 class="bg-primary text-white p-2 rounded"> <?= $info['title'] ?></h4>
                    <table class="table table-bordered table-striped">
            <thead class="table-light">
                <tr>
                    <th>GL Code</th>
                    <th>GL Name</th>
                    <th>Debit Amount</th>
                    <th>Credit Amount</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($grouped[$type] as $row): 
                $type_debit += $row['debit_amount'];
                $type_credit += $row['credit_amount'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['glac_code']) ?></td>
                    <td><?= htmlspecialchars($row['glac_name']) ?></td>
                    <td class="text-end">৳ <?= bn_number(number_format($row['debit_amount'], 2)) ?></td>
                    <td class="text-end">৳ <?= bn_number(number_format($row['credit_amount'], 2)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="2" class="text-end">Subtotal</td>
                    <td class="text-end">৳ <?= bn_number(number_format($type_debit, 2)) ?></td>
                    <td class="text-end">৳ <?= bn_number(number_format($type_credit, 2)) ?></td>
                </tr>
            </tfoot>
        </table>
                </div>
                <?php 
        $grand_debit += $type_debit;
        $grand_credit += $type_credit;
    endforeach; ?>
    <div class="alert alert-success fw-bold">
        Grand Total: <span class="float-end">Debit: ৳ <?= bn_number(number_format($grand_debit, 2)) ?> | Credit: ৳ <?= bn_number(number_format($grand_credit, 2)) ?></span>
    </div>
            </div>
        </div>
    </div>
</main>
</div>
</div>

<?php include_once __DIR__ . '/../includes/end.php'; ?>
