<?php
/**
 * Onboarding Wizard
 * 
 * 3-step wizard for new users to set allowance, select categories, and set first budget.
 * Tracks current step in session and updates database progressively.
 * 
 * @package BIRRWise
 * @version 1.0
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

// Start session (user is already logged in from register or login)
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$current_step = $_SESSION['onboarding_step'] ?? 1;
$error = '';
$pdo = getDB();

// ============================================================
// HANDLE FORM SUBMISSIONS
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // STEP 1: Set Allowance
    if ($action === 'set_allowance') {
        $allowance = $_POST['allowance'] ?? '';

        // Validate allowance
        if (empty($allowance)) {
            $error = 'Allowance is required';
        } elseif (!is_numeric($allowance)) {
            $error = 'Allowance must be a number';
        } elseif ($allowance <= 0) {
            $error = 'Allowance must be greater than 0';
        } elseif ($allowance >= 100000) {
            $error = 'Allowance must be less than 100,000';
        } else {
            try {
                // Update user's monthly allowance
                $stmt = $pdo->prepare('UPDATE users SET monthly_allowance = :allowance WHERE id = :user_id');
                $stmt->execute([
                    ':allowance' => floatval($allowance),
                    ':user_id' => $user_id
                ]);

                // Move to step 2
                $_SESSION['onboarding_step'] = 2;
                $current_step = 2;
            } catch (PDOException $e) {
                $error = 'Failed to save allowance. Please try again.';
            }
        }
    }

    // STEP 2: Select Categories
    elseif ($action === 'select_categories') {
        $selected_categories = $_POST['categories'] ?? [];

        // Validate at least one category selected
        if (empty($selected_categories)) {
            $error = 'Please select at least one category';
        } else {
            try {
                // Get all default categories for this user
                $stmt = $pdo->prepare('SELECT id FROM categories WHERE user_id = :user_id AND is_default = 1');
                $stmt->execute([':user_id' => $user_id]);
                $all_categories = $stmt->fetchAll();
                $all_category_ids = array_column($all_categories, 'id');

                // Note: In this simple implementation, we don't actually soft-disable unselected categories
                // All 9 defaults remain available. Selected status is just for UX.
                // Save selected categories to session for step 3 reference
                $_SESSION['selected_categories'] = array_map('intval', $selected_categories);

                // Move to step 3
                $_SESSION['onboarding_step'] = 3;
                $current_step = 3;
            } catch (PDOException $e) {
                $error = 'Failed to save categories. Please try again.';
            }
        }
    }

    // STEP 3: Set Budget or Skip
    elseif ($action === 'set_budget' || $action === 'skip_budget') {
        try {
            // If setting budget, validate and insert
            if ($action === 'set_budget') {
                $category_id = $_POST['category_id'] ?? '';
                $amount = $_POST['amount'] ?? '';
                $period = $_POST['period'] ?? 'monthly';

                // Validate inputs
                if (empty($category_id)) {
                    $error = 'Please select a category';
                } elseif (!validateAmount($amount)) {
                    $error = 'Please enter a valid budget amount';
                } else {
                    // Verify category belongs to user
                    $stmt = $pdo->prepare('SELECT id FROM categories WHERE id = :category_id AND user_id = :user_id');
                    $stmt->execute([
                        ':category_id' => intval($category_id),
                        ':user_id' => $user_id
                    ]);

                    if (!$stmt->fetch()) {
                        $error = 'Invalid category selected';
                    } else {
                        // Attempt to insert budget
                        try {
                            $stmt = $pdo->prepare('
                                INSERT INTO budgets (user_id, category_id, amount, period)
                                VALUES (:user_id, :category_id, :amount, :period)
                            ');
                            $stmt->execute([
                                ':user_id' => $user_id,
                                ':category_id' => intval($category_id),
                                ':amount' => floatval($amount),
                                ':period' => $period
                            ]);
                        } catch (PDOException $e) {
                            // Check if it's a duplicate budget error
                            if (strpos($e->getMessage(), 'unique_budget') !== false) {
                                $error = 'You already have a budget for this category in this period';
                            } else {
                                $error = 'Failed to save budget. Please try again.';
                            }
                        }
                    }
                }
            }

            // If no error, mark onboarding as complete
            if (empty($error)) {
                // Mark onboarding complete
                $stmt = $pdo->prepare('UPDATE users SET onboarding_complete = 1 WHERE id = :user_id');
                $stmt->execute([':user_id' => $user_id]);

                // Update session
                $_SESSION['onboarding_complete'] = 1;

                // Redirect to dashboard
                header('Location: ../pages/dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'An error occurred. Please try again.';
        }
    }

    // Go back to previous step
    elseif ($action === 'back') {
        if ($current_step > 1) {
            $_SESSION['onboarding_step'] = $current_step - 1;
            $current_step = $current_step - 1;
        }
    }
}

// Get user's current allowance for pre-fill
$stmt = $pdo->prepare('SELECT monthly_allowance FROM users WHERE id = :user_id');
$stmt->execute([':user_id' => $user_id]);
$user_data = $stmt->fetch();
$current_allowance = $user_data['monthly_allowance'] ?? 3000;

// Get all default categories for step 2
$stmt = $pdo->prepare('SELECT id, name, icon FROM categories WHERE user_id = :user_id AND is_default = 1 ORDER BY name');
$stmt->execute([':user_id' => $user_id]);
$categories = $stmt->fetchAll();

// Get selected categories for reference
$selected_categories = $_SESSION['selected_categories'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Birr Wise</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
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
            font-family: 'Orbitron', monospace;
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

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 32px;
        }

        .step-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--border);
            transition: background-color 0.3s;
        }

        .step-dot.active {
            background-color: var(--primary);
        }

        .step-dot.completed {
            background-color: var(--success);
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
        }

        .title {
            font-family: 'Orbitron', monospace;
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--text-muted);
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

        input[type="number"],
        input[type="text"],
        select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 16px;
            font-family: 'Orbitron', monospace;
            transition: border-color 0.3s;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .input-with-suffix {
            position: relative;
        }

        .suffix {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-weight: 500;
            pointer-events: none;
            margin-top: 12px;
        }

        .input-with-suffix input {
            padding-right: 40px;
        }

        .category-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .category-checkbox {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .category-checkbox:hover {
            border-color: var(--primary);
            background-color: #F9F9F9;
        }

        .category-checkbox input {
            width: 0;
            height: 0;
            opacity: 0;
            position: absolute;
        }

        .category-checkbox input:checked + .category-label {
            color: var(--primary);
        }

        .category-checkbox input:checked ~ .category-icon {
            transform: scale(1.1);
        }

        .category-checkbox.selected {
            border-color: var(--primary);
            background-color: #E8F5E9;
        }

        .category-icon {
            font-size: 20px;
            margin-right: 8px;
            transition: transform 0.3s;
        }

        .category-label {
            font-size: 13px;
            font-weight: 500;
            transition: color 0.3s;
        }

        .period-toggle {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
        }

        .period-option {
            flex: 1;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            background: white;
            transition: all 0.3s;
        }

        .period-option:hover {
            border-color: var(--primary);
        }

        .period-option.selected {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .period-option input {
            display: none;
        }

        .button-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            flex: 1;
        }

        .btn-primary:hover {
            background-color: var(--primary-light);
        }

        .btn-secondary {
            background-color: transparent;
            border: 2px solid var(--border);
            color: var(--text);
        }

        .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-full {
            width: 100%;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 12px;
        }

        @media (min-width: 768px) {
            .card {
                padding: 40px;
            }

            .category-grid {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step-dot <?php echo $current_step >= 1 ? 'active' : ''; ?>"></div>
                <div class="step-dot <?php echo $current_step >= 2 ? 'active' : ''; ?>"></div>
                <div class="step-dot <?php echo $current_step >= 3 ? 'active' : ''; ?>"></div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error show">
                    <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- STEP 1: Allowance -->
            <div class="step-content <?php echo $current_step === 1 ? 'active' : ''; ?>">
                <div class="header">
                    <div class="title">What's your monthly allowance?</div>
                    <p class="subtitle">Most students receive 3000 ETB from the government</p>
                </div>

                <form method="POST" action="" id="allowanceForm">
                    <div class="form-group">
                        <label for="allowance">Monthly Allowance</label>
                        <div class="input-with-suffix">
                            <input 
                                type="number" 
                                id="allowance" 
                                name="allowance" 
                                value="<?php echo htmlspecialchars($current_allowance, ENT_QUOTES, 'UTF-8'); ?>"
                                min="1" 
                                max="99999"
                                required
                            >
                            <span class="suffix">ETB</span>
                        </div>
                    </div>
                    <input type="hidden" name="action" value="set_allowance">
                    <button type="submit" class="btn btn-primary btn-full">Next →</button>
                </form>
            </div>

            <!-- STEP 2: Categories -->
            <div class="step-content <?php echo $current_step === 2 ? 'active' : ''; ?>">
                <div class="header">
                    <div class="title">What do you spend money on?</div>
                    <p class="subtitle">Pick at least one category to track</p>
                </div>

                <form method="POST" action="" id="categoriesForm">
                    <div class="category-grid">
                        <?php foreach ($categories as $category): ?>
                            <label class="category-checkbox <?php echo in_array($category['id'], $selected_categories) ? 'selected' : ''; ?>">
                                <input 
                                    type="checkbox" 
                                    name="categories[]" 
                                    value="<?php echo $category['id']; ?>"
                                    <?php echo in_array($category['id'], $selected_categories) ? 'checked' : ''; ?>
                                >
                                <span class="category-icon"><?php echo htmlspecialchars($category['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="category-label"><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <input type="hidden" name="action" value="select_categories">
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="goBack()">← Back</button>
                        <button type="submit" class="btn btn-primary">Next →</button>
                    </div>
                </form>

                <script>
                    // Update checkbox styling when clicked
                    document.querySelectorAll('.category-checkbox').forEach(label => {
                        label.addEventListener('change', function() {
                            this.classList.toggle('selected');
                        });
                    });

                    // Validate at least one is selected before submit
                    document.getElementById('categoriesForm').addEventListener('submit', function(e) {
                        const checked = document.querySelectorAll('input[name="categories[]"]:checked').length;
                        if (checked === 0) {
                            e.preventDefault();
                            alert('Please select at least one category');
                        }
                    });
                </script>
            </div>

            <!-- STEP 3: Budget -->
            <div class="step-content <?php echo $current_step === 3 ? 'active' : ''; ?>">
                <div class="header">
                    <div class="title">Set a spending limit</div>
                    <p class="subtitle">You can always change this later</p>
                </div>

                <form method="POST" action="" id="budgetForm">
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">Select a category</option>
                            <?php 
                            // Get selected categories to show in dropdown
                            $stmt = $pdo->prepare('
                                SELECT id, name, icon FROM categories 
                                WHERE user_id = :user_id AND is_default = 1 
                                ORDER BY name
                            ');
                            $stmt->execute([':user_id' => $user_id]);
                            $available_categories = $stmt->fetchAll();
                            
                            foreach ($available_categories as $cat):
                            ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo htmlspecialchars($cat['icon'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="amount">Budget Amount</label>
                        <div class="input-with-suffix">
                            <input 
                                type="number" 
                                id="amount" 
                                name="amount" 
                                placeholder="0"
                                min="1" 
                                max="99999"
                            >
                            <span class="suffix">ETB</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Budget Period</label>
                        <div class="period-toggle">
                            <label class="period-option">
                                <input type="radio" name="period" value="monthly" checked>
                                Monthly
                            </label>
                            <label class="period-option">
                                <input type="radio" name="period" value="weekly">
                                Weekly
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="action" value="set_budget">
                    <div class="button-group">
                        <button type="button" class="btn btn-secondary" onclick="goBack()">← Back</button>
                        <button type="submit" class="btn btn-primary">Finish Setup</button>
                    </div>
                </form>

                <form method="POST" action="" style="margin-top: 12px;">
                    <input type="hidden" name="action" value="skip_budget">
                    <button type="submit" class="btn btn-secondary btn-full">Skip for now</button>
                </form>

                <script>
                    // Style period toggle buttons
                    document.querySelectorAll('.period-option').forEach(option => {
                        const radio = option.querySelector('input[type="radio"]');
                        if (radio.checked) {
                            option.classList.add('selected');
                        }
                        option.addEventListener('click', function() {
                            document.querySelectorAll('.period-option').forEach(o => o.classList.remove('selected'));
                            this.classList.add('selected');
                            radio.checked = true;
                        });
                    });
                </script>
            </div>
        </div>
    </div>

    <script>
        /**
         * Navigate back to previous step
         */
        function goBack() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="back">';
            document.body.appendChild(form);
            form.submit();
        }

        /**
         * Validate allowance form on submit
         */
        const allowanceForm = document.getElementById('allowanceForm');
        if (allowanceForm) {
            allowanceForm.addEventListener('submit', function(e) {
                const allowance = document.getElementById('allowance').value;
                if (!allowance || isNaN(allowance) || allowance <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid allowance amount');
                }
            });
        }
    </script>
</body>
</html>
