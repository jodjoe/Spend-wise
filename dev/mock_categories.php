<?php
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }
header('Content-Type: application/json');

echo json_encode([
  'success' => true,
  'data' => [
    ['id'=>1,'name'=>'Food',           'icon'=>'🍛','is_default'=>1,'month_spent'=>780,'budget_amount'=>1000],
    ['id'=>2,'name'=>'Transport',      'icon'=>'🚌','is_default'=>1,'month_spent'=>420,'budget_amount'=>500],
    ['id'=>3,'name'=>'Clothing',       'icon'=>'👕','is_default'=>1,'month_spent'=>0,  'budget_amount'=>0],
    ['id'=>4,'name'=>'School Supplies','icon'=>'📚','is_default'=>1,'month_spent'=>310,'budget_amount'=>400],
    ['id'=>5,'name'=>'Mobile Top-up',  'icon'=>'📱','is_default'=>1,'month_spent'=>200,'budget_amount'=>200],
    ['id'=>6,'name'=>'Café',           'icon'=>'☕','is_default'=>1,'month_spent'=>135,'budget_amount'=>300],
    ['id'=>7,'name'=>'Entertainment',  'icon'=>'🎮','is_default'=>1,'month_spent'=>0,  'budget_amount'=>0],
    ['id'=>8,'name'=>'Health',         'icon'=>'🏥','is_default'=>1,'month_spent'=>0,  'budget_amount'=>0],
    ['id'=>9,'name'=>'Other',          'icon'=>'🔀','is_default'=>1,'month_spent'=>0,  'budget_amount'=>0],
    ['id'=>10,'name'=>'Gym',           'icon'=>'🏋️','is_default'=>0,'month_spent'=>150,'budget_amount'=>200],
    ['id'=>11,'name'=>'Rent',          'icon'=>'🏠','is_default'=>0,'month_spent'=>0,  'budget_amount'=>0],
  ]
]);
