<?php
// Helper functions for user_access table
function log_user_access($pdo, $user_id, $member_id) {
    $stmt = $pdo->prepare("INSERT INTO user_access (user_id, member_id, login, created_at) VALUES (?, ?, NOW(), NOW())");
    $stmt->execute([$user_id, $member_id]);
    return $pdo->lastInsertId();
}

function update_user_logout($pdo, $user_id, $member_id) {
    $stmt = $pdo->prepare("UPDATE user_access SET logout = NOW() WHERE user_id = ? AND member_id = ? AND logout IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id, $member_id]);
}
