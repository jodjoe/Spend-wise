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

    if ($id <= 0) {
        jsonResponse(false, [], 'Invalid expense ID', 400);
    }

    if (!validateAmount($amount)) {
        jsonResponse(false, [], 'Amount must be between 0 and 100,000', 400);
    }

    if ($category_id <= 0) {
        jsonResponse(false, [], 'Please select a category', 400);
    }

    // Verify expense belongs to user
    $stmt = $pdo->prepare('SELECT id FROM expenses WHERE id = :id AND user_id = :user_id');
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    if (!$stmt->fetch()) {
        jsonResponse(false, [], 'Expense not found', 403);
    }

    // Verify category belongs to user
    $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = :category_id AND user_id = :user_id');
    $stmt->execute([':category_id' => $category_id, ':user_id' => $user_id]);
    if (!$stmt->fetch()) {
        jsonResponse(false, [], 'Invalid category', 403);
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

    jsonResponse(true, [], 'Expense updated');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update expense']);
}
?>
