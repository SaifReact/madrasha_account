<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['journal_submit'])) {
    try {
        if (!isset($pdo)) {
            include __DIR__ . '/../config/config.php';
        }
        $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Account';
        $tran_date = date('Y-m-d');
        $status = 'I';
        $now = date('Y-m-d H:i:s');

        // Insert Debit Rows
        if (!empty($_POST['debit_gl']) && is_array($_POST['debit_gl'])) {
            foreach ($_POST['debit_gl'] as $i => $glac_id) {
                $amount = isset($_POST['debit_amount'][$i]) ? floatval(str_replace(',', '', $_POST['debit_amount'][$i])) : 0;
                $remarks = isset($_POST['debit_narration'][$i]) ? $_POST['debit_narration'][$i] : '';
                if ($glac_id && $amount > 0) {
                    $stmt = $pdo->prepare("INSERT INTO voucher_payments (glac_id, tran_date, tran_amount, drcr_code, remarks, status, created_at, created_by) VALUES (?, ?, ?, 'D', ?, ?, ?, ?)");
                    $stmt->execute([$glac_id, $tran_date, $amount, $remarks, $status, $now, $created_by]);
                }
            }
        }
        // Insert Credit Rows
        if (!empty($_POST['credit_gl']) && is_array($_POST['credit_gl'])) {
            foreach ($_POST['credit_gl'] as $i => $glac_id) {
                $amount = isset($_POST['credit_amount'][$i]) ? floatval(str_replace(',', '', $_POST['credit_amount'][$i])) : 0;
                $remarks = isset($_POST['credit_narration'][$i]) ? $_POST['credit_narration'][$i] : '';
                if ($glac_id && $amount > 0) {
                    $stmt = $pdo->prepare("INSERT INTO voucher_payments (glac_id, tran_date, tran_amount, drcr_code, remarks, status, created_at, created_by) VALUES (?, ?, ?, 'C', ?, ?, ?, ?)");
                    $stmt->execute([$glac_id, $tran_date, $amount, $remarks, $status, $now, $created_by]);
                }
            }
        }
        $_SESSION['success_msg'] = 'Journal voucher saved successfully!';
        header('Location: ../account/voucher.php');
        exit();
    } catch (Exception $e) {
        // Redirect back with error
        $_SESSION['error_msg'] = 'Error saving journal voucher: ' . $e->getMessage();
        header('Location: ../account/voucher.php');
        exit();
    }
} else {
    header('Location: ../account/voucher.php');
    exit();
}
