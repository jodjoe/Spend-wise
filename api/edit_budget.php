<?php
/**
 * Edit Budget API
 * 
 * Updates an existing budget record
 * Verifies ownership before updating
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
    $amount = floatval(str_replace(',', '.', sanitize($_POST['amount'] ?? '0')));
    $period = sanitize($_POST['period'] ?? 'monthly');

    // Validation
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid budget ID'
        ]);
        exit;
    }

    if (!validateAmount($amount)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Amount must be between 0 and 100,000',
            'field' => 'amount'
        ]);
        exit;
    }

    if (!in_array($period, ['weekly', 'monthly'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Period must be weekly or monthly',
            'field' => 'period'
        ]);
        exit;
    }

    // Verify budget belongs to user
    $stmt = $pdo->prepare('
        SELECT id, category_id FROM budgets
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    $budget = $stmt->fetch();

    if (!$budget) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Budget not found'
        ]);
        exit;
    }

    // Check duplicate for same category+period except current record
    $stmt = $pdo->prepare('
        SELECT id FROM budgets
        WHERE user_id = :user_id AND category_id = :category_id AND period = :period AND id != :id
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':category_id' => $budget['category_id'],
        ':period' => $period,
        ':id' => $id
    ]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Another budget already exists for this category and period'
        ]);
        exit;
    }

    // Update budget
    $stmt = $pdo->prepare('
        UPDATE budgets
        SET amount = :amount, period = :period
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([
        ':amount' => $amount,
        ':period' => $period,
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Budget updated'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update budget'
    ]);
}
?>
