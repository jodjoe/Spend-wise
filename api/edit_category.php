<?php
/**
 * Edit Category API
 * 
 * Updates a custom category
 * Cannot edit default categories
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
    $id = intval(sanitize($_POST['id'] ?? '0'));
    $name = sanitize($_POST['name'] ?? '');
    $icon = sanitize($_POST['icon'] ?? '/assets/icon/user.png');

    // Validation
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid category ID'
        ]);
        exit;
    }

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

    // Verify category belongs to user and is not default
    $stmt = $pdo->prepare('
        SELECT is_default FROM categories 
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);
    $category = $stmt->fetch();

    if (!$category) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Category not found'
        ]);
        exit;
    }

    if ($category['is_default'] == 1) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Cannot edit default categories'
        ]);
        exit;
    }

    // Update category
    $stmt = $pdo->prepare('
        UPDATE categories 
        SET name = :name, icon = :icon
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([
        ':name' => $name,
        ':icon' => $icon,
        ':id' => $id,
        ':user_id' => $user_id
    ]);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Category updated'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update category'
    ]);
}
?>
