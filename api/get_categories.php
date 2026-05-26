<?php
/**
 * Get Categories API
 * 
 * Fetches all categories for the current user
 * Ordered by is_default descending, then name ascending
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

    // Fetch all categories for this user with month spending and budget
    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.icon, c.is_default,
               COALESCE(SUM(e.amount), 0) AS month_spent,
               COALESCE(b.amount, 0) AS budget_amount
        FROM categories c
        LEFT JOIN expenses e
            ON e.category_id = c.id
            AND e.user_id = c.user_id
            AND MONTH(e.expense_date) = MONTH(CURDATE())
            AND YEAR(e.expense_date)  = YEAR(CURDATE())
        LEFT JOIN budgets b
            ON b.category_id = c.id
            AND b.user_id = c.user_id
            AND b.period = \'monthly\'
        WHERE c.user_id = :user_id
        GROUP BY c.id
        ORDER BY c.is_default DESC, c.name ASC
    ');
    $stmt->execute([':user_id' => $user_id]);
    $categories = $stmt->fetchAll();

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch categories'
    ]);
}
?>
