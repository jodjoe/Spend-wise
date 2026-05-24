<?php
/**
 * Add Budget API
 * 
 * Creates a new budget
 * Validates no duplicate category+period combination exists
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
    $category_id = intval(sanitize($_POST['category_id'] ?? '0'));
    $amount = floatval(str_replace(',', '.', sanitize($_POST['amount'] ?? '0')));
    $period = sanitize($_POST['period'] ?? 'monthly');

    // Validation
    if ($category_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please select a category',
            'field' => 'category_id'
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

    // Check for duplicate budget
    $stmt = $pdo->prepare('
        SELECT id FROM budgets 
        WHERE user_id = :user_id AND category_id = :category_id AND period = :period
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':category_id' => $category_id,
        ':period' => $period
    ]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Budget already exists for this category and period',
            'field' => 'category_id'
        ]);
        exit;
    }

    // Insert budget
    $stmt = $pdo->prepare('
        INSERT INTO budgets (user_id, category_id, amount, period)
        VALUES (:user_id, :category_id, :amount, :period)
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':category_id' => $category_id,
        ':amount' => $amount,
        ':period' => $period
    ]);

    $budget_id = $pdo->lastInsertId();

    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Budget created',
        'data' => [
            'id' => (int)$budget_id,
            'category_id' => $category_id,
            'amount' => $amount,
            'period' => $period
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create budget'
    ]);
}
?>
