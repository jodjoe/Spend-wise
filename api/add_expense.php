<?php
/**
 * Add Expense API
 * 
 * Creates a new expense and returns popup data with:
 * - remaining_today: Daily limit remaining after expense
 * - remaining_month: Monthly allowance remaining after expense
 * - budget_alerts: Any budget warnings for affected category
 * - overall_status: Pace status (on_track/warning/critical)
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
    $amount = floatval(str_replace(',', '.', sanitize($_POST['amount'] ?? '0')));
    $category_id = intval(sanitize($_POST['category_id'] ?? '0'));
    $note = sanitize($_POST['note'] ?? '');
    $expense_date = sanitize($_POST['expense_date'] ?? date('Y-m-d'));

    // Validation - use helper function
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

    // Verify expense_date is valid
    if (strtotime($expense_date) === false) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid expense date',
            'field' => 'expense_date'
        ]);
        exit;
    }

    // Insert expense
    $stmt = $pdo->prepare('
        INSERT INTO expenses (user_id, category_id, amount, note, expense_date, created_at)
        VALUES (:user_id, :category_id, :amount, :note, :expense_date, NOW())
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':category_id' => $category_id,
        ':amount' => $amount,
        ':note' => $note,
        ':expense_date' => $expense_date
    ]);

    // Build popup response with all required data
    $stmt = $pdo->prepare('SELECT name FROM categories WHERE id = :category_id AND user_id = :user_id');
    $stmt->execute([':category_id' => $category_id, ':user_id' => $user_id]);
    $category = $stmt->fetch();
    $category_name = $category ? $category['name'] : 'Expense';

    $remaining_today = getRemainingToday($user_id, $pdo) ?? 0;
    $remaining_month = getRemainingMonth($user_id, $pdo) ?? 0;
    $pace_status = getPaceStatus($user_id, $pdo) ?? 'on_track';

    $stmt = $pdo->prepare('
        SELECT id, amount, period FROM budgets
        WHERE user_id = :user_id AND category_id = :category_id AND period = :period
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':category_id' => $category_id,
        ':period' => 'monthly'
    ]);
    $budget = $stmt->fetch();

    $budget_alerts = [];
    if ($budget) {
        $percent_used = getBudgetUsage($user_id, $category_id, 'monthly', $pdo);
        if ($percent_used === null) {
            $percent_used = 0;
        }

        $level = 'success';
        if ($percent_used >= 80) {
            $level = 'danger';
        } elseif ($percent_used >= 60) {
            $level = 'warning';
        }

        $budget_alerts[] = [
            'category' => $category_name,
            'percent_used' => intval($percent_used),
            'level' => $level
        ];
    }

    $popupData = [
        'expense_label' => number_format($amount, 2) . ' ETB on ' . $category_name,
        'remaining_today' => round($remaining_today, 2),
        'remaining_month' => round($remaining_month, 2),
        'budget_alerts' => $budget_alerts,
        'overall_status' => $pace_status
    ];

    jsonResponse(true, ['popup' => $popupData], 'Expense saved');
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to record expense'
    ]);
}
?>
