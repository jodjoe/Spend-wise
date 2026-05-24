<?php
/**
 * Example configuration for Spend-wise
 * Copy this to `config.php` and fill in your local values.
 */

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'birr_wise');
define('DB_USER', 'birr_wise_user');
define('DB_PASS', 'your_db_password_here');

// ============================================================
// GEMINI API CONFIGURATION
// ============================================================
// Replace with your Google Gemini key if used (or leave empty)
define('GEMINI_API_KEY', 'YOUR_GEMINI_API_KEY_HERE');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent');

// ============================================================
// APPLICATION CONFIGURATION
// ============================================================
define('APP_NAME', 'Birr Wise');
define('CURRENCY', 'ETB');
define('DEFAULT_ALLOWANCE', 3000);

?>
