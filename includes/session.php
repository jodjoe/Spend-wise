<?php
/**
 * Session Management
 * 
 * Handles session initialization, validation, and auth redirects.
 * Checks for active user session and onboarding completion status.
 * Routes unauthorized API requests to JSON response, pages to login redirect.
 * 
 * @package BIRRWise
 * @version 1.0
 */

// Start or resume session
session_start();

// CSRF token generation for forms and AJAX requests
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Fallback to less-preferred but safe randomness
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

/**
 * Determine if current request expects JSON response (API call)
 * Checks Content-Type header and Accept header
 * 
 * @return bool True if request is for JSON, false otherwise
 */
function is_json_request() {
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    
    return (strpos($content_type, 'application/json') !== false) ||
           (strpos($accept, 'application/json') !== false);
}

/**
 * Check if user is logged in
 * Validates session user_id exists
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user completed onboarding
 * Validates onboarding_complete flag is set to 1
 * 
 * @return bool True if onboarding complete, false otherwise
 */
function is_onboarding_complete() {
    return isset($_SESSION['onboarding_complete']) && $_SESSION['onboarding_complete'] == 1;
}

/**
 * Get the current page script name for redirect checking
 * Returns only the filename (e.g., "onboarding.php")
 * 
 * @return string The current PHP filename
 */
function get_current_page() {
    return basename($_SERVER['SCRIPT_FILENAME']);
}

// ============================================================
// AUTH ENFORCEMENT
// ============================================================

// If user is not logged in
if (!is_logged_in()) {
    if (is_json_request()) {
        // API request without auth - return JSON 401
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized',
            'code' => 401
        ]);
        exit;
    } else {
        // Page request without auth - redirect to login
        header('Location: /auth/login.php');
        exit;
    }
}

// ============================================================
// ONBOARDING ENFORCEMENT
// ============================================================

// If user is logged in but onboarding not complete
if (is_logged_in() && !is_onboarding_complete()) {
    $current_page = get_current_page();
    
    // Allow access to onboarding page and logout
    if ($current_page !== 'onboarding.php' && $current_page !== 'logout.php') {
        if (is_json_request()) {
            // API request - return JSON redirect instruction
            header('Content-Type: application/json');
            http_response_code(302);
            echo json_encode([
                'success' => false,
                'message' => 'Onboarding required',
                'redirect' => '/auth/onboarding.php'
            ]);
            exit;
        } else {
            // Page request - redirect to onboarding
            header('Location: /auth/onboarding.php');
            exit;
        }
    }
}
?>
