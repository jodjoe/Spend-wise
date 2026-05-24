<?php
/**
 * Database Connection - PDO Singleton Pattern
 * 
 * Handles all database connections for the Birr Wise application.
 * Implements singleton pattern to ensure only one PDO connection is created.
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../config.php';

class Database {
    /**
     * @var PDO|null Static instance of the PDO connection
     */
    private static $instance = null;

    /**
     * Get or create the PDO database connection
     * 
     * @return PDO The database connection instance
     * @throws Exception If connection fails
     */
    public static function getInstance() {
        if (self::$instance === null) {
            try {
                // Create DSN (Data Source Name)
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

                // Create PDO connection
                self::$instance = new PDO(
                    $dsn,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                // Determine if request expects JSON (API call) or HTML (page view)
                $is_json_request = (isset($_SERVER['CONTENT_TYPE']) && 
                                   strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                                   (isset($_SERVER['HTTP_ACCEPT']) && 
                                   strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

                if ($is_json_request) {
                    // Return JSON error for API requests
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Database connection failed',
                        'code' => 500
                    ]);
                } else {
                    // Show HTML error for page requests
                    http_response_code(500);
                    echo '<!DOCTYPE html>';
                    echo '<html>';
                    echo '<head><title>Connection Error</title></head>';
                    echo '<body>';
                    echo '<h1>Database Connection Error</h1>';
                    echo '<p>Unable to connect to the database. Please try again later.</p>';
                    echo '</body>';
                    echo '</html>';
                }
                exit;
            }
        }

        return self::$instance;
    }

    /**
     * Prevent direct instantiation of this class
     */
    private function __construct() {}

    /**
     * Prevent cloning of this class
     */
    private function __clone() {}
}

/**
 * Helper function to get database connection throughout the application
 * 
 * Usage: $pdo = getDB();
 * 
 * @return PDO The database connection instance
 */
function getDB() {
    return Database::getInstance();
}
?>
