<?php
// ── Mock get_analytics — works without DB for UI preview ──
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'data' => [
        'monthly_allowance' => 3000.00,
        'month_spent'       => 1845.50,
        'month_remaining'   => 1154.50,
        'today_remaining'   => 42.30,
        'spent_percent'     => 62,
        'pace_status'       => 'warning',
        'predicted_remaining' => '320.00 ETB',
        'top_categories'    => [
            ['id'=>1, 'icon'=>'🍛', 'name'=>'Food',          'spent'=>780.00, 'spent_formatted'=>'780.00 ETB'],
            ['id'=>2, 'icon'=>'🚌', 'name'=>'Transport',     'spent'=>420.00, 'spent_formatted'=>'420.00 ETB'],
            ['id'=>3, 'icon'=>'📚', 'name'=>'School Supplies','spent'=>310.50,'spent_formatted'=>'310.50 ETB'],
            ['id'=>4, 'icon'=>'📱', 'name'=>'Mobile Top-up', 'spent'=>200.00, 'spent_formatted'=>'200.00 ETB'],
            ['id'=>5, 'icon'=>'☕', 'name'=>'Café',           'spent'=>135.00, 'spent_formatted'=>'135.00 ETB'],
        ],
        'recent_expenses'   => [
            ['id'=>1,'amount'=>45.00,'amount_formatted'=>'45.00 ETB','note'=>'Lunch at canteen',   'expense_date'=>'2026-05-26','expense_date_formatted'=>'26 May 2026','category_name'=>'Food',          'category_icon'=>'🍛'],
            ['id'=>2,'amount'=>20.00,'amount_formatted'=>'20.00 ETB','note'=>'Bus to campus',      'expense_date'=>'2026-05-26','expense_date_formatted'=>'26 May 2026','category_name'=>'Transport',     'category_icon'=>'🚌'],
            ['id'=>3,'amount'=>150.00,'amount_formatted'=>'150.00 ETB','note'=>'Textbook',         'expense_date'=>'2026-05-25','expense_date_formatted'=>'25 May 2026','category_name'=>'School Supplies','category_icon'=>'📚'],
            ['id'=>4,'amount'=>50.00,'amount_formatted'=>'50.00 ETB','note'=>'Ethio Telecom',      'expense_date'=>'2026-05-25','expense_date_formatted'=>'25 May 2026','category_name'=>'Mobile Top-up', 'category_icon'=>'📱'],
            ['id'=>5,'amount'=>35.00,'amount_formatted'=>'35.00 ETB','note'=>'Coffee with friends','expense_date'=>'2026-05-24','expense_date_formatted'=>'24 May 2026','category_name'=>'Café',           'category_icon'=>'☕'],
        ]
    ]
]);
