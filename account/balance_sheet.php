<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

try {
    $stmt = $pdo->query("
        SELECT 
            g.id, 
            g.glac_name, 
            g.glac_type, 
            g.glac_code, 
            COALESCE(s.debit_amount,0) as debit_amount, 
            COALESCE(s.credit_amount,0) as credit_amount
        FROM glac_mst g
        LEFT JOIN gl_summary s ON g.id = s.glac_id
        WHERE g.parent_id = 11 OR g.id = 11
        ORDER BY g.glac_type ASC, g.glac_code ASC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Query Error: " . $e->getMessage());
}

$typeMap = [
    '1' => 'সম্পদ (Assets)',
    '2' => 'দায় (Liabilities)',
    '5' => 'মূলধন (Owner Equity)',
];

// ЁЯФ┤ Grouping
$grouped = [];
foreach ($rows as $row) {
    $type = (string)($row['glac_type'] ?? '0');
    if (isset($typeMap[$type])) {
        $grouped[$type][] = $row;
    }
}

// ЁЯФ┤ Bangla Number
function bn($number) {
    $en = ['0','1','2','3','4','5','6','7','8','9','.',' ,'];
    $bn = ['১','২','৩','৪','৫','৬','৭','৮','৯','০','.',' ,'];
    return str_replace($en, $bn, $number);
}

// ЁЯФ┤ Include UI Layout
include_once __DIR__ . '/../includes/open.php';
include_once __DIR__ . '/../includes/side_bar.php';
?>

<main class="col-12 col-md-10 col-lg-10 col-xl-10 px-md-3">
    <div class="row px-2">
        <div class="card shadow-lg rounded-3 border-0">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="text-primary fw-bold mb-0">ব্যালেন্স শীট <span class="text-secondary">(Balance Sheet)</span></h3>
                </div>
                <hr class="mb-4" />
                
                <div class="row">

                <?php if (empty($rows)): ?>
                    <div class="alert alert-warning">
                        কোন তথ্য পাওয়া যায়নি (No Information Found)
                    </div>
                <?php else: ?>

                    <?php 
                    $grand_total = 0;
                    ?>

                    <?php foreach ($typeMap as $type => $title): ?>

                        <?php if (empty($grouped[$type])) continue; ?>

                        <?php 
                        $type_total = 0;
                        ?>
                        
                        <div class="col-md-6"> 
                        <div class="table-responsive mb-4">
                            <h4 class="bg-primary text-white p-2 rounded">
                                <?= $title ?>
                            </h4>

                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>GL Code</th>
                                        <th>GL Name</th>
                                        <th class="text-end">Balance</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($grouped[$type] as $row): 
                                        $balance = $row['debit_amount'] - $row['credit_amount'];
                                        $type_total += $balance;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['glac_code']) ?></td>
                                        <td><?= htmlspecialchars($row['glac_name']) ?></td>
                                        <td class="text-end">
                                            ৳ <?= bn(number_format($balance, 2)) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>

                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="2" class="text-end">Subtotal</td>
                                        <td class="text-end">
                                            ৳ <?= bn(number_format($type_total, 2)) ?>
                                        </td>
                                    </tr>
                                </tfoot>

                            </table>
                        </div>
                        </div>
                        <?php 
                        $grand_total += $type_total;
                        ?>

                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
    </div>
    </div>
</main>
</div>
</div>

<?php include_once __DIR__ . '/../includes/end.php'; ?>