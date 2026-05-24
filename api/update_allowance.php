<?php
/**
 * Update Allowance API
 * 
 * Updates the user's monthly allowance.
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

    $monthly_allowance = floatval(str_replace(',', '.', sanitize($_POST['monthly_allowance'] ?? '0')));

    if (!validateAmount($monthly_allowance)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Allowance must be between 0 and 100,000'
        ]);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE users SET monthly_allowance = :allowance WHERE id = :user_id');
    $stmt->execute([':allowance' => $monthly_allowance, ':user_id' => $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Monthly allowance updated successfully'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update allowance'
    ]);
}
?>