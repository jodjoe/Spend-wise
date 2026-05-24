<?php
/**
 * Logout Handler
 * 
 * Destroys session and redirects to login page.
 * 
 * @package BIRRWise
 * @version 1.0
 */

// Start session
session_start();

// Unset all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
?>
