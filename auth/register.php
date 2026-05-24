<?php
/**
 * User Registration Page
 * 
 * Handles new user account creation with form validation.
 * Creates user account, inserts default categories, and redirects to onboarding.
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// Initialize variables
$error = '';
$full_name = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize inputs
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // ============================================================
    // SERVER-SIDE VALIDATION
    // ============================================================

    // Validate all fields are provided
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    }
    // Validate email format
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    }
    // Validate password length
    elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    }
    // Validate password has at least one number
    elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number';
    }
    // Validate passwords match
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    }
    // All validations passed
    else {
        try {
            $pdo = getDB();

            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute([':email' => $email]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                $error = 'This email is already registered';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user with default values
                $stmt = $pdo->prepare('
                    INSERT INTO users (name, email, password, monthly_allowance, onboarding_complete)
                    VALUES (:name, :email, :password, :allowance, 0)
                ');
                $stmt->execute([
                    ':name' => $full_name,
                    ':email' => $email,
                    ':password' => $password_hash,
                    ':allowance' => DEFAULT_ALLOWANCE
                ]);

                // Get the newly created user ID
                $user_id = $pdo->lastInsertId();

                // ============================================================
                // INSERT DEFAULT CATEGORIES (9 total)
                // ============================================================
                $default_categories = [
                    ['name' => 'Food', 'icon' => '🍛'],
                    ['name' => 'Transport', 'icon' => '🚌'],
                    ['name' => 'Clothing', 'icon' => '👕'],
                    ['name' => 'School Supplies', 'icon' => '📚'],
                    ['name' => 'Mobile Top-up', 'icon' => '📱'],
                    ['name' => 'Café', 'icon' => '☕'],
                    ['name' => 'Entertainment', 'icon' => '🎮'],
                    ['name' => 'Health', 'icon' => '🏥'],
                    ['name' => 'Other', 'icon' => '🔀']
                ];

                $stmt = $pdo->prepare('
                    INSERT INTO categories (user_id, name, icon, is_default)
                    VALUES (:user_id, :name, :icon, 1)
                ');

                foreach ($default_categories as $category) {
                    $stmt->execute([
                        ':user_id' => $user_id,
                        ':name' => $category['name'],
                        ':icon' => $category['icon']
                    ]);
                }

                // ============================================================
                // SET SESSION AND REDIRECT
                // ============================================================
                session_start();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $full_name;
                $_SESSION['onboarding_complete'] = 0;

                // Redirect to onboarding
                header('Location: onboarding.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Birr Wise</title>
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

        .alert-success {
            background-color: #E8F5E9;
            border-left: 4px solid var(--success);
            color: var(--success);
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
                    <span>🇪🇹</span>Birr Wise
                </div>
                <p class="subtitle">Join Birr Wise — manage your 3000 ETB wisely</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error show">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="registerForm" novalidate>
                <!-- Full Name Field -->
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        placeholder="Your full name"
                        value="<?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                    <div class="error-message"></div>
                </div>

                <!-- Email Field -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        placeholder="your@email.com"
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
                            placeholder="At least 8 characters with a number"
                            required
                        >
                        <button type="button" class="password-toggle" data-target="password">👁️</button>
                    </div>
                    <div class="error-message"></div>
                </div>

                <!-- Confirm Password Field -->
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-group">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            placeholder="Confirm your password"
                            required
                        >
                        <button type="button" class="password-toggle" data-target="confirm_password">👁️</button>
                    </div>
                    <div class="error-message"></div>
                </div>

                <button type="submit" class="btn-primary">Create Account</button>
            </form>

            <div class="footer">
                Already have an account? <a href="login.php">Login</a>
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
         * Validate password strength
         */
        function validatePassword(password) {
            return password.length >= 8 && /[0-9]/.test(password);
        }

        /**
         * Client-side form validation
         */
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            const fields = {
                full_name: { 
                    input: document.getElementById('full_name'),
                    validate: (val) => val.trim() !== '',
                    errorMsg: 'Full name is required'
                },
                email: { 
                    input: document.getElementById('email'),
                    validate: (val) => validateEmail(val),
                    errorMsg: 'Please enter a valid email'
                },
                password: { 
                    input: document.getElementById('password'),
                    validate: (val) => validatePassword(val),
                    errorMsg: 'Password must be at least 8 characters with a number'
                },
                confirm_password: { 
                    input: document.getElementById('confirm_password'),
                    validate: (val) => val === document.getElementById('password').value,
                    errorMsg: 'Passwords do not match'
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
