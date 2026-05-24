<?php
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
    $user_id = $_SESSION['user_id'];
    $prompt = sanitize($_POST['prompt'] ?? '');

    if (empty($prompt)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please enter a question for the assistant.'
        ]);
        exit;
    }

    $assistantPrompt = "You are Birr Wise, a friendly Ethiopian student budgeting assistant. Answer the question clearly and help the student manage money, save, and stay within allowance. User question: {$prompt}";

    $answer = callGemini($assistantPrompt);
    if (!$answer) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unable to connect to the AI assistant. Please try again later.'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'message' => trim($answer)
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Assistant request failed.'
    ]);
}

