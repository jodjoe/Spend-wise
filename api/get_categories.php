<?php
/**
 * Get Categories API
 * 
 * Fetches all categories for the current user
 * Ordered by is_default descending, then name ascending
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');
requireMethod('GET');

try {
    $pdo = getDB();
    $user_id = $_SESSION['user_id'];

    // Fetch all categories for this user
    $stmt = $pdo->prepare('
        SELECT id, name, icon, is_default 
        FROM categories 
        WHERE user_id = :user_id 
        ORDER BY is_default DESC, name ASC
    ');
    $stmt->execute([':user_id' => $user_id]);
    $categories = $stmt->fetchAll();

    // Return success response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $categories
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch categories'
    ]);
}
?>
