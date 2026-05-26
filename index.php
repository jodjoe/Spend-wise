<?php
// Redirect root to the login page so the app launches correctly.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host     = $_SERVER['HTTP_HOST'];
$dir      = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
header('Location: ' . $protocol . '://' . $host . $dir . '/auth/login.php');
exit;
