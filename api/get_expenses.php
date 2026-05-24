<?php
/**
 * Get Expenses API
 * 
 * Fetches expenses with dynamic filtering
 * Supports: search, category_id, date range, amount range, month, year
 * Returns expenses with category_name and icon joined
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

    // Get query parameters
    $search = sanitize($_GET['search'] ?? '');
    $category_id = intval($_GET['category_id'] ?? 0);
    $date_from = sanitize($_GET['date_from'] ?? '');
    $date_to = sanitize($_GET['date_to'] ?? '');
    $amount_min = floatval($_GET['amount_min'] ?? 0);
    $amount_max = floatval($_GET['amount_max'] ?? 1000000);
    $month = intval($_GET['month'] ?? date('m'));
    $year = intval($_GET['year'] ?? date('Y'));

    // Build dynamic WHERE clause
    $where_clauses = ['e.user_id = :user_id'];
    $params = [':user_id' => $user_id];

    // Search filter (note field)
    if (!empty($search)) {
        $where_clauses[] = 'e.note LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    // Category filter
    if ($category_id > 0) {
        $where_clauses[] = 'e.category_id = :category_id';
        $params[':category_id'] = $category_id;
    }

    // Date range filters
    if (!empty($date_from)) {
        $where_clauses[] = 'e.expense_date >= :date_from';
        $params[':date_from'] = $date_from;
    }

    if (!empty($date_to)) {
        $where_clauses[] = 'e.expense_date <= :date_to';
        $params[':date_to'] = $date_to;
    }

    // Month/Year filter (if no specific date range)
    if (empty($date_from) && empty($date_to)) {
        $where_clauses[] = 'MONTH(e.expense_date) = :month AND YEAR(e.expense_date) = :year';
        $params[':month'] = $month;
        $params[':year'] = $year;
    }

    // Amount range filters
    if ($amount_min > 0) {
        $where_clauses[] = 'e.amount >= :amount_min';
        $params[':amount_min'] = $amount_min;
    }

    if ($amount_max < 1000000) {
        $where_clauses[] = 'e.amount <= :amount_max';
        $params[':amount_max'] = $amount_max;
    }

    // Build query
    $where_string = implode(' AND ', $where_clauses);

    $query = "
        SELECT 
            e.id, e.category_id, e.amount, e.note, e.expense_date,
            c.name as category_name, c.icon as category_icon
        FROM expenses e
        JOIN categories c ON e.category_id = c.id
        WHERE $where_string
        ORDER BY e.expense_date DESC, e.created_at DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll();

    // Format response
    $formatted_expenses = [];
    foreach ($expenses as $expense) {
        $formatted_expenses[] = [
            'id' => intval($expense['id']),
            'category_id' => intval($expense['category_id']),
            'amount' => floatval($expense['amount']),
            'amount_formatted' => number_format($expense['amount'], 2) . ' ETB',
            'note' => $expense['note'],
            'expense_date' => $expense['expense_date'],
            'expense_date_formatted' => date('d M Y', strtotime($expense['expense_date'])),
            'category_name' => $expense['category_name'],
            'category_icon' => $expense['category_icon']
        ];
    }

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $formatted_expenses,
        'count' => count($formatted_expenses)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch expenses'
    ]);
}
?>
