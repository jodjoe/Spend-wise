<?php
/**
 * Update Profile API
 * 
 * Updates the user's name and email address.
 * Validates email uniqueness and returns success only for owned user.
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

    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Name and email are required'
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a valid email address'
        ]);
        exit;
    }

    // Check if email is already in use by another user
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :user_id');
    $stmt->execute([':email' => $email, ':user_id' => $user_id]);
    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'This email is already taken'
        ]);
        exit;
    }

    // Update user record
    $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email WHERE id = :user_id');
    $stmt->execute([':name' => $name, ':email' => $email, ':user_id' => $user_id]);

    $_SESSION['user_name'] = $name;

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update profile'
    ]);
}
?>