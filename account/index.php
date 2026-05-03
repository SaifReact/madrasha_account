<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';
$allAccountType = [
    ['name' => 'সম্পদ (Asset)', 'id' => '1', 'label' => 'A'],
    ['name' => 'দায় (Liability)', 'id' => '2', 'label' => 'L'],
    ['name' => 'আয় (Income)', 'id' => '3', 'label' => 'I'],
    ['name' => 'ব্যয় (Expense)', 'id' => '4', 'label' => 'E'],
];

// Fetch all general ledger entries with hierarchical structure
$stmt = $pdo->query("SELECT * FROM glac_mst ORDER BY glac_code ASC");
$ledgers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Example: Insert Level 1
function insertLevel1($pdo, $name) {
    $stmt = $pdo->prepare("SELECT MAX(CAST(glac_code AS UNSIGNED)) as max_code FROM glac_mst WHERE level_code = 1");
    $stmt->execute();
    $max = $stmt->fetchColumn();
    $new_code = $max ? $max + 1 : 1;
    $stmt = $pdo->prepare("INSERT INTO glac_mst (glac_name, glac_code, parent_id, level_code) VALUES (?, ?, 0, 1)");
    $stmt->execute([$name, $new_code]);
    return $pdo->lastInsertId();
}

// Example: Insert Level 2
function insertLevel2($pdo, $parent_id, $name) {
    // Get parent glac_code
    $stmt = $pdo->prepare("SELECT glac_code FROM glac_mst WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent_code = $stmt->fetchColumn();
    // Find max child code
    $stmt = $pdo->prepare("SELECT MAX(CAST(glac_code AS UNSIGNED)) as max_code FROM glac_mst WHERE parent_id = ? AND level_code = 2");
    $stmt->execute([$parent_id]);
    $max = $stmt->fetchColumn();
    $suffix = $max ? substr($max, -2) + 1 : 1;
    $new_code = $parent_code . str_pad($suffix, 2, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO glac_mst (glac_name, glac_code, parent_id, level_code) VALUES (?, ?, ?, 2)");
    $stmt->execute([$name, $new_code, $parent_id]);
    return $pdo->lastInsertId();
}

// Example: Insert Level 3
function insertLevel3($pdo, $parent_id, $name) {
    $stmt = $pdo->prepare("SELECT glac_code FROM glac_mst WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent_code = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT MAX(CAST(glac_code AS UNSIGNED)) as max_code FROM glac_mst WHERE parent_id = ? AND level_code = 3");
    $stmt->execute([$parent_id]);
    $max = $stmt->fetchColumn();
    $suffix = $max ? substr($max, -2) + 1 : 1;
    $new_code = $parent_code . str_pad($suffix, 2, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO glac_mst (glac_name, glac_code, parent_id, level_code) VALUES (?, ?, ?, 3)");
    $stmt->execute([$name, $new_code, $parent_id]);
    return $pdo->lastInsertId();
}

// Example: Insert Level 4
function insertLevel4($pdo, $parent_id, $name) {
    $stmt = $pdo->prepare("SELECT glac_code FROM glac_mst WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent_code = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT MAX(CAST(glac_code AS UNSIGNED)) as max_code FROM glac_mst WHERE parent_id = ? AND level_code = 4");
    $stmt->execute([$parent_id]);
    $max = $stmt->fetchColumn();
    $suffix = $max ? substr($max, -3) + 1 : 1;
    $new_code = $parent_code . str_pad($suffix, 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO glac_mst (glac_name, glac_code, parent_id, level_code) VALUES (?, ?, ?, 4)");
    $stmt->execute([$name, $new_code, $parent_id]);
    return $pdo->lastInsertId();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // ট্রানজাকশন শুরু করা (যদি আগে না করা থাকে)
if ($pdo->inTransaction() === false) {
    $pdo->beginTransaction();
}

        if ($_POST['action'] === 'add') {
            $glac_name = $_POST['glac_name'] ?? '';
            $glac_type = $_POST['glac_type'] ?? '';
            $parent_id = intval($_POST['parent_id'] ?? 0);
            $gl_nature = $_POST['gl_nature'] ?? 'D';
            $allow_manual_dr = $_POST['allow_manual_dr'] ?? 'Y';
            $allow_manual_cr = $_POST['allow_manual_cr'] ?? 'Y';
            $is_bank_balance = isset($_POST['is_bank_balance']) ? 1 : 0;
            $is_cash_in_hand = isset($_POST['is_cash_in_hand']) ? 1 : 0;
            $parent_child = $_POST['parent_child'] ?? 'P';
            $status = $_POST['status'] ?? 'A';
            $created_by = $_SESSION['user_id'];
            $inserted_id = null;
            if ($parent_id == 0) {
                // Level 1
                $inserted_id = insertLevel1($pdo, $glac_name);
                // Update other fields for this row
                $stmt = $pdo->prepare("UPDATE glac_mst SET glac_type=?, gl_nature=?, allow_manual_dr=?, allow_manual_cr=?, is_bank_balance=?, is_cash_in_hand=?, parent_child=?, status=?, created_by=? WHERE id=?");
                $stmt->execute([$glac_type, $gl_nature, $allow_manual_dr, $allow_manual_cr, $is_bank_balance, $is_cash_in_hand, $parent_child, $status, $created_by, $inserted_id]);
            } else {
                // Get parent level
                $stmt = $pdo->prepare("SELECT level_code FROM glac_mst WHERE id = ?");
                $stmt->execute([$parent_id]);
                $parent_level = $stmt->fetchColumn();
                if ($parent_level == 1) {
                    $inserted_id = insertLevel2($pdo, $parent_id, $glac_name);
                } elseif ($parent_level == 2) {
                    $inserted_id = insertLevel3($pdo, $parent_id, $glac_name);
                } elseif ($parent_level == 3) {
                    $inserted_id = insertLevel4($pdo, $parent_id, $glac_name);
                }
                // Update other fields for this row
                $stmt = $pdo->prepare("UPDATE glac_mst SET glac_type=?, gl_nature=?, allow_manual_dr=?, allow_manual_cr=?, is_bank_balance=?, is_cash_in_hand=?, parent_child=?, status=?, created_by=? WHERE id=?");
                $stmt->execute([$glac_type, $gl_nature, $allow_manual_dr, $allow_manual_cr, $is_bank_balance, $is_cash_in_hand, $parent_child, $status, $created_by, $inserted_id]);
            }
            $pdo->commit();
            $_SESSION['success_msg'] = '✅ সফলভাবে জেনারেল লেজার এন্ট্রি যোগ করা হয়েছে!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'update') {
            $id = intval($_POST['id'] ?? 0);
            $glac_name = $_POST['glac_name'] ?? '';
            $gl_nature = $_POST['gl_nature'] ?? 'D';
            $allow_manual_dr = $_POST['allow_manual_dr'] ?? 'Y';
            $allow_manual_cr = $_POST['allow_manual_cr'] ?? 'Y';
            $is_bank_balance = isset($_POST['is_bank_balance']) ? 1 : 0;
            $is_cash_in_hand = isset($_POST['is_cash_in_hand']) ? 1 : 0;
            $status = $_POST['status'] ?? 'A';
            $updated_by = $_SESSION['user_id'];
            
            $stmt = $pdo->prepare("UPDATE glac_mst SET glac_name = ?, gl_nature = ?, allow_manual_dr = ?, allow_manual_cr = ?, is_bank_balance = ?, is_cash_in_hand = ?, status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$glac_name, $gl_nature, $allow_manual_dr, $allow_manual_cr, $is_bank_balance, $is_cash_in_hand, $status, $updated_by, $id]);
            
            $pdo->commit();
            
            $_SESSION['success_msg'] = '✅ সফলভাবে জেনারেল লেজার আপডেট করা হয়েছে!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
        if ($_POST['action'] === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            
            // Check if has children
            $stmt = $pdo->prepare("SELECT COUNT(*) as child_count FROM glac_mst WHERE parent_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['child_count'] > 0) {
                $_SESSION['error_msg'] = '❌ এই লেজারের চাইল্ড আছে, প্রথমে চাইল্ড ডিলিট করুন!';
            } else {
                $stmt = $pdo->prepare("DELETE FROM glac_mst WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success_msg'] = '✅ সফলভাবে জেনারেল লেজার ডিলিট করা হয়েছে!';
            }
            
            $pdo->commit();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_msg'] = '❌ Error: ' . $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
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
                    <h3 class="text-primary fw-bold mb-0">জেনারেল লেজার <span class="text-secondary">(General Ledger)</span></h3>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addLedgerModal">
                        <i class="bi bi-plus-circle"></i> জেনারেল লেজার তৈরি
                    </button>
                </div>
                <hr class="mb-4" />

                <?php if (isset($_SESSION['success_msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success_msg'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success_msg']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_msg'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error_msg'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error_msg']); ?>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%"></th>
                                <th width="5%">ক্রমিক</th>
                                <th width="10%">লেজার কোড</th>
                                <th width="20%">লেজারের নাম</th>
                                <th width="10%">প্যারেন্ট/চাইল্ড</th>
                                <th width="10%">প্যারেন্ট আইডি</th>
                                <th width="10%">লেজার টাইপ</th>
                                <th width="10%">লেভেল কোড</th>
                                <th width="10%">লেজার প্রকৃতি</th>
                                <th width="10%">অ্যাকশন</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ledgers)): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted">কোন ডেটা পাওয়া যায়নি</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ledgers as $index => $ledger): ?>
                                    <tr>
                                        <td>
                                            <button class="btn btn-sm btn-link text-secondary" type="button">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </td>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($ledger['glac_code']) ?></td>
                                        <td><?= htmlspecialchars($ledger['glac_name']) ?></td>
                                        <td><?= htmlspecialchars($ledger['parent_child']) ?></td>
                                        <td><?= htmlspecialchars($ledger['parent_id']) ?></td>
                                        <td>
                                            <?php
                                            $type_label = '';
                                            switch($ledger['glac_type']) {
                                                case '1': $type_label = 'Asset'; break;
                                                case '2': $type_label = 'Liability'; break;
                                                case '3': $type_label = 'Income'; break;
                                                case '4': $type_label = 'Expense'; break;
                                            }
                                            echo htmlspecialchars($type_label);
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($ledger['level_code']) ?></td>
                                        <td><?= htmlspecialchars($ledger['gl_nature']) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-btn" 
                                                data-id="<?= $ledger['id'] ?>"
                                                data-name="<?= htmlspecialchars($ledger['glac_name']) ?>"
                                                data-nature="<?= htmlspecialchars($ledger['gl_nature']) ?>"
                                                data-dr="<?= htmlspecialchars($ledger['allow_manual_dr']) ?>"
                                                data-cr="<?= htmlspecialchars($ledger['allow_manual_cr']) ?>"
                                                data-status="<?= htmlspecialchars($ledger['status']) ?>"
                                                data-bank="<?= htmlspecialchars($ledger['is_bank_balance'] ?? 0) ?>"
                                                data-cash="<?= htmlspecialchars($ledger['is_cash_in_hand'] ?? 0) ?>"
                                                data-bs-toggle="modal" data-bs-target="#editLedgerModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-btn" 
                                                data-id="<?= $ledger['id'] ?>"
                                                data-name="<?= htmlspecialchars($ledger['glac_name']) ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Add Ledger Modal -->
<div class="modal fade" id="addLedgerModal" tabindex="-1" aria-labelledby="addLedgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addLedgerModalLabel">জেনারেল লেজার তৈরি</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">লেজার স্তর <span class="text-danger">(Ledger Level)*</span></label>
                            <select class="form-select" name="glac_type" id="glac_type" required>
                                <option value="">নির্বাচন করুন</option>
                                <?php foreach ($allAccountType as $accountType): ?>
                                    <option value="<?= $accountType['id'] ?>"><?= htmlspecialchars($accountType['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Level 1 এর জন্য প্রথমে স্তর নির্বাচন করুন</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">প্যারেন্ট লেজার <span class="text-danger">(Parent Ledger)*</span></label>
                            <select class="form-select" name="parent_id" id="parent_id">
                                <option value="0" data-type="">কোনটি নয় (Root Level 1)</option>
                                <?php foreach ($ledgers as $ledger): ?>
                                    <option value="<?= $ledger['id'] ?>" data-type="<?= htmlspecialchars($ledger['glac_type']) ?>"><?= htmlspecialchars($ledger['glac_name']) ?> (<?= htmlspecialchars($ledger['glac_code']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Level 2/3/4 এর জন্য প্যারেন্ট নির্বাচন করুন</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">লেজারের নাম <span class="text-danger">(Ledger Name)*</span></label>
                            <input type="text" class="form-control" name="glac_name" placeholder="লেজারের নাম লিখুন" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">জিএল প্রকৃতি <span class="text-danger">(GL Nature)*</span></label>
                            <select class="form-select" name="gl_nature" required>
                                <option value="D">ডেবিট (Debit)</option>
                                <option value="C">ক্রেডিট (Credit)</option>
                            </select>
                        </div>
                    </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">অবস্থা <span class="text-danger">(Status)*</span></label>
                                <select class="form-select" name="status" required>
                                    <option value="A" selected>সক্রিয় (Active)</option>
                                    <option value="I">নিষ্ক্রিয় (Inactive)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ধরণ <span class="text-danger">(Type)*</span></label>
                                <div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="parent_child" id="typeParent" value="P" checked required>
                                        <label class="form-check-label" for="typeParent">প্যারেন্ট (P)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="parent_child" id="typeChild" value="C" required>
                                        <label class="form-check-label" for="typeChild">চাইল্ড (C)</label>
                                    </div>
                                </div>
                                <small class="text-muted">প্যারেন্ট/চাইল্ড টাইপ নির্বাচন করুন</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_bank_balance" id="is_bank_balance" value="1">
                                    <label class="form-check-label" for="is_bank_balance">Bank Balance হিসেবে চিহ্নিত</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_cash_in_hand" id="is_cash_in_hand" value="1">
                                    <label class="form-check-label" for="is_cash_in_hand">Cash In Hand হিসেবে চিহ্নিত</label>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ম্যানুয়াল ডেবিট অনুমতি <span class="text-danger">(Allow Manual DR)*</span></label>
                            <select class="form-select" name="allow_manual_dr" required>
                                <option value="Y">হ্যাঁ (Y)</option>
                                <option value="N">না (N)</option>
                            </select>
                        </div>
                       <div class="col-md-6">
                            <label class="form-label">ম্যানুয়াল ক্রেডিট অনুমতি <span class="text-danger">(Allow Manual CR)*</span></label>
                            <select class="form-select" name="allow_manual_cr" required>
                                <option value="Y">হ্যাঁ (Y)</option>
                                <option value="N">না (N)</option>
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <strong>📝 নোট:</strong>
                        <ul class="mb-0">
                            <li>Level 1: parent_id = 0, level_code = 1 (1, 2, 3, 4)</li>
                            <li>Level 2: parent_id = Level 1 ID, level_code = 2 (101, 102, 103, 104)</li>
                            <li>Level 3: parent_id = Level 2 ID, level_code = 3 (10101, 10201, 10301, 10401)</li>
                            <li>Level 4: parent_id = Level 3 ID, level_code = 4 (10101001, 10201001, 10301001, 10401001)</li>
                        </ul>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-check-circle"></i> সংরক্ষণ করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Ledger Modal -->
<div class="modal fade" id="editLedgerModal" tabindex="-1" aria-labelledby="editLedgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editLedgerModalLabel">জেনারেল লেজার সম্পাদন</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">লেজারের নাম <span class="text-danger">(Ledger Name)*</span></label>
                        <input type="text" class="form-control" name="glac_name" id="edit_glac_name" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">জিএল প্রকৃতি <span class="text-danger">(GL Nature)*</span></label>
                            <select class="form-select" name="gl_nature" id="edit_gl_nature" required>
                                <option value="D">ডেবিট (Debit)</option>
                                <option value="C">ক্রেডিট (Credit)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">অবস্থা <span class="text-danger">(Status)*</span></label>
                            <select class="form-select" name="status" id="edit_status" required>
                                <option value="A">সক্রিয় (Active)</option>
                                <option value="N">নিষ্ক্রিয় (Inactive)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_bank_balance" id="edit_is_bank_balance" value="1">
                                    <label class="form-check-label" for="edit_is_bank_balance">Bank Balance হিসেবে চিহ্নিত</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_cash_in_hand" id="edit_is_cash_in_hand" value="1">
                                    <label class="form-check-label" for="edit_is_cash_in_hand">Cash In Hand হিসেবে চিহ্নিত</label>
                                </div>
                            </div>
                        </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ম্যানুয়াল ডেবিট অনুমতি <span class="text-danger">(Allow Manual DR)*</span></label>
                            <select class="form-select" name="allow_manual_dr" id="edit_allow_manual_dr" required>
                                <option value="Y">হ্যাঁ (Y)</option>
                                <option value="N">না (N)</option>
                            </select>
                        </div>
                       <div class="col-md-6">
                            <label class="form-label">ম্যানুয়াল ক্রেডিট অনুমতি <span class="text-danger">(Allow Manual CR)*</span></label>
                            <select class="form-select" name="allow_manual_cr" id="edit_allow_manual_cr" required>
                                <option value="Y">হ্যাঁ (Y)</option>
                                <option value="N">না (N)</option>
                            </select>
                        </div>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> আপডেট করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteLedgerModal" tabindex="-1" aria-labelledby="deleteLedgerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteLedgerModalLabel">নিশ্চিত করুন</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>আপনি কি নিশ্চিত যে এই লেজার ডিলিট করতে চান?</p>
                <p class="text-danger fw-bold" id="delete_ledger_name"></p>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="text-center">
                        <button type="submit" class="btn btn-danger px-4">
                            <i class="bi bi-trash"></i> হ্যাঁ, ডিলিট করুন
                        </button>
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                            না, বাতিল করুন
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Edit button click handler
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_glac_name').value = this.dataset.name;
        document.getElementById('edit_gl_nature').value = this.dataset.nature;
        document.getElementById('edit_allow_manual_dr').value = this.dataset.dr;
        document.getElementById('edit_allow_manual_cr').value = this.dataset.cr;
        document.getElementById('edit_status').value = this.dataset.status;
        // set bank/cash checkboxes
        var bankCheckbox = document.getElementById('edit_is_bank_balance');
        var cashCheckbox = document.getElementById('edit_is_cash_in_hand');
        if (bankCheckbox) bankCheckbox.checked = (this.dataset.bank == '1');
        if (cashCheckbox) cashCheckbox.checked = (this.dataset.cash == '1');
    });
});

// Delete button click handler
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_id').value = this.dataset.id;
        document.getElementById('delete_ledger_name').textContent = this.dataset.name;
        new bootstrap.Modal(document.getElementById('deleteLedgerModal')).show();
    });
});
</script>

<script>
// Filter parent list based on selected glac_type when adding new ledger
document.addEventListener('DOMContentLoaded', function() {
    var glacType = document.getElementById('glac_type');
    var parentSelect = document.getElementById('parent_id');
    if (!glacType || !parentSelect) return;

    // Capture original options
    var original = Array.from(parentSelect.options).map(function(opt) {
        return { value: opt.value, text: opt.text, type: opt.dataset.type || '' };
    });

    function renderFiltered() {
        var sel = glacType.value;
        parentSelect.innerHTML = '';
        // Always include root
        var root = document.createElement('option');
        root.value = '0';
        root.dataset.type = '';
        root.text = 'কোনটি নয় (Root Level 1)';
        parentSelect.appendChild(root);

        original.forEach(function(o) {
            if (o.value === '0') return; // skip root duplicate
            if (sel === '' || o.type === sel) {
                var opt = document.createElement('option');
                opt.value = o.value;
                opt.text = o.text;
                opt.dataset.type = o.type;
                parentSelect.appendChild(opt);
            }
        });
    }

    // Filter on change
    glacType.addEventListener('change', renderFiltered);
    // Initial render (in case form preserved state)
    renderFiltered();

    // Sync Allow Manual DR/CR based on Parent/Child selection in Add modal
    (function(){
        var addModal = document.getElementById('addLedgerModal');
        if (!addModal) return;

        function syncAllowManual(val){
            var dr = addModal.querySelector('select[name="allow_manual_dr"]');
            var cr = addModal.querySelector('select[name="allow_manual_cr"]');
            if (!dr || !cr) return;
            if (val === 'P'){
                dr.value = 'N';
                cr.value = 'N';
            } else if (val === 'C'){
                dr.value = 'Y';
                cr.value = 'Y';
            }
        }

        var radios = addModal.querySelectorAll('input[name="parent_child"]');
        radios.forEach(function(r){
            r.addEventListener('change', function(){
                syncAllowManual(this.value);
            });
        });

        // Initial sync based on currently checked radio
        var checked = addModal.querySelector('input[name="parent_child"]:checked');
        if (checked) syncAllowManual(checked.value);
    })();
});
</script>

</div>
</div>

<?php include_once __DIR__ . '/../includes/end.php'; ?>
