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

    // Build parameterized WHERE clause — no user input is ever interpolated into SQL
    $where_clauses = ['e.user_id = :user_id'];
    $params = [':user_id' => $user_id];

    // Search filter — value bound via PDO, never interpolated
    if ($search !== '') {
        $where_clauses[] = 'e.note LIKE :search';
        $params[':search'] = '%' . $search . '%';
    }

    // Category filter — cast to int, safe
    if ($category_id > 0) {
        $where_clauses[] = 'e.category_id = :category_id';
        $params[':category_id'] = $category_id;
    }

    // Date range — values bound via PDO
    if ($date_from !== '') {
        // Validate date format before binding
        $date_from = date('Y-m-d', strtotime($date_from)) ?: '';
        if ($date_from) {
            $where_clauses[] = 'e.expense_date >= :date_from';
            $params[':date_from'] = $date_from;
        }
    }

    if ($date_to !== '') {
        $date_to = date('Y-m-d', strtotime($date_to)) ?: '';
        if ($date_to) {
            $where_clauses[] = 'e.expense_date <= :date_to';
            $params[':date_to'] = $date_to;
        }
    }

    // Month/Year filter only when no explicit date range given
    if (empty($params[':date_from']) && empty($params[':date_to'])) {
        // month and year are cast to int — safe
        $where_clauses[] = 'MONTH(e.expense_date) = :month AND YEAR(e.expense_date) = :year';
        $params[':month'] = $month;
        $params[':year'] = $year;
    }

    // Amount range — cast to float, safe
    if ($amount_min > 0) {
        $where_clauses[] = 'e.amount >= :amount_min';
        $params[':amount_min'] = $amount_min;
    }

    if ($amount_max < 1000000) {
        $where_clauses[] = 'e.amount <= :amount_max';
        $params[':amount_max'] = $amount_max;
    }

    // All clauses are static strings; only values come from user input via bound params
    $sql = 'SELECT e.id, e.category_id, e.amount, e.note, e.expense_date,
                c.name AS category_name, c.icon AS category_icon
            FROM expenses e
            LEFT JOIN categories c ON e.category_id = c.id AND c.user_id = e.user_id
            WHERE ' . implode(' AND ', $where_clauses) . '
            ORDER BY e.expense_date DESC, e.created_at DESC';

    $stmt = $pdo->prepare($sql);
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
            'category_name' => $expense['category_name'] ?? 'Uncategorized',
            'category_icon' => $expense['category_icon'] ?? '📦'
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $formatted_expenses,
        'count'   => count($formatted_expenses)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch expenses']);
}
?>
