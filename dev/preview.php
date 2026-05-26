<?php
session_start();
$_SESSION['user_id']             = 1;
$_SESSION['user_name']           = 'Abebe Kebede';
$_SESSION['onboarding_complete'] = 1;
$_SESSION['csrf_token']          = bin2hex(random_bytes(32));
$_SESSION['preview_mode']        = true;

header('Location: /pages/dashboard.php');
exit;
