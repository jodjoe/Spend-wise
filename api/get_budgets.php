<?php
/**
 * Get Budgets API
 * 
 * Fetches all budgets for the user with calculated spent and percentage
 * Returns budgets with:
 * - spent: Total spent in this period
 * - percentage: Percentage used (capped at 999)
 * - level: Color level (green/yellow/red based on percentage)
 * - remaining: Amount remaining in budget
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');
requireMethod('GET');

try {
    $pdo = getDB();
    $user_id = $_SESSION['user_id'];

    // Fetch all budgets for this user with categories
    $stmt = $pdo->prepare('
        SELECT b.id, b.category_id, b.amount, b.period,
               c.name as category_name, c.icon as category_icon
        FROM budgets b
        JOIN categories c ON b.category_id = c.id
        WHERE b.user_id = :user_id
        ORDER BY c.name ASC
    ');
    $stmt->execute([':user_id' => $user_id]);
    $budgets = $stmt->fetchAll();

    // Calculate spent for each budget and format response
    $formatted_budgets = [];
    foreach ($budgets as $budget) {
        $category_id = $budget['category_id'];
        $period = $budget['period'];

        // Calculate spent in this period
        if ($period === 'weekly') {
            // Last 7 days
            $stmt = $pdo->prepare('
                SELECT COALESCE(SUM(amount), 0) as total
                FROM expenses
                WHERE user_id = :user_id AND category_id = :category_id
                AND expense_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ');
        } else {
            // This month
            $stmt = $pdo->prepare('
                SELECT COALESCE(SUM(amount), 0) as total
                FROM expenses
                WHERE user_id = :user_id AND category_id = :category_id
                AND MONTH(expense_date) = MONTH(NOW())
                AND YEAR(expense_date) = YEAR(NOW())
            ');
        }

        $stmt->execute([':user_id' => $user_id, ':category_id' => $category_id]);
        $spent = floatval($stmt->fetch()['total']);

        // Calculate percentage and level
        $percentage = $budget['amount'] > 0 ? round(($spent / $budget['amount']) * 100) : 0;
        $percentage_capped = min($percentage, 999);

        if ($percentage < 60) {
            $level = 'green';
        } elseif ($percentage < 80) {
            $level = 'yellow';
        } else {
            $level = 'red';
        }

        $remaining = $budget['amount'] - $spent;

        $formatted_budgets[] = [
            'id' => intval($budget['id']),
            'category_id' => intval($budget['category_id']),
            'category_name' => $budget['category_name'],
            'category_icon' => $budget['category_icon'],
            'amount' => floatval($budget['amount']),
            'amount_formatted' => number_format($budget['amount'], 2) . ' ETB',
            'period' => $budget['period'],
            'spent' => $spent,
            'spent_formatted' => number_format($spent, 2) . ' ETB',
            'percentage' => $percentage_capped,
            'level' => $level,
            'remaining' => $remaining,
            'remaining_formatted' => number_format($remaining, 2) . ' ETB'
        ];
    }

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $formatted_budgets,
        'count' => count($formatted_budgets)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch budgets'
    ]);
}
?>
