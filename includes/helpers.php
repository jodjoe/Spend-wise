<?php
/**
 * Helper Functions
 * 
 * Reusable utility functions for input validation, sanitization,
 * financial calculations, and API responses.
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../config.php';

// ============================================================
// INPUT & OUTPUT FUNCTIONS
// ============================================================

/**
 * Sanitize user input to prevent XSS attacks
 * Trims whitespace and applies htmlspecialchars with ENT_QUOTES
 * 
 * @param mixed $input The input to sanitize
 * @return string|mixed Sanitized string or original value if not string
 */
function sanitize($input) {
    if (is_string($input)) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    return $input;
}

/**
 * Validate monetary amount
 * Checks if amount is numeric, positive, and within reasonable limits
 * 
 * @param mixed $amount The amount to validate
 * @return bool True if valid, false otherwise
 */
function validateAmount($amount) {
    // Must be numeric
    if (!is_numeric($amount)) {
        return false;
    }

    // Convert to float for comparison
    $amount = floatval($amount);

    // Must be greater than 0
    if ($amount <= 0) {
        return false;
    }

    // Must be less than 100,000 ETB (reasonable max for student)
    if ($amount >= 100000) {
        return false;
    }

    return true;
}

// ============================================================
// FINANCIAL CALCULATION FUNCTIONS
// ============================================================

/**
 * Calculate remaining allowance for today
 * 
 * Divides monthly allowance by days in current month to get daily limit.
 * Subtracts today's total expenses from daily limit.
 * 
 * @param int $user_id The user ID
 * @param PDO $pdo The database connection
 * @return float|null Remaining allowance for today, or null on error
 */
function getRemainingToday($user_id, $pdo) {
    try {
        // Get user's monthly allowance
        $stmt = $pdo->prepare('SELECT monthly_allowance FROM users WHERE id = :user_id');
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['monthly_allowance'] <= 0) {
            return null;
        }

        $monthly_allowance = floatval($user['monthly_allowance']);

        // Get number of days in current month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));

        // Calculate daily limit
        $daily_limit = $monthly_allowance / $days_in_month;

        // Get today's total expenses
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(amount), 0) as today_total
            FROM expenses
            WHERE user_id = :user_id AND expense_date = CURDATE()
        ');
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch();
        $today_spent = floatval($result['today_total']);

        // Calculate remaining
        $remaining = $daily_limit - $today_spent;

        return max(0, round($remaining, 2)); // Return 0 if negative
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Calculate remaining allowance for current month
 * 
 * Gets monthly allowance and subtracts total expenses in current month.
 * 
 * @param int $user_id The user ID
 * @param PDO $pdo The database connection
 * @return float|null Remaining allowance for month, or null on error
 */
function getRemainingMonth($user_id, $pdo) {
    try {
        // Get user's monthly allowance
        $stmt = $pdo->prepare('SELECT monthly_allowance FROM users WHERE id = :user_id');
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['monthly_allowance'] <= 0) {
            return null;
        }

        $monthly_allowance = floatval($user['monthly_allowance']);

        // Get total expenses for current month
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(amount), 0) as month_total
            FROM expenses
            WHERE user_id = :user_id 
            AND MONTH(expense_date) = MONTH(CURDATE())
            AND YEAR(expense_date) = YEAR(CURDATE())
        ');
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch();
        $month_spent = floatval($result['month_total']);

        // Calculate remaining
        $remaining = $monthly_allowance - $month_spent;

        return max(0, round($remaining, 2)); // Return 0 if negative
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get budget usage percentage for a category
 * 
 * Sums expenses for category in current period (weekly/monthly).
 * Divides by budget amount to get usage percentage.
 * 
 * @param int $user_id The user ID
 * @param int $category_id The category ID
 * @param string $period The budget period ('weekly' or 'monthly')
 * @param PDO $pdo The database connection
 * @return int|null Usage percentage (0-100+), or null on error
 */
