<?php
/**
 * Change Password API
 * 
 * Verifies current password and updates to a new hashed password.
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');
requireMethod('POST');

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    jsonResponse(false, [], 'Invalid request', 403);
}

try {
    $pdo = getDB();
    $user_id = $_SESSION['user_id'];

    $current_password = sanitize($_POST['current_password'] ?? '');
    $new_password = sanitize($_POST['new_password'] ?? '');
    $confirm_password = sanitize($_POST['confirm_password'] ?? '');

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'All password fields are required'
        ]);
        exit;
    }

    if ($new_password !== $confirm_password) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'New passwords do not match'
        ]);
        exit;
    }

    if (strlen($new_password) < 8 || !preg_match('/[0-9]/', $new_password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 8 characters and include a number'
        ]);
        exit;
    }

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($current_password, $user['password'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Current password is incorrect'
        ]);
        exit;
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password = :new_password WHERE id = :user_id');
    $stmt->execute([':new_password' => $new_hash, ':user_id' => $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Password changed successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to change password'
    ]);
}
?>