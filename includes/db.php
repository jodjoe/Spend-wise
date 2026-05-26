<?php
require_once '../config.php';

class Database {
    private static $instance = null;

    public static function getInstance() {
        // ── Preview mode: no DB needed ──
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['preview_mode'])) {
            return null;
        }

        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                $is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                        || (isset($_SERVER['HTTP_ACCEPT'])   && strpos($_SERVER['HTTP_ACCEPT'],   'application/json') !== false);

                if ($is_json) {
                    header('Content-Type: application/json');
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
                } else {
                    http_response_code(500);
                    echo '<!DOCTYPE html><html><head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width,initial-scale=1">
                    <title>DB Error — Birr Wise</title>
                    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
                    <style>
                      *{box-sizing:border-box;margin:0;padding:0}
                      body{font-family: 'Orbitron', monospace;background:#f5f5f5;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
                      .card{background:#fff;border-radius:16px;padding:40px 32px;max-width:420px;width:100%;box-shadow:0 8px 32px rgba(0,0,0,.1);text-align:center}
                      .icon{font-size:48px;margin-bottom:16px}
                      h1{font-size:20px;font-weight:700;margin-bottom:8px;color:#0a0a0a}
                      p{font-size:14px;color:#666;line-height:1.6;margin-bottom:24px}
                      .btn{display:inline-block;padding:12px 28px;background:#000;color:#fff;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none}
                      .hint{font-size:12px;color:#999;margin-top:16px}
                      code{background:#f0f0f0;padding:2px 6px;border-radius:4px;font-size:12px}
                    </style>
                    </head><body>
                    <div class="card">
                      <div class="icon">🗄️</div>
                      <h1>Database not running</h1>
                      <p>MariaDB is not started. Start it to use the full app, or use preview mode to browse the UI.</p>
                      <a href="/dev/preview.php" class="btn">Open Preview Mode</a>
                      <p class="hint">To start the DB: <code>sudo systemctl start mariadb</code></p>
                    </div>
                    </body></html>';
                }
                exit;
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}

function getDB() {
    return Database::getInstance();
}
?>
