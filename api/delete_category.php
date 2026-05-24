<?php
/**
 * Delete Category API
 * 
 * Deletes a custom category
 * If category has expenses, shows warning first
 * If confirmed, moves expenses to "Other" category before deleting
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
    $confirm = intval(sanitize($_POST['confirm'] ?? '0'));

    // Validation
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid category ID'
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
            'message' => 'Cannot delete default categories'
        ]);
        exit;
    }

    // Count expenses with this category
    $stmt = $pdo->prepare('
        SELECT COUNT(*) as count FROM expenses 
        WHERE category_id = :category_id AND user_id = :user_id
    ');
    $stmt->execute([':category_id' => $id, ':user_id' => $user_id]);
    $result = $stmt->fetch();
    $expense_count = intval($result['count']);

    // If expenses exist and not confirmed, show warning
    if ($expense_count > 0 && !$confirm) {
        http_response_code(200);
        echo json_encode([
            'success' => false,
            'warning' => true,
            'expense_count' => $expense_count,
            'message' => "This category has $expense_count expense(s). They will be moved to 'Other'."
        ]);
        exit;
    }

    // If expenses exist and confirmed, move to "Other" category
    if ($expense_count > 0 && $confirm) {
        // Find "Other" category
        $stmt = $pdo->prepare('
            SELECT id FROM categories 
            WHERE user_id = :user_id AND name = \'Other\'
        ');
        $stmt->execute([':user_id' => $user_id]);
        $other = $stmt->fetch();

        if ($other) {
            // Move expenses to "Other"
            $stmt = $pdo->prepare('
                UPDATE expenses 
                SET category_id = :other_id
                WHERE category_id = :category_id AND user_id = :user_id
            ');
            $stmt->execute([
                ':other_id' => $other['id'],
                ':category_id' => $id,
                ':user_id' => $user_id
            ]);
        }
    }

    // Delete category
    $stmt = $pdo->prepare('
        DELETE FROM categories 
        WHERE id = :id AND user_id = :user_id
    ');
    $stmt->execute([':id' => $id, ':user_id' => $user_id]);

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Category deleted'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete category'
    ]);
}
?>
