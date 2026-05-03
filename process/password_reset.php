<?php
include_once __DIR__ . '/../config/config.php';
session_start();

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in.');
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT user_name, password, member_id, member_code, re_password FROM user_login WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('User not found.');
    }

    // Validate POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $previouspassword = $_POST['previous_password'] ?? '';
    $newpassword = $_POST['password'] ?? '';
    $retypepassword = $_POST['retype_password'] ?? '';

    if ($newpassword === '' || $retypepassword === '') {
        throw new Exception('Please fill all fields.');
    }
    if ($newpassword !== $retypepassword) {
        throw new Exception('New passwords do not match.');
    }
    if (strlen($newpassword) < 6) {
        throw new Exception('Password must be at least 6 characters long.');
    }
    if ($previouspassword === $newpassword) {
        throw new Exception('Previous password cannot be the same as the new password.');
    }

    $md5pass = md5($newpassword);

    // Update password
    $stmtUpdate = $pdo->prepare("UPDATE user_login SET password = ?, re_password = ? WHERE id = ? AND member_id = ? AND member_code = ?");
    $stmtUpdate->execute([$md5pass, $retypepassword, $user_id, $user['member_id'], $user['member_code']]);

    // Update session variable for re_password
    $_SESSION['re_password'] = $retypepassword;

    // Set success message in session
    $_SESSION['success_msg'] = '✅ আপনার পাসওয়ার্ডটি হালনাগাদ করা হয়েছে (Your password updated successfully)';
    header('Location: ../users/password.php'); // Redirect to the profile page or any other page
    exit;
} catch (Exception $e) {
    // Set error message in session
    $_SESSION['error_msg'] = '❌ ' . $e->getMessage();
    header('Location: ../users/password_reset.php'); // Redirect back to the password reset page
    exit;
}