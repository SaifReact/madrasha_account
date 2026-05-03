<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Account') {
    header('Location: ../login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_submit'])) {
    try {
        if (!isset($pdo)) {
            include __DIR__ . '/../config/config.php';
        }
        $created_by = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'system';
        $tran_date = date('Y-m-d');
        $status = 'I';
        $now = date('Y-m-d H:i:s');

        // Loop through all rows
        if (!empty($_POST['debit_gl']) && is_array($_POST['debit_gl']) && !empty($_POST['credit_gl']) && is_array($_POST['credit_gl'])) {
            $rowCount = max(count($_POST['debit_gl']), count($_POST['credit_gl']), count($_POST['amount']));
            for ($i = 0; $i < $rowCount; $i++) {
                $debit_gl = isset($_POST['debit_gl'][$i]) ? $_POST['debit_gl'][$i] : '';
                $credit_gl = isset($_POST['credit_gl'][$i]) ? $_POST['credit_gl'][$i] : '';
                $amount = isset($_POST['amount'][$i]) ? floatval(str_replace(',', '', $_POST['amount'][$i])) : 0;
                $narration = isset($_POST['narration'][$i]) ? $_POST['narration'][$i] : '';
                if ($debit_gl && $amount > 0) {
                    $stmt = $pdo->prepare("INSERT INTO voucher_payments (glac_id, tran_date, tran_amount, drcr_code, remarks, status, created_at, created_by) VALUES (?, ?, ?, 'D', ?, ?, ?, ?)");
                    $stmt->execute([$debit_gl, $tran_date, $amount, $narration, $status, $now, $created_by]);
                }
                if ($credit_gl && $amount > 0) {
                    $stmt = $pdo->prepare("INSERT INTO voucher_payments (glac_id, tran_date, tran_amount, drcr_code, remarks, status, created_at, created_by) VALUES (?, ?, ?, 'C', ?, ?, ?, ?)");
                    $stmt->execute([$credit_gl, $tran_date, $amount, $narration, $status, $now, $created_by]);
                }
            }
        }
        $_SESSION['success_msg'] = 'Payment voucher saved successfully!';
        header('Location: ../account/voucher.php');
        exit();
    } catch (Exception $e) {
        $_SESSION['error_msg'] = 'Error saving payment voucher: ' . $e->getMessage();
        header('Location: ../account/voucher.php');
        exit();
    }
} else {
    header('Location: ../account/voucher.php');
    exit();
}
