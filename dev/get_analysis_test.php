<?php
require_once '../includes/session.php';
header('Content-Type: application/json');
// Simple sample analysis payload for local testing
$sample = [
    'monthly_allowance' => 3000.00,
    'month_spent' => 1250.50,
    'pace_status' => 'warning',
    'predicted_remaining' => 950.25,
    'weekly_spending' => [
        ['date'=>'2026-05-18','label'=>'Mon','amount'=>120.00,'amount_formatted'=>'120.00 ETB'],
        ['date'=>'2026-05-19','label'=>'Tue','amount'=>80.00,'amount_formatted'=>'80.00 ETB'],
        ['date'=>'2026-05-20','label'=>'Wed','amount'=>210.50,'amount_formatted'=>'210.50 ETB'],
        ['date'=>'2026-05-21','label'=>'Thu','amount'=>0.00,'amount_formatted'=>'0.00 ETB'],
        ['date'=>'2026-05-22','label'=>'Fri','amount'=>150.00,'amount_formatted'=>'150.00 ETB'],
        ['date'=>'2026-05-23','label'=>'Sat','amount'=>0.00,'amount_formatted'=>'0.00 ETB'],
        ['date'=>'2026-05-24','label'=>'Sun','amount'=>90.00,'amount_formatted'=>'90.00 ETB'],
    ],
    'max_weekly' => 210.50,
    'category_breakdown' => [
        ['id'=>1,'name'=>'Food','icon'=>'🍛','amount'=>600.50,'percentage'=>48,'amount_formatted'=>'600.50 ETB'],
        ['id'=>6,'name'=>'Café','icon'=>'☕','amount'=>300.00,'percentage'=>24,'amount_formatted'=>'300.00 ETB'],
        ['id'=>2,'name'=>'Transport','icon'=>'🚌','amount'=>200.00,'percentage'=>16,'amount_formatted'=>'200.00 ETB'],
        ['id'=>3,'name'=>'Clothing','icon'=>'👕','amount'=>150.00,'percentage'=>12,'amount_formatted'=>'150.00 ETB']
    ],
    'total_category_spent' => 1250.50,
    'budget_usage' => [
        ['id'=>1,'category_name'=>'Food','category_icon'=>'🍛','amount'=>800.00,'amount_formatted'=>'800.00 ETB','period'=>'monthly','spent'=>600.50,'spent_formatted'=>'600.50 ETB','percentage'=>75,'level'=>'yellow','remaining'=>199.50]
    ]
];

echo json_encode(['success'=>true,'data'=>$sample]);
?>