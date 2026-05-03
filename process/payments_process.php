<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

include_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['user_id'];

$member_id = isset($_SESSION['member_id'])? $_SESSION['member_id'] : 0;
$member_code = isset($_SESSION['member_code'])? $_SESSION['member_code'] : '';

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
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
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
    $payment_method = $_POST['payment_type'] ?? '';
    $tran_type = $_POST['tran_type'] ?? '';
    $payment_year = $_POST['payment_year'] ?? '';
    $project_id = $_POST['project_id'] ?? 0;
    $member_id = $_POST['member_id'] ?? 0;
    $member_code = $_POST['member_code'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $bank_pay_date = $_POST['payment_date'] ?? '';
    // Convert empty date to NULL
    $bank_pay_date = !empty($bank_pay_date) ? $bank_pay_date : null;
    $bank_trans_no = $_POST['bank_trans'] ?? '';
    $pay_mode = $_POST['pay_mode'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $total_share_value = floatval($_POST['total_share_value'] ?? 0);
    $memberProjectDate = $_POST['memberProjects'] ?? '0';
    $sundry_amt = floatval($_POST['sundry_amt'] ?? 0);

    // Fetch monthly fee from utils table
    $monthly_fee = 0; // Default value
    $stmt_utils = $pdo->prepare("SELECT * FROM utils WHERE fee_type in ('samity_share','monthly') AND status = 'A' LIMIT 1");
    $stmt_utils->execute();
    if ($row_utils = $stmt_utils->fetch()) {
        $monthly_fee = isset($row_utils['fee']) ? (float)$row_utils['fee'] : 2000;
        $per_share_value_utils = isset($row_utils['fee']) ? (float)$row_utils['fee'] : 5000;
    }

    // Get no_share and extra_share from member_share table
    $stmt = $pdo->prepare("SELECT no_share, samity_share, samity_share_amt, extra_share, admission_fee FROM member_share WHERE member_id = ? AND member_code = ? LIMIT 1");
    $stmt->execute([$member_id, $member_code]);
    $share_data = $stmt->fetch();
    $no_share = $share_data ? (float)$share_data['no_share'] : 0;
    $samity_share = $share_data ? (float)$share_data['samity_share'] : 0;
    $samity_share_amt = $share_data ? (float)$share_data['samity_share_amt'] : 0;
    $extra_share = $share_data ? (float)$share_data['extra_share'] : 0;

    // Check if admission_fee already paid for this user
    if ($payment_method === 'admission') {
        if ($share_data && isset($share_data['admission_fee']) && (float)$share_data['admission_fee'] > 0) {
            $_SESSION['error_msg'] = 'Admission fee already paid for this user.';
            header('Location: ../account/payment.php');
            exit;
        }
    }

    // Check if payment already exists for this month and year
    if ($payment_method !== 'admission' && $payment_method !== 'Samity Share' && $payment_method !== 'Project Share') {
        $stmt = $pdo->prepare("SELECT id FROM member_payments WHERE member_id = ? AND payment_method = ? AND payment_year = ? LIMIT 1");
        $stmt->execute([$member_id, $payment_method, $payment_year]);
        if ($stmt->fetch()) {
            $_SESSION['error_msg'] = 'Payment for this month and year already exists.';
            header('Location: ../account/payment.php');
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

    $pay_slip = uploadPaymentSlip($_FILES['payment_slip']);

    // ===================== ADMISSION PAYMENT =====================
    if ($payment_method === 'admission' && $amount > 0) {
        try {
            $pdo->beginTransaction();

            // Insert into member_payments table
            $stmtInsertPayments = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type, project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsertPayments->execute([$member_id, $member_code, $payment_method, $tran_type, 0, $payment_year, $bank_pay_date, $bank_trans_no, $trans_no, $serial_no, $amount, 'admission', date('Y-m-d'), $user_id, $pay_slip, 'I', $pay_mode, $remarks]);

            // Check if record exists in member_share table
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM member_share WHERE member_id = ? AND member_code = ?");
            $stmtCheck->execute([$member_id, $member_code]);
            $recordExists = $stmtCheck->fetchColumn();

            if ($recordExists) {
                // Fixed admission fees
                $admission_fee = $amount;
                $idcard_fee = 150;
                $passbook_fee = 200;
                $softuses_fee = 400;
                $sms_fee = 100;
                $office_rent = 300;
                $office_staff = 200;
                $other_fee = 150;

                // Update member_share table with the fixed fees
                $stmtUpdateMemberShare = $pdo->prepare("UPDATE member_share SET admission_fee = ?, idcard_fee = ?, passbook_fee = ?, softuses_fee = ?, sms_fee = ?, office_rent = ?, office_staff = ?, other_fee = ? WHERE member_id = ? AND member_code = ?");
                $stmtUpdateMemberShare->execute([$admission_fee, $idcard_fee, $passbook_fee, $softuses_fee, $sms_fee, $office_rent, $office_staff, $other_fee, $member_id, $member_code]);

                $pdo->commit();

                $_SESSION['success_msg'] = '✅ Admission Fee Payment Successfully..! (সফলভাবে সদস্য এন্ট্রি ফি পেমেন্ট করা হলো..!)';
                header('Location: ../account/payment_approval.php');
                exit;
            } else {
                $pdo->rollBack();
                $_SESSION['error_msg'] = '❌ Member Share Record Not Found for member_id: ' . $member_id . ' and member_code: ' . $member_code;
                header('Location: ../account/payment.php');
                exit;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = '❌ Error: ' . $e->getMessage();
            header('Location: ../account/payment.php');
            exit;
        }
    }
    
    // ===================== SAMITY SHARE PAYMENT =====================
    elseif ($payment_method === 'Samity Share' && $amount > 0) {
        try {
            $pdo->beginTransaction();

            $sundry_samity_share = $total_share_value;

            // Insert into member_payments table
            $stmt = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type, project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $member_code, $payment_method, $tran_type, 1, $payment_year, $bank_pay_date, $bank_trans_no, $trans_no, $serial_no, $amount, 'Samity Share', date('Y-m-d'), $user_id, $pay_slip, 'I', $pay_mode, $remarks]);
            // Update member_share table - SET sundry_share to the remaining balance
            $stmt = $pdo->prepare("UPDATE member_share SET samity_share_amt = samity_share_amt + ?, sundry_samity_share = ? WHERE member_id = ? AND member_code = ?");
            $stmt->execute([$amount, $sundry_samity_share, $member_id, $member_code]);

            // Only insert into member_project and project_share if sundry_samity_share is 0 (payment is complete)
            if ($memberProjectDate == 0) {
                // member_project table insert if samity_share > 0
                if ($samity_share > 0) {
                    $stmtInsertProject = $pdo->prepare("INSERT INTO member_project (member_id, member_code, project_id, project_share, share_amount, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmtInsertProject->execute([$member_id, $member_code, 1, 0, 0]);
                    $member_project_id = $pdo->lastInsertId();

                    // Insert into project_share table for each samity share
                    $stmtInsert = $pdo->prepare("INSERT INTO project_share (member_project_id, member_id, member_code, project_id, share_id) VALUES (?, ?, ?, ?, ?)");
                    $startingNumber = 1;

                    for ($i = 0; $i < $samity_share; $i++) {
                        $currentShareNumber = $startingNumber + $i;
                        $share_id = 'samity' . $member_id . $member_project_id . 1 . str_pad($currentShareNumber, 3, '0', STR_PAD_LEFT);
                        $stmtInsert->execute([$member_project_id, $member_id, $member_code, 1, $share_id]);
                    }
                }
            }

            $pdo->commit();

            $_SESSION['success_msg'] = '✅ Samity Share Fee Payment Successfully..! (সফলভাবে সমিতি শেয়ার ফি পেমেন্ট করা হলো..!)';
            header('Location: ../account/payment_approval.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = '❌ Error: ' . $e->getMessage();
            header('Location: ../account/payment.php');
            exit;
        }
    }
    
    // ===================== PROJECT SHARE PAYMENT =====================
    elseif ($payment_method === 'Project Share' && $amount > 0 && $project_id > 1) {
        try {
            $pdo->beginTransaction();

            // Insert into member_payments table
            $stmt = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type, project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $member_code, $payment_method, $tran_type, $project_id, $payment_year, $bank_pay_date, $bank_trans_no, $trans_no, $serial_no, $amount, 'Project Share', date('Y-m-d'), $user_id, $pay_slip, 'I', $pay_mode, $remarks]);

            // Get Current Status of member_project for this member_id and project_id
            $stmtCheck = $pdo->prepare("SELECT id, paid_amount, share_amount, sundry_amount FROM member_project WHERE member_id = ? AND member_code = ? AND project_id = ? LIMIT 1");
            $stmtCheck->execute([$member_id, $member_code, $project_id]);
            $memberProject = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            $share_amount = $extra_share * 5000;
            $sundry_amt_value = $sundry_amt - $amount;

            if ($memberProjectDate == 0) {
                if ($extra_share > 0 && $amount > 0) {
                    $stmtInsertProject = $pdo->prepare("INSERT INTO member_project (member_id, member_code, project_id, project_share, share_amount, paid_amount, sundry_amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtInsertProject->execute([$member_id, $member_code, $project_id, $extra_share, $share_amount, $amount, $sundry_amt, date('Y-m-d')]);
                    $member_project_id = $pdo->lastInsertId();

                    // Insert into project_share table for each extra share
                    $stmtInsert = $pdo->prepare("INSERT INTO project_share (member_project_id, member_id, member_code, project_id, share_id) VALUES (?, ?, ?, ?, ?)");
                    $startingNumber = 1;

                    for ($j = 0; $j < $extra_share; $j++) {
                        $currentShareNumber = $startingNumber + $j;
                        $n = str_pad($currentShareNumber, 3, '0', STR_PAD_LEFT);
                        $share_id = "share{$member_id}{$member_project_id}{$project_id}{$n}";
                        $stmtInsert->execute([$member_project_id, $member_id, $member_code, $project_id, $share_id]);
                    }

                    $stmt = $pdo->prepare("UPDATE member_share SET extra_share = extra_share - ? WHERE member_id = ? AND member_code = ?");
                    $stmt->execute([$extra_share, $member_id, $member_code]);
                }
            }

            if ($memberProjectDate > 0) {
                $stmt = $pdo->prepare("UPDATE member_project SET paid_amount = paid_amount + ?, sundry_amount = ? WHERE member_id = ? AND member_code = ? AND project_id = ?");
                $stmt->execute([$amount, $sundry_amt_value, $member_id, $member_code, $project_id]);
            }

            $pdo->commit();

            $_SESSION['success_msg'] = '✅ Project Share Fee Payment Successfully..! (সফলভাবে প্রকল্প শেয়ার ফি পেমেন্ট করা হলো..!)';
            header('Location: ../account/payment_approval.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = '❌ Error: ' . $e->getMessage();
            header('Location: ../account/payment.php');
            exit;
        }
    }
    
    // ===================== MONTHLY/OTHER PAYMENTS =====================
    elseif ($payment_method != 'admission' && $payment_method != 'Samity Share' && $payment_method != 'Project Share' && $amount > 0) {
        try {
            $pdo->beginTransaction();

            // Calculate fees for monthly payments
        // Late fee is the difference between amount and monthly fee
        $late_fee = 0;
        if ($amount > $monthly_fee) {
            $late_fee = round($amount - $monthly_fee, 2);
        }
        
        $for_install = round($amount * 0.98, 2);
        $other_fee = round($amount * 0.02, 2);



        // Fees to insert
        $stmt = $pdo->prepare("INSERT INTO member_payments (member_id, member_code, payment_method, tran_type, project_id, payment_year, bank_pay_date, bank_trans_no, trans_no, serial_no, amount, for_fees, created_at, created_by, payment_slip, status, pay_mode, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$member_id, $member_code, $payment_method, $tran_type, $project_id, $payment_year, $bank_pay_date, $bank_trans_no, $trans_no, $serial_no, $amount, $payment_method, date('Y-m-d'), $user_id, $pay_slip, 'I', $pay_mode, $remarks]);

        // Update member_share table
        $stmt = $pdo->prepare("UPDATE member_share SET for_install = for_install + ?, other_fee = other_fee + ?, late_fee = late_fee + ? WHERE member_id = ? AND member_code = ?");
        $stmt->execute([$for_install, $other_fee, $late_fee, $member_id, $member_code]);

            $pdo->commit();

            $_SESSION['success_msg'] = '✅ সফলভাবে পেমেন্ট করা হয়েছে, অনুমোদনের জন্য অপেক্ষা করুন (Payment successful, please wait for approval)';
            header('Location: ../account/payment.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_msg'] = '❌ Error: ' . $e->getMessage();
            header('Location: ../account/payment.php');
            exit;
        }
    }
    
    // ===================== INVALID PAYMENT =====================
    else {
        $_SESSION['error_msg'] = '❌ Invalid payment type or amount.';
        header('Location: ../account/payment.php');
        exit;
    }
}
?>
