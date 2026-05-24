<?php
/**
 * Delete Expense API
 * 
 * Deletes an expense
 * Verifies ownership before deleting
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

    // Get POST data (sanitized)
    $id = intval(sanitize($_POST['id'] ?? '0'));

    // Validation
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid expense ID'
        ]);
        exit;
    }

    // Verify expense belongs to user
    $stmt = $pdo->prepare('
        SELECT id FROM expenses 
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Expense not found'
        ]);
        exit;
    }

    // Delete expense
    $stmt = $pdo->prepare('
        DELETE FROM expenses 
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Expense deleted'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete expense'
    ]);
}
?>