function getBudgetUsage($user_id, $category_id, $period, $pdo) {
    try {
        // Get budget amount for this category
        $stmt = $pdo->prepare('
            SELECT amount FROM budgets
            WHERE user_id = :user_id 
            AND category_id = :category_id 
            AND period = :period
        ');
        $stmt->execute([
            ':user_id' => $user_id,
            ':category_id' => $category_id,
            ':period' => $period
        ]);
        $budget = $stmt->fetch();

        if (!$budget) {
            return null; // No budget exists
        }

        $budget_amount = floatval($budget['amount']);

        // Calculate date range for period
        if ($period === 'weekly') {
            // Get Monday of current week
            $monday = date('Y-m-d', strtotime('monday this week'));
            $date_condition = "expense_date >= '$monday' AND expense_date <= CURDATE()";
        } else {
            // Current month
            $date_condition = "MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())";
        }

        // Get total spent in period
        $query = "
            SELECT COALESCE(SUM(amount), 0) as period_total
            FROM expenses
            WHERE user_id = :user_id 
            AND category_id = :category_id 
            AND $date_condition
        ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':category_id' => $category_id
        ]);
        $result = $stmt->fetch();
        $spent = floatval($result['period_total']);

        // Calculate percentage
        if ($budget_amount <= 0) {
            return 0;
        }

        $percentage = intval(($spent / $budget_amount) * 100);

        return $percentage;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get spending pace status
 * 
 * Compares percentage of month elapsed vs percentage of budget spent.
 * Returns status: 'on_track', 'warning', or 'critical'.
 * 
 * @param int $user_id The user ID
 * @param PDO $pdo The database connection
 * @return string|null Status ('on_track', 'warning', 'critical'), or null on error
 */
function getPaceStatus($user_id, $pdo) {
    try {
        // Get user's monthly allowance
        $stmt = $pdo->prepare('SELECT monthly_allowance FROM users WHERE id = :user_id');
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['monthly_allowance'] <= 0) {
            return null;
        }

        $monthly_allowance = floatval($user['monthly_allowance']);

        // Calculate days elapsed in month
        $current_day = intval(date('d'));
        $days_in_month = intval(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')));
        $month_percent = ($current_day / $days_in_month) * 100;

        // Calculate percent of budget spent
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(amount), 0) as month_total
            FROM expenses
            WHERE user_id = :user_id 
            AND MONTH(expense_date) = MONTH(CURDATE())
            AND YEAR(expense_date) = YEAR(CURDATE())
        ');
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch();
        $spent = floatval($result['month_total']);
        $spent_percent = ($spent / $monthly_allowance) * 100;

        // Determine status
        if ($spent_percent <= $month_percent) {
            return 'on_track';
        } elseif ($spent_percent <= $month_percent + 10) {
            return 'warning';
        } else {
            return 'critical';
        }
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Predict remaining allowance at end of month
 * 
 * Calculates average daily spending so far and projects to end of month.
 * Returns predicted remaining amount.
 * 
 * @param int $user_id The user ID
 * @param PDO $pdo The database connection
 * @return float|null Predicted remaining at month end, or null on error
 */
function getEndOfMonthPrediction($user_id, $pdo) {
    try {
        // Get user's monthly allowance
        $stmt = $pdo->prepare('SELECT monthly_allowance FROM users WHERE id = :user_id');
        $stmt->execute([':user_id' => $user_id]);
        $user = $stmt->fetch();

        if (!$user || $user['monthly_allowance'] <= 0) {
            return null;
        }

        $monthly_allowance = floatval($user['monthly_allowance']);

        // Get total spent so far this month
        $stmt = $pdo->prepare('
            SELECT COALESCE(SUM(amount), 0) as month_total
            FROM expenses
            WHERE user_id = :user_id 
            AND MONTH(expense_date) = MONTH(CURDATE())
            AND YEAR(expense_date) = YEAR(CURDATE())
        ');
        $stmt->execute([':user_id' => $user_id]);
        $result = $stmt->fetch();
        $spent_so_far = floatval($result['month_total']);

        // Calculate current day
        $current_day = intval(date('d'));

        // Avoid division by zero
        if ($current_day <= 0) {
            return $monthly_allowance;
        }

        // Calculate average daily spend
        $avg_daily = $spent_so_far / $current_day;

        // Get total days in month
        $days_in_month = intval(cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y')));

        // Project total spending by end of month
        $projected_total = $avg_daily * $days_in_month;

        // Calculate predicted remaining
        $predicted_remaining = $monthly_allowance - $projected_total;

        return round($predicted_remaining, 2);
    } catch (PDOException $e) {
        return null;
    }
}

// ============================================================
// API RESPONSE FUNCTIONS
// ============================================================

/**
 * Send standardized JSON response
 * 
 * Sets HTTP response code and returns JSON with success, data, and message.
 * Automatically exits after sending response.
 * 
 * @param bool $success Whether the operation was successful
 * @param array $data Optional data to include in response
 * @param string $message Optional message text
 * @param int $code HTTP response code (default 200)
 * @return void Sends response and exits
 */
function jsonResponse($success, $data = [], $message = '', $code = 200) {
    header('Content-Type: application/json');
    http_response_code($code);

    $response = [
        'success' => $success,
        'data' => $data
    ];

    if (!empty($message)) {
        $response['message'] = $message;
    }

    echo json_encode($response);
    exit;
}

/**
 * Require a specific HTTP method for an endpoint.
 * If the current request method does not match, returns 405 JSON and exits.
 *
 * @param string $method The required HTTP method (GET or POST)
 * @return void
 */
function requireMethod($method) {
    $required = strtoupper($method);
    $current = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    if ($current !== $required) {
        jsonResponse(false, [], 'Method not allowed', 405);
    }
}

// ============================================================
// GEMINI API FUNCTIONS
// ============================================================

/**
 * Call Google Gemini API via cURL
 * 
 * Sends a prompt to the Gemini API and returns the generated response text.
 * Uses generationConfig for controlled output (temperature 0.3, max 500 tokens).
 * Timeout is 15 seconds.
 * 
 * @param string $prompt The prompt to send to Gemini
 * @return string|null The generated response text, or null on failure
 */
function callGemini($prompt) {
    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => 500
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check for successful response
    if ($http_code !== 200 || !$response) {
        return null;
    }

    // Decode JSON response
    $decoded = json_decode($response, true);

    // Extract response text safely
    if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
        return $decoded['candidates'][0]['content']['parts'][0]['text'];
    }

    return null;
}
?>
