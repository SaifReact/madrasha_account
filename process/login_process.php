<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $_SESSION['error_msg'] = '❌ Username and password are required.';
        header('Location: ../login.php');
        exit;
    }

    // Hash password (using md5 as per your existing system)
    $password_hash = md5($password);

    $stmt = $pdo->prepare("
        SELECT id, user_name, role, member_id, member_code, re_password, status
        FROM user_login
        WHERE user_name = ? AND password = ? AND status IN ('P','A')
        LIMIT 1
    ");
    $stmt->execute([$username, $password_hash]);
    $user = $stmt->fetch();

    if ($user) {
        // Store session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['user_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['member_id'] = $user['member_id'];
        $_SESSION['member_code'] = $user['member_code'];
        $_SESSION['re_password'] = $user['re_password'];
        $_SESSION['status'] = $user['status'];

        // ✅ Add success message
       $_SESSION['success_msg'] = '✅ আপনি সিস্টেম এ লগইন করার জন্য, স্বাগতম, ' . htmlspecialchars($user['user_name']) . '.';

        // Log user access (same for all roles)
        $logStmt = $pdo->prepare("
            INSERT INTO user_access (user_id, member_id, login)
            VALUES (?, ?, NOW())
        ");
        $logStmt->execute([$user['id'], $user['member_id']]);

        // Redirect based on role
        if ($user['role'] === 'Admin') {
            header('Location: ../admin/index.php');
        } elseif ($user['role'] === 'Account') {
            header('Location: ../account/index.php');
        } elseif ($user['role'] === 'user') {
            header('Location: ../users/index.php');
        } else {
            $_SESSION['error_msg'] = '❌ Unauthorized role.';
            header('Location: ../login.php');
        }
        exit;

    } else {
        $_SESSION['error_msg'] = '❌ আপনার ইউজার ও পাসওয়ার্ড একটিভ হয়নি। (Your username and password have not been activated.)';
        header('Location: ../login.php');
        exit;
    }

} else {
    header('Location: ../login.php');
    exit;
}
