<?php
require_once '../includes/session.php';
header('Content-Type: application/json');
$prompt = trim($_POST['prompt'] ?? '');
if (empty($prompt)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Please provide a prompt']);
    exit;
}
// Simple canned reply for dev testing
$reply = "Hi — to stay on track this week, try limiting non-essential spending to about 500 ETB and prioritize food and transport. Consider moving 200 ETB to savings if possible.";

echo json_encode(['success'=>true,'data'=>['message'=>$reply]]);
?>