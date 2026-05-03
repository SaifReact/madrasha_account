<?php
session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';
include_once __DIR__ . '/../includes/head.php';

//Fetch Data from utils table for GL Mapping

$query = "SELECT * FROM utils WHERE status = 'A'";
$stmt = $pdo->prepare($query);
$stmt->execute();
$utils_data = $stmt->fetchAll(PDO::FETCH_ASSOC);



// Always fetch glac_mst for dropdowns
$query_glac = "SELECT * FROM glac_mst WHERE parent_child = 'C'";
$stmt_glac = $pdo->prepare($query_glac);
$stmt_glac->execute();
$glac_data = $stmt_glac->fetchAll(PDO::FETCH_ASSOC);

// Check if gl_mapping has data
$query_gl_mapping = "SELECT gm.*, g1.glac_name AS glac_name, g1.glac_code AS glac_code, g2.glac_name AS contra_name, g2.glac_code AS contra_code FROM gl_mapping gm
    LEFT JOIN glac_mst g1 ON gm.credit_glac_id = g1.id
    LEFT JOIN glac_mst g2 ON gm.debit_glac_id = g2.id";
$stmt_map = $pdo->prepare($query_gl_mapping);
$stmt_map->execute();
$gl_mapping_data = $stmt_map->fetchAll(PDO::FETCH_ASSOC);

?>

<?php 
include_once __DIR__ . '/../includes/open.php';
include_once __DIR__ . '/../includes/side_bar.php'; 
?>

<main class="col-12 col-md-10 col-lg-10 col-xl-10 px-md-3">
    <div class="row px-2">
        <div class="card shadow-lg rounded-3 border-0">
            <div class="card-body p-4">
                <h3 class="mb-3 text-primary fw-bold">GL Mapping</h3>
                <hr class="mb-4" />
                <form method="post" action="../process/gl_mapping_process.php">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ক্রম</th>
                                    <th>লেনদেনের ধরন</th>
                                    <th>ক্রেডিট জি.এল</th>
                                    <th>ডেবিট জি.এল</th>
                                    <th>অবস্থা</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($gl_mapping_data)) {
                                    foreach ($gl_mapping_data as $row) {
                                        $selected_type = $row['tran_type'] ?? '';
                                        $selected_type_name = $row['tran_type_name'] ?? '';
                                        $selected_gl = $row['credit_glac_id'] ?? '';
                                        $selected_contra = $row['debit_glac_id'] ?? '';
                                        $is_active = isset($row['is_active']) ? ($row['is_active'] ? 'সক্রিয়' : 'নিষ্ক্রিয়') : 'সক্রিয়';
                                ?>
                                    <tr>
                                        <td width="5%"> <?= $row['id'] ?> </td>
                                        <td width="20%">
                                            <select class="form-select" name="fee_type[]">
                                                <?php
                                                $type_name_bn = array_unique(array_column($utils_data, 'type_name_bn'));
                                                foreach ($type_name_bn as $ftype):
                                                    $ftype_id = '';
                                                    foreach ($utils_data as $u) {
                                                        if ($u['type_name_bn'] == $ftype) {
                                                            $ftype_id = $u['id'];
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                    <option value="<?= $ftype_id ?>" <?= ($selected_type_name == $ftype || $selected_type == $ftype_id) ? 'selected' : '' ?>><?= $ftype ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="fee_type_name[]" value="<?= htmlspecialchars($selected_type_name) ?>">
                                        </td>
                                        <td width="30%">
                                            <select class="form-select" name="gl[]">
                                                <?php foreach ($glac_data as $glac): ?>
                                                    <option value="<?= $glac['id'] ?>" <?= $selected_gl == $glac['id'] ? 'selected' : '' ?>>
                                                        <?= $glac['glac_name'] . ' - ' . $glac['glac_code'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td width="30%">
                                            <select class="form-select" name="contra[]">
                                                <?php foreach ($glac_data as $glac): ?>
                                                    <option value="<?= $glac['id'] ?>" <?= $selected_contra == $glac['id'] ? 'selected' : '' ?>>
                                                        <?= $glac['glac_name'] . ' - ' . $glac['glac_code'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td width="15%">
                                            <select class="form-select" name="type[]">
                                                <option value="সক্রিয়" <?= $is_active == 'সক্রিয়' ? 'selected' : '' ?>>সক্রিয়</option>
                                                <option value="নিষ্ক্রিয়" <?= $is_active == 'নিষ্ক্রিয়' ? 'selected' : '' ?>>নিষ্ক্রিয়</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php }
                                } else {
                                    foreach ($utils_data as $row) {
                                        $selected_type_name = $row['type_name_bn'] ?? '';
                                ?>
                                    <tr>
                                        <td width="5%"> <?= $row['id'] ?> </td>
                                        <td width="20%">
                                            <select class="form-select" name="fee_type[]">
                                                <?php
                                                $type_name_bn = array_unique(array_column($utils_data, 'type_name_bn'));
                                                foreach ($type_name_bn as $ftype):
                                                    $ftype_id = '';
                                                    foreach ($utils_data as $u) {
                                                        if ($u['type_name_bn'] == $ftype) {
                                                            $ftype_id = $u['id'];
                                                            break;
                                                        }
                                                    }
                                                ?>
                                                    <option value="<?= $ftype_id ?>" <?= $selected_type_name == $ftype ? 'selected' : '' ?>><?= $ftype ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <input type="hidden" name="fee_type_name[]" value="<?= htmlspecialchars($selected_type_name) ?>">
                                        </td>
                                        <td width="30%">
                                            <select class="form-select" name="gl[]">
                                                <?php foreach ($glac_data as $glac): ?>
                                                    <option value="<?= $glac['id'] ?>">
                                                        <?= $glac['glac_name'] . ' - ' . $glac['glac_code'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td width="30%">
                                            <select class="form-select" name="contra[]">
                                                <?php foreach ($glac_data as $glac): ?>
                                                    <option value="<?= $glac['id'] ?>">
                                                        <?= $glac['glac_name'] . ' - ' . $glac['glac_code'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td width="15%">
                                            <select class="form-select" name="type[]">
                                                <option value = "সক্রিয়">সক্রিয়</option>
                                                <option value = "নিষ্ক্রিয়">নিষ্ক্রিয়</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm">Save Mapping (সংরক্ষণ করুন)</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
</div>
</div>
<?php include_once __DIR__ . '/../includes/end.php'; ?>
