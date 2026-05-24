<?php
/**
 * Edit Expense API
 * 
 * Updates an expense
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
    $category_id = intval(sanitize($_POST['category_id'] ?? '0'));
    $note = sanitize($_POST['note'] ?? '');
    $expense_date = sanitize($_POST['expense_date'] ?? date('Y-m-d'));

    // Validation
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid expense ID'
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

    if ($category_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please select a category',
            'field' => 'category_id'
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

    // Verify category belongs to user
    $stmt = $pdo->prepare('
        SELECT id FROM categories 
        WHERE id = :category_id AND user_id = :user_id
    ');
    $stmt->execute([':category_id' => $category_id, ':user_id' => $user_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid category'
        ]);
        exit;
    }

    // Update expense
    $stmt = $pdo->prepare('
        UPDATE expenses 
        SET amount = :amount, category_id = :category_id, note = :note, expense_date = :expense_date
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([
        ':amount' => $amount,
        ':category_id' => $category_id,
        ':note' => $note,
        ':expense_date' => $expense_date,
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Expense updated'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update expense'
    ]);
}
?>
