<?php
session_start();
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }
header('Content-Type: application/json');

$data = [
  // May 2026
  ['id'=>1, 'category_id'=>1,'amount'=>45.00,'amount_formatted'=>'45.00 ETB','note'=>'Lunch at canteen',    'expense_date'=>'2026-05-26','expense_date_formatted'=>'26 May 2026','category_name'=>'Food',          'category_icon'=>'🍛'],
  ['id'=>2, 'category_id'=>2,'amount'=>20.00,'amount_formatted'=>'20.00 ETB','note'=>'Bus to campus',       'expense_date'=>'2026-05-26','expense_date_formatted'=>'26 May 2026','category_name'=>'Transport',     'category_icon'=>'🚌'],
  ['id'=>3, 'category_id'=>4,'amount'=>150.00,'amount_formatted'=>'150.00 ETB','note'=>'Calculus textbook', 'expense_date'=>'2026-05-25','expense_date_formatted'=>'25 May 2026','category_name'=>'School Supplies','category_icon'=>'📚'],
  ['id'=>4, 'category_id'=>5,'amount'=>50.00,'amount_formatted'=>'50.00 ETB','note'=>'Ethio Telecom top-up','expense_date'=>'2026-05-25','expense_date_formatted'=>'25 May 2026','category_name'=>'Mobile Top-up', 'category_icon'=>'📱'],
  ['id'=>5, 'category_id'=>6,'amount'=>35.00,'amount_formatted'=>'35.00 ETB','note'=>'Coffee with friends', 'expense_date'=>'2026-05-24','expense_date_formatted'=>'24 May 2026','category_name'=>'Café',           'category_icon'=>'☕'],
  ['id'=>6, 'category_id'=>1,'amount'=>60.00,'amount_formatted'=>'60.00 ETB','note'=>'Dinner at restaurant','expense_date'=>'2026-05-23','expense_date_formatted'=>'23 May 2026','category_name'=>'Food',          'category_icon'=>'🍛'],
  ['id'=>7, 'category_id'=>2,'amount'=>40.00,'amount_formatted'=>'40.00 ETB','note'=>'Taxi to hospital',    'expense_date'=>'2026-05-22','expense_date_formatted'=>'22 May 2026','category_name'=>'Transport',     'category_icon'=>'🚌'],
  ['id'=>8, 'category_id'=>10,'amount'=>150.00,'amount_formatted'=>'150.00 ETB','note'=>'Monthly gym fee',  'expense_date'=>'2026-05-20','expense_date_formatted'=>'20 May 2026','category_name'=>'Gym',           'category_icon'=>'🏋️'],
  ['id'=>9, 'category_id'=>1,'amount'=>30.00,'amount_formatted'=>'30.00 ETB','note'=>'Breakfast',           'expense_date'=>'2026-05-19','expense_date_formatted'=>'19 May 2026','category_name'=>'Food',          'category_icon'=>'🍛'],
  ['id'=>10,'category_id'=>4,'amount'=>80.00,'amount_formatted'=>'80.00 ETB','note'=>'Lab manual',          'expense_date'=>'2026-05-18','expense_date_formatted'=>'18 May 2026','category_name'=>'School Supplies','category_icon'=>'📚'],

  // April 2026
  ['id'=>11,'category_id'=>1,'amount'=>55.00,'amount_formatted'=>'55.00 ETB','note'=>'Lunch',               'expense_date'=>'2026-04-28','expense_date_formatted'=>'28 Apr 2026','category_name'=>'Food',          'category_icon'=>'🍛'],
  ['id'=>12,'category_id'=>2,'amount'=>25.00,'amount_formatted'=>'25.00 ETB','note'=>'Bus fare',             'expense_date'=>'2026-04-27','expense_date_formatted'=>'27 Apr 2026','category_name'=>'Transport',     'category_icon'=>'🚌'],
  ['id'=>13,'category_id'=>3,'amount'=>200.00,'amount_formatted'=>'200.00 ETB','note'=>'New shirt',          'expense_date'=>'2026-04-25','expense_date_formatted'=>'25 Apr 2026','category_name'=>'Clothing',      'category_icon'=>'👕'],
  ['id'=>14,'category_id'=>6,'amount'=>40.00,'amount_formatted'=>'40.00 ETB','note'=>'Study session coffee', 'expense_date'=>'2026-04-22','expense_date_formatted'=>'22 Apr 2026','category_name'=>'Café',          'category_icon'=>'☕'],
  ['id'=>15,'category_id'=>5,'amount'=>50.00,'amount_formatted'=>'50.00 ETB','note'=>'Data bundle',          'expense_date'=>'2026-04-20','expense_date_formatted'=>'20 Apr 2026','category_name'=>'Mobile Top-up', 'category_icon'=>'📱'],
  ['id'=>16,'category_id'=>1,'amount'=>70.00,'amount_formatted'=>'70.00 ETB','note'=>'Groceries',            'expense_date'=>'2026-04-18','expense_date_formatted'=>'18 Apr 2026','category_name'=>'Food',          'category_icon'=>'🍛'],
  ['id'=>17,'category_id'=>8,'amount'=>120.00,'amount_formatted'=>'120.00 ETB','note'=>'Clinic visit',       'expense_date'=>'2026-04-15','expense_date_formatted'=>'15 Apr 2026','category_name'=>'Health',        'category_icon'=>'🏥'],
  ['id'=>18,'category_id'=>4,'amount'=>90.00,'amount_formatted'=>'90.00 ETB','note'=>'Notebook set',         'expense_date'=>'2026-04-10','expense_date_formatted'=>'10 Apr 2026','category_name'=>'School Supplies','category_icon'=>'📚'],

  // March 2026
  ['id'=>19,'category_id'=>1,'amount'=>500.00,'amount_formatted'=>'500.00 ETB','note'=>'Monthly food budget','expense_date'=>'2026-03-30','expense_date_formatted'=>'30 Mar 2026','category_name'=>'Food',          'category_icon'=>'🍛'],
  ['id'=>20,'category_id'=>2,'amount'=>180.00,'amount_formatted'=>'180.00 ETB','note'=>'Transport for month','expense_date'=>'2026-03-28','expense_date_formatted'=>'28 Mar 2026','category_name'=>'Transport',     'category_icon'=>'🚌'],
  ['id'=>21,'category_id'=>7,'amount'=>100.00,'amount_formatted'=>'100.00 ETB','note'=>'Cinema tickets',     'expense_date'=>'2026-03-25','expense_date_formatted'=>'25 Mar 2026','category_name'=>'Entertainment', 'category_icon'=>'🎮'],
  ['id'=>22,'category_id'=>6,'amount'=>95.00,'amount_formatted'=>'95.00 ETB','note'=>'Café study sessions',  'expense_date'=>'2026-03-20','expense_date_formatted'=>'20 Mar 2026','category_name'=>'Café',          'category_icon'=>'☕'],
  ['id'=>23,'category_id'=>4,'amount'=>250.00,'amount_formatted'=>'250.00 ETB','note'=>'Semester books',     'expense_date'=>'2026-03-15','expense_date_formatted'=>'15 Mar 2026','category_name'=>'School Supplies','category_icon'=>'📚'],
  ['id'=>24,'category_id'=>5,'amount'=>50.00,'amount_formatted'=>'50.00 ETB','note'=>'Phone top-up',         'expense_date'=>'2026-03-10','expense_date_formatted'=>'10 Mar 2026','category_name'=>'Mobile Top-up', 'category_icon'=>'📱'],
];

echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
