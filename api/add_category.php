<?php
/**
 * Add Category API
 * 
 * Creates a new custom category for the user
 * Only custom categories can be added (is_default = 0)
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
    $pdo = getDB();
    $user_id = $_SESSION['user_id'];

    // Get POST data (sanitized)
    $name = sanitize($_POST['name'] ?? '');
    $icon = sanitize($_POST['icon'] ?? '/assets/icon/user.png');

    // Validation
    if (empty($name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Category name is required',
            'field' => 'name'
        ]);
        exit;
    }

    if (strlen($name) > 50) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Category name must be 50 characters or less',
            'field' => 'name'
        ]);
        exit;
    }

    // Check if category with same name already exists for this user
    $stmt = $pdo->prepare('
        SELECT id FROM categories 
        WHERE user_id = :user_id AND name = :name
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $name
    ]);

    if ($stmt->fetch()) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'You already have a category with this name',
            'field' => 'name'
        ]);
        exit;
    }

    // Insert new category
    $stmt = $pdo->prepare('
        INSERT INTO categories (user_id, name, icon, is_default)
        VALUES (:user_id, :name, :icon, 0)
    ');
    $stmt->execute([
        ':user_id' => $user_id,
        ':name' => $name,
        ':icon' => $icon
    ]);

    $category_id = $pdo->lastInsertId();

    // Return success response
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Category created',
        'data' => [
            'id' => (int)$category_id,
            'name' => $name,
            'icon' => $icon,
            'is_default' => 0
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create category'
    ]);
}
?>
