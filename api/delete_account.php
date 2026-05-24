<?php
/**
 * Delete Account API
 * 
 * Deletes the current user account after verifying password.
 * Cascades delete through related data using foreign key constraints.
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
    $password = sanitize($_POST['password'] ?? '');

    if (empty($password)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Password is required to delete account'
        ]);
        exit;
    }

    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Password is incorrect'
        ]);
        exit;
    }

    // Delete user and related data
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = :user_id');
    $stmt->execute([':user_id' => $user_id]);

    // Destroy session
    session_unset();
    session_destroy();

    echo json_encode([
        'success' => true,
        'message' => 'Your account has been deleted'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete account'
    ]);
}
?>