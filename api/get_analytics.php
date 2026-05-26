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

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) as total_spent FROM expenses WHERE user_id = :user_id AND MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())');
    $stmt->execute([':user_id' => $user_id]);
    $month_spent = floatval($stmt->fetchColumn());

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) as today_spent FROM expenses WHERE user_id = :user_id AND expense_date = CURDATE()');
    $stmt->execute([':user_id' => $user_id]);
    $today_spent = floatval($stmt->fetchColumn());

    $days_in_month = intval(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')));
    $daily_limit = $allowance > 0 ? $allowance / $days_in_month : 0;
    $remaining_month = max(0, round($allowance - $month_spent, 2));
    $remaining_today = max(0, round($daily_limit - $today_spent, 2));
    $month_percent = $allowance > 0 ? min(100, round(($month_spent / $allowance) * 100)) : 0;
    $pace_status = getPaceStatus($user_id, $pdo) ?? 'on_track';
    $predicted_remaining = getEndOfMonthPrediction($user_id, $pdo);
    $predicted_remaining = $predicted_remaining !== null ? number_format($predicted_remaining, 2) . ' ETB' : 'N/A';

    $stmt = $pdo->prepare('SELECT c.id, c.icon, c.name, COALESCE(SUM(e.amount), 0) as spent FROM categories c LEFT JOIN expenses e ON e.category_id = c.id AND e.user_id = :user_id AND MONTH(e.expense_date) = MONTH(CURDATE()) AND YEAR(e.expense_date) = YEAR(CURDATE()) WHERE c.user_id = :user_id GROUP BY c.id ORDER BY spent DESC LIMIT 6');
    $stmt->execute([':user_id' => $user_id]);
    $top_categories = [];

    while ($row = $stmt->fetch()) {
        if (floatval($row['spent']) <= 0) {
            continue;
        }
        $top_categories[] = [
            'id' => intval($row['id']),
            'icon' => $row['icon'],
            'name' => $row['name'],
            'spent' => floatval($row['spent']),
            'spent_formatted' => number_format($row['spent'], 2) . ' ETB'
        ];
    }

    $stmt = $pdo->prepare('SELECT e.id, e.amount, e.note, e.expense_date, c.name as category_name, c.icon as category_icon FROM expenses e LEFT JOIN categories c ON e.category_id = c.id AND c.user_id = e.user_id WHERE e.user_id = :user_id ORDER BY e.expense_date DESC, e.created_at DESC LIMIT 5');
    $stmt->execute([':user_id' => $user_id]);
    $recent_expenses = [];
    while ($expense = $stmt->fetch()) {
        $recent_expenses[] = [
            'id' => intval($expense['id']),
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
        'data' => [
            'monthly_allowance' => round($allowance, 2),
            'month_spent' => round($month_spent, 2),
            'month_remaining' => $remaining_month,
            'today_remaining' => $remaining_today,
            'spent_percent' => $month_percent,
            'pace_status' => $pace_status,
            'predicted_remaining' => $predicted_remaining,
            'top_categories' => $top_categories,
            'recent_expenses' => $recent_expenses
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to load analytics.']);
}
?>
