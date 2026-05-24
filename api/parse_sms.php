<?php
/**
 * Parse SMS API
 * 
 * Attempts to parse a raw SMS expense message and extract amount,
 * note and expense date. Returns parsed values for quick entry.
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');
requireMethod('POST');

// CSRF validation
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    jsonResponse(false, [], 'Invalid request', 403);
}

try {
    $text = trim(sanitize($_POST['text'] ?? ''));

    if (empty($text)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'SMS text is required'
        ]);
        exit;
    }

    // Try to parse amount from SMS
    $amount = null;
    $amountText = '';
    if (preg_match('/(\d+[\.,]?\d{0,2})/', $text, $matches)) {
        $amount = floatval(str_replace(',', '.', $matches[1]));
        $amountText = $matches[1];
    }

    // Try to parse date from SMS. Accept YYYY-MM-DD or DD/MM/YYYY
    $expense_date = date('Y-m-d');
    if (preg_match('/(\d{4}-\d{2}-\d{2})/', $text, $dateMatch)) {
        $expense_date = $dateMatch[1];
    } elseif (preg_match('/(\d{2}\/\d{2}\/\d{4})/', $text, $dateMatch)) {
        $parts = explode('/', $dateMatch[1]);
        $expense_date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }

    // Determine a note by stripping amount and dates
    $patterns = [];
    if (!empty($amountText)) {
        $patterns[] = '/\b' . preg_quote($amountText, '/') . '\b/';
    }
    $patterns[] = '/(\d{4}-\d{2}-\d{2})/';
    $patterns[] = '/(\d{2}\/\d{2}\/\d{4})/';

    $note = trim(preg_replace($patterns, '', $text));
    if (empty($note)) {
        $note = 'SMS expense';
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'amount' => $amount,
            'note' => htmlspecialchars($note, ENT_QUOTES, 'UTF-8'),
            'expense_date' => $expense_date
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to parse SMS text'
    ]);
}
?>
