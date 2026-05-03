<?php
session_start();
include_once __DIR__ . '/../config/config.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['member_id'])) {
    $stmt = $pdo->prepare("
        UPDATE user_access 
        SET logout = NOW() 
        WHERE user_id = ? AND member_id = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['member_id']]);
}

// ✅ Store success message before destroying session
$success_msg = '✅ আপনি সিস্টেম থেকে লগআউট হয়েছেন';

// Clear session
session_unset();
session_destroy();

// Start a new session to carry message forward
session_start();
$_SESSION['success_msg'] = $success_msg;

header('Location: ../index.php');
exit;
