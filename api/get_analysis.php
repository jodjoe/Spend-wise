<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');
requireMethod('GET');

try {
    $pdo = getDB();
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare('SELECT monthly_allowance FROM users WHERE id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $user = $stmt->fetch();
    $allowance = $user ? floatval($user['monthly_allowance']) : 0;

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) as month_spent FROM expenses WHERE user_id = :user_id AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())');
    $stmt->execute([':user_id' => $user_id]);
    $month_spent = floatval($stmt->fetchColumn());

    $predicted_remaining = getEndOfMonthPrediction($user_id, $pdo);
    $pace_status = getPaceStatus($user_id, $pdo) ?? 'on_track';

    $week_days = [];
    for ($offset = 6; $offset >= 0; $offset--) {
        $date = date('Y-m-d', strtotime("-{$offset} days"));
        $week_days[$date] = [
            'label' => date('D', strtotime($date)),
            'amount' => 0.0
        ];
    }

    $stmt = $pdo->prepare('SELECT expense_date, COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = :user_id AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) GROUP BY expense_date ORDER BY expense_date ASC');
    $stmt->execute([':user_id' => $user_id]);
    while ($row = $stmt->fetch()) {
        $date = $row['expense_date'];
        if (isset($week_days[$date])) {
            $week_days[$date]['amount'] = floatval($row['total']);
        }
    }

    $weekly_spending = [];
    $max_weekly = 0;
    foreach ($week_days as $date => $value) {
        $weekly_spending[] = [
            'date' => $date,
            'label' => $value['label'],
            'amount' => $value['amount'],
            'amount_formatted' => number_format($value['amount'], 2) . ' ETB'
        ];
        $max_weekly = max($max_weekly, $value['amount']);
    }

    $stmt = $pdo->prepare('SELECT c.id, c.name, c.icon, COALESCE(SUM(e.amount), 0) as total FROM categories c LEFT JOIN expenses e ON e.category_id = c.id AND e.user_id = :user_id AND MONTH(e.expense_date) = MONTH(CURDATE()) AND YEAR(e.expense_date) = YEAR(CURDATE()) WHERE c.user_id = :user_id GROUP BY c.id ORDER BY total DESC');
    $stmt->execute([':user_id' => $user_id]);
    $category_breakdown = [];
    $total_category_spent = 0;
    while ($row = $stmt->fetch()) {
        $category_breakdown[] = [
            'id' => intval($row['id']),
            'name' => $row['name'],
            'icon' => $row['icon'],
            'amount' => floatval($row['total'])
        ];
        $total_category_spent += floatval($row['total']);
    }

    foreach ($category_breakdown as &$category) {
        $category['percentage'] = $total_category_spent > 0 ? round(($category['amount'] / $total_category_spent) * 100) : 0;
        $category['amount_formatted'] = number_format($category['amount'], 2) . ' ETB';
    }
    unset($category);

    $stmt = $pdo->prepare('SELECT b.id, b.category_id, b.amount, b.period, c.name as category_name, c.icon as category_icon FROM budgets b JOIN categories c ON b.category_id = c.id WHERE b.user_id = :user_id ORDER BY c.name ASC');
    $stmt->execute([':user_id' => $user_id]);

    $budget_usage = [];
    while ($budget = $stmt->fetch()) {
        if ($budget['period'] === 'weekly') {
            $periodStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = :user_id AND category_id = :category_id AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)');
        } else {
            $periodStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE user_id = :user_id AND category_id = :category_id AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())');
        }
        $periodStmt->execute([':user_id' => $user_id, ':category_id' => $budget['category_id']]);
        $spent = floatval($periodStmt->fetchColumn());
        $percentage = $budget['amount'] > 0 ? min(999, round(($spent / $budget['amount']) * 100)) : 0;
        $level = $percentage < 60 ? 'green' : ($percentage < 80 ? 'yellow' : 'red');
        $budget_usage[] = [
            'id' => intval($budget['id']),
            'category_name' => $budget['category_name'],
            'category_icon' => $budget['category_icon'],
            'amount' => floatval($budget['amount']),
            'amount_formatted' => number_format($budget['amount'], 2) . ' ETB',
            'period' => $budget['period'],
            'spent' => $spent,
            'spent_formatted' => number_format($spent, 2) . ' ETB',
            'percentage' => $percentage,
            'level' => $level,
            'remaining' => round($budget['amount'] - $spent, 2)
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'monthly_allowance' => round($allowance, 2),
            'month_spent' => round($month_spent, 2),
            'pace_status' => $pace_status,
            'predicted_remaining' => $predicted_remaining !== null ? round($predicted_remaining, 2) : null,
            'weekly_spending' => $weekly_spending,
            'max_weekly' => $max_weekly,
            'category_breakdown' => $category_breakdown,
            'total_category_spent' => round($total_category_spent, 2),
            'budget_usage' => $budget_usage
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load analysis.']);
}
?>
