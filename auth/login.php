<?php
/**
 * User Login Page
 * 
 * Handles user authentication with email and password.
 * Validates credentials and sets session variables before redirecting.
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// Start session for CSRF token (login page cannot include session.php)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Initialize variables
$error = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid request';
    } else {
        // Get and sanitize inputs
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // ============================================================
        // SERVER-SIDE VALIDATION
        // ============================================================

        // Validate all fields are provided
        if (empty($email) || empty($password)) {
            $error = 'Email and password are required';
        }
        // Validate email format
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        }
        else {
            try {
                $pdo = getDB();

                // Fetch user by email
                $stmt = $pdo->prepare(
                    'SELECT id, name, password, onboarding_complete FROM users WHERE email = :email'
                );
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                // User not found or password incorrect
                if (!$user || !password_verify($password, $user['password'])) {
                    $error = 'Invalid email or password';
                } else {
                    // ============================================================
                    // AUTHENTICATION SUCCESSFUL
                    // ============================================================

                    // Regenerate session id to prevent session fixation
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['onboarding_complete'] = $user['onboarding_complete'];

                    // Redirect based on onboarding status
                    if ($user['onboarding_complete'] == 0) {
                        header('Location: onboarding.php');
                    } else {
                        header('Location: ../pages/dashboard.php');
                    }
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <title>Login - Birr Wise</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E7D32;
            --primary-light: #4CAF50;
            --accent: #F9A825;
            --danger: #C62828;
            --warning: #F57F17;
            --success: #2E7D32;
            --bg: #F5F5F5;
            --card: #FFFFFF;
            --text: #212121;
            --text-muted: #757575;
            --border: #E0E0E0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 420px;
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .app-name {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }

        .app-name span {
            margin-right: 8px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
        }

        input.error {
            border-color: var(--danger);
        }

        .error-message {
            font-size: 12px;
            color: var(--danger);
            margin-top: 4px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: var(--text-muted);
            padding: 4px;
            margin-top: 12px;
        }

        .password-toggle:hover {
            color: var(--text);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-error {
            background-color: #FFEBEE;
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 8px;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (min-width: 768px) {
            .card {
                padding: 40px;
            }

            .header {
                margin-bottom: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="app-name">
                    <span></span>Birr Wise
                </div>
                <p class="subtitle">Login to your Birr Wise account</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error show">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="example@gmail.com"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <div class="error-message"></div>
                </div>

                <!-- Password Field -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-group">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter password"
                            required
                        >
                        <button type="button" class="password-toggle" data-target="password">👁️</button>
                    </div>
                    <div class="error-message"></div>
                </div>

                <button type="submit" class="btn-primary">Login</button>
            </form>

            <div class="footer">
                Don't have an account? <a href="register.php">Register</a>
            </div>
        </div>
    </div>

    <script>
        /**
         * Toggle password visibility
         */
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                this.textContent = isPassword ? '👁️‍🗨️' : '👁️';
            });
        });

        /**
         * Validate email format
         */
        function validateEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        /**
         * Client-side form validation
         */
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let isValid = true;
            const fields = {
                email: { 
                    input: document.getElementById('email'),
                    validate: (val) => validateEmail(val),
                    errorMsg: 'Please enter a valid email'
                },
                password: { 
                    input: document.getElementById('password'),
                    validate: (val) => val.trim() !== '',
                    errorMsg: 'Password is required'
                }
            };

            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(msg => {
                msg.classList.remove('show');
            });
            document.querySelectorAll('input').forEach(input => {
                input.classList.remove('error');
            });

            // Validate each field
            Object.entries(fields).forEach(([key, field]) => {
                if (!field.validate(field.input.value)) {
                    isValid = false;
                    field.input.classList.add('error');
                    const errorMsg = field.input.parentElement.querySelector('.error-message') || 
                                    field.input.nextElementSibling;
                    if (errorMsg) {
                        errorMsg.textContent = field.errorMsg;
                        errorMsg.classList.add('show');
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
