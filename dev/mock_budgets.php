<?php
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }
header('Content-Type: application/json');

echo json_encode([
  'success' => true,
  'data' => [
    ['id'=>1,'category_id'=>1,'category_name'=>'Food',          'category_icon'=>'🍛','amount'=>1000,'amount_formatted'=>'1,000.00 ETB','period'=>'monthly','spent'=>780,  'spent_formatted'=>'780.00 ETB', 'percentage'=>78, 'level'=>'yellow','remaining'=>220,  'remaining_formatted'=>'220.00 ETB'],
    ['id'=>2,'category_id'=>2,'category_name'=>'Transport',     'category_icon'=>'🚌','amount'=>500, 'amount_formatted'=>'500.00 ETB',  'period'=>'monthly','spent'=>420,  'spent_formatted'=>'420.00 ETB', 'percentage'=>84, 'level'=>'red',   'remaining'=>80,   'remaining_formatted'=>'80.00 ETB'],
    ['id'=>3,'category_id'=>4,'category_name'=>'School Supplies','category_icon'=>'📚','amount'=>400, 'amount_formatted'=>'400.00 ETB',  'period'=>'monthly','spent'=>310,  'spent_formatted'=>'310.00 ETB', 'percentage'=>78, 'level'=>'yellow','remaining'=>90,   'remaining_formatted'=>'90.00 ETB'],
    ['id'=>4,'category_id'=>5,'category_name'=>'Mobile Top-up', 'category_icon'=>'📱','amount'=>200, 'amount_formatted'=>'200.00 ETB',  'period'=>'monthly','spent'=>200,  'spent_formatted'=>'200.00 ETB', 'percentage'=>100,'level'=>'red',   'remaining'=>0,    'remaining_formatted'=>'0.00 ETB'],
    ['id'=>5,'category_id'=>6,'category_name'=>'Café',          'category_icon'=>'☕','amount'=>300, 'amount_formatted'=>'300.00 ETB',  'period'=>'monthly','spent'=>135,  'spent_formatted'=>'135.00 ETB', 'percentage'=>45, 'level'=>'green', 'remaining'=>165,  'remaining_formatted'=>'165.00 ETB'],
    ['id'=>6,'category_id'=>10,'category_name'=>'Gym',          'category_icon'=>'🏋️','amount'=>200, 'amount_formatted'=>'200.00 ETB',  'period'=>'weekly', 'spent'=>150,  'spent_formatted'=>'150.00 ETB', 'percentage'=>75, 'level'=>'yellow','remaining'=>50,   'remaining_formatted'=>'50.00 ETB'],
  ]
]);
