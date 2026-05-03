<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

$member_id = isset($_SESSION['member_id'])? $_SESSION['member_id'] : '';

// Payment folder
$payment_folder = '../payment/';
if (!is_dir($payment_folder)) {
    mkdir($payment_folder, 0777, true);
}

// Helper to upload image
function uploadPaymentSlip($file) {
    global $payment_folder;
    global $member_id;
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'pdf'])) {
            return null;
        }
        $filename = 'payment_slip_' . $member_id . '_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $target = $payment_folder . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            return $filename;
        }
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_year = $_POST['payment_year'] ?? '';
    $project_id = $_POST['project_id'] ?? 0;
    $member_id = $_SESSION['member_id'] ?? 0;
    $member_code = $_SESSION['member_code'] ?? '';
    $payment_method = $_POST['payment_type'] ?? '';
    $tran_type = $_POST['tran_type'] ?? '';
    $late_tran_type = $_POST['late_tran_type'] ?? '0';
    $amount = floatval($_POST['amount'] ?? 0);
    $months_advance = $_POST['monthsAdvance'] ?? 0;
    $bank_pay_date = $_POST['payment_date'] ?? '';
    // Convert empty date to NULL
    $bank_pay_date = !empty($bank_pay_date) ? $bank_pay_date : null;
    $bank_trans_no = $_POST['bank_trans'] ?? '';
    $pay_mode = $_POST['pay_mode'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $created_by = $_SESSION['user_id'];
    $created_at = date('Y-m-d H:i:s');
    $total_share_value = floatval($_POST['total_share_value'] ?? 0);

    // Get no_share and extra_share from member_share table
    $stmt = $pdo->prepare("SELECT no_share, extra_share, admission_fee FROM member_share WHERE member_id = ? LIMIT 1");
    $stmt->execute([$member_id]);
    $share_data = $stmt->fetch();
    $no_share = $share_data ? (float)$share_data['no_share'] : 1;
    $extra_share = $share_data ? (float)$share_data['extra_share'] : 0;
    $monthly_fee = 2000; // Default monthly fee

    // Check if payment already exists for this month and year
    if ($payment_method !== 'admission' && $payment_method !== 'Samity Share' && $payment_method !== 'Project Share') {
        $stmt = $pdo->prepare("SELECT id FROM member_payments WHERE member_id = ? AND payment_method = ? AND payment_year = ? LIMIT 1");
        $stmt->execute([$member_id, $payment_method, $payment_year]);
        if ($stmt->fetch()) {
            $_SESSION['error_msg'] = 'Payment for this month and year already exists.';
            header('Location: ../users/payment.php');
            exit;
        }
    }
    // Generate serial_no for this payment_method and payment_year
    $serial_no = 1;
    $stmt = $pdo->prepare("SELECT MAX(serial_no) as max_serial FROM member_payments WHERE payment_method = ? AND payment_year = ?");
    $stmt->execute([$payment_method, $payment_year]);
    if ($row = $stmt->fetch()) {
        $serial_no = intval($row['max_serial']) + 1;
    }

    // Generate trans_no as payment_method-payment_year-serial_no
    $trans_no = 'TR' . strtoupper($payment_method) . $payment_year . $serial_no;

    $pay_slip = null;
    if (isset($_FILES['payment_slip']) && isset($_FILES['payment_slip']['tmp_name']) && $_FILES['payment_slip']['tmp_name'] != '') {
        $pay_slip = uploadPaymentSlip($_FILES['payment_slip']);
    }

    $months = ['january','february','march','april','may','june','july','august','september','october','november','december'];
    if ($payment_method == 'advance') {
        // Get all paid months for this user (only for 'Monthly' payments)
        $paid_months = [];
        $stmt = $pdo->prepare("SELECT for_fees, payment_year FROM member_payments WHERE member_id = ? AND payment_method = 'Monthly'");
        $stmt->execute([$member_id]);
        while($row = $stmt->fetch()) {
            $paid_months[$row['payment_year'] . '-' . strtolower($row['for_fees'])] = true;
        }
        // Find the first $months_advance unpaid months from current year
        $unpaid_months = [];
        for ($y = $payment_year; $y <= $payment_year + 1; $y++) {
            for ($m = 0; $m < 12; $m++) {
                if (count($unpaid_months) >= $months_advance) break 2;
                if (empty($paid_months[$y . '-' . $months[$m]])) {
                    $unpaid_months[] = ['year' => $y, 'month_index' => $m, 'month_name' => $months[$m]];
                }
            }
        }
        if (count($unpaid_months) < $months_advance) {
            $_SESSION['error_msg'] = 'Unpaid month not enough for advance payment.';
            header('Location: ../users/payment.php');
            exit;
        }
        $inserted = 0;
        
        // Calculate per month amount
        $per_month_amount = $monthly_fee;
        $for_install = round($amount * 0.95, 2);
        $other_fee = round($amount * 0.05, 2);
        foreach ($unpaid_months as $unpaid) {
            $y = $unpaid['year'];
            $m = $unpaid['month_index'];
            $cur_month = $unpaid['month_name'];
            // Serial no for this month
            $serial_no = 1;
            $stmt = $pdo->prepare("SELECT MAX(serial_no) as max_serial FROM member_payments WHERE payment_method = ? AND payment_year = ?");
            $stmt->execute([$cur_month, $y]);
            if ($row = $stmt->fetch()) {
                $serial_no = intval($row['max_serial']) + 1;
            }
            $trans_no = 'TR' . strtoupper($cur_month) . $y . $serial_no;
            $stmt = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type,project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $member_code, 'Monthly', $tran_type, $project_id, $y, $bank_pay_date, $bank_trans_no, $trans_no, $serial_no, $per_month_amount, $cur_month, $created_at, $created_by, $pay_slip, 'I', $pay_mode, $remarks]);
            $inserted++;
        }

        if ($inserted > 0) {
            $_SESSION['success_msg'] = '✅ সফলভাবে পেমেন্ট করা হয়েছে ' . $inserted . ' মাসের জন্য, অনুমোদনের জন্য অপেক্ষা করুন (Payment successful for ' . $inserted . ' months, please wait for approval)';
        } else {
            $_SESSION['error_msg'] = 'Already paid for selected months or invalid amount.';
        }
        header('Location: ../users/payment.php');
        exit;
    } elseif ($amount > 0 && $payment_method !== 'advance') {
       
        $for_install = round($amount * 0.95, 2);
        $other_fee = round($amount * 0.05, 2);
        
        // Case 1: tran_type = 2 -> Insert one Monthly transaction
        if ($tran_type == 2 && $late_tran_type == 0) {
            $stmt = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type, project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $member_code, 'Monthly', $tran_type, $project_id, $payment_year, $bank_pay_date, $bank_trans_no, $trans_no, $serial_no, $amount, $payment_method, $created_at, $created_by, $pay_slip, 'I', $pay_mode, $remarks]);
            $_SESSION['success_msg'] = '✅ সফলভাবে পেমেন্ট করা হয়েছে, অনুমোদনের জন্য অপেক্ষা করুন (Payment successful, please wait for approval)';
        }
        // Case 2: late_tran_type = 3 -> Insert two transactions (Monthly and Late)
        elseif ($tran_type == 2 && $late_tran_type == 3) {
            $late_fee = 0;
            if ($amount > $monthly_fee) {
                $late_fee = round($amount - $monthly_fee, 2);
            }
            // Insert Monthly transaction
            $stmt = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type, project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $member_code, 'Monthly', $tran_type, $project_id, $payment_year, $bank_pay_date, $bank_trans_no, $trans_no, $serial_no, $monthly_fee, $payment_method, $created_at, $created_by, $pay_slip, 'I', $pay_mode, $remarks]);
            
            // Generate serial_no for Late transaction
            $serial_no_late = 1;
            $stmt = $pdo->prepare("SELECT MAX(serial_no) as max_serial FROM member_payments WHERE payment_method = 'Late' AND payment_year = ?");
            $stmt->execute([$payment_year]);
            if ($row = $stmt->fetch()) {
                $serial_no_late = intval($row['max_serial']) + 1;
            }
            $trans_no_late = 'TRLATE' . $payment_year . $serial_no_late;
            
            // Insert Late transaction
            $stmt = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type, project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $member_code, 'Late', $late_tran_type, $project_id, $payment_year, $bank_pay_date, $bank_trans_no, $trans_no_late, $serial_no_late, $late_fee, $payment_method, $created_at, $created_by, $pay_slip, 'I', $pay_mode, $remarks]);
            
            $_SESSION['success_msg'] = '✅ সফলভাবে পেমেন্ট করা হয়েছে (মাসিক এবং বিলম্ব ফি), অনুমোদনের জন্য অপেক্ষা করুন (Payment successful for Monthly and Late Fee, please wait for approval)';
        }

        header('Location: ../users/payment.php');
        exit;
    } else {
        $_SESSION['error_msg'] = 'Invalid payment type or amount.';
        header('Location: ../users/payment.php');
        exit;
    }
}
?>

