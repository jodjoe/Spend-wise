<?php
// Development helper - sets a session for user_id=1 and onboarding_complete=1
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['onboarding_complete'] = 1;
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Test session created', 'user_id' => 1]);
?>