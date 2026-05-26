<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error      = '';
$full_name  = '';
$email      = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $full_name        = trim(strip_tags($_POST['full_name'] ?? ''));
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif (strlen($full_name) < 2 || strlen($full_name) > 100) {
            $error = 'Full name must be between 2 and 100 characters.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $pdo  = getDB();
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);

                if ($stmt->fetch()) {
                    $error = 'This email is already registered.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare('INSERT INTO users (name, email, password, monthly_allowance, onboarding_complete) VALUES (:name, :email, :password, :allowance, 0)')
                        ->execute([':name' => $full_name, ':email' => $email, ':password' => $hash, ':allowance' => DEFAULT_ALLOWANCE]);

                    $user_id = $pdo->lastInsertId();

                    $default_categories = [
                        ['Food','🍛'], ['Transport','🚌'], ['Clothing','👕'],
                        ['School Supplies','📚'], ['Mobile Top-up','📱'], ['Café','☕'],
                        ['Entertainment','🎮'], ['Health','🏥'], ['Other','🔀']
                    ];
                    $stmt = $pdo->prepare('INSERT INTO categories (user_id, name, icon, is_default) VALUES (:user_id, :name, :icon, 1)');
                    foreach ($default_categories as [$name, $icon]) {
                        $stmt->execute([':user_id' => $user_id, ':name' => $name, ':icon' => $icon]);
                    }

                    session_regenerate_id(true);
                    $_SESSION['user_id']             = $user_id;
                    $_SESSION['user_name']           = $full_name;
                    $_SESSION['onboarding_complete'] = 0;

                    header('Location: onboarding.php');
                    exit;
                }
            } catch (PDOException $e) {
                $error = 'A server error occurred. Please try again later.';
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
    <title>Create Account — Birr Wise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #f0f2f5;
            --card:     #ffffff;
            --primary:  #000000;
            --primary-h:#222222;
            --danger:   #cc0000;
            --text:     #0f1419;
            --muted:    #536471;
            --border:   #cfd9de;
            --input-bg: #f7f9f9;
            --shadow:   0 8px 40px rgba(0,0,0,.14), 0 2px 8px rgba(0,0,0,.08);
        }

        body {
            font-family: 'Orbitron', monospace;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .card {
            background: var(--card);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 48px 44px 40px;
            width: 100%;
            max-width: 400px;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 28px;
        }
        .logo-icon {
            width: 42px; height: 42px;
            background: #000;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,.25);
            flex-shrink: 0;
        }
        .logo-icon img {
            width: 28px;
            height: 28px;
            object-fit: contain;
            display: block;
        }
        .logo-text {
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -.4px;
        }

        h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--text);
            text-align: center;
            margin-bottom: 6px;
            letter-spacing: -.5px;
        }
        .sub {
            text-align: center;
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 32px;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #fff0f3;
            border: 1px solid #fcc;
            border-left: 4px solid var(--danger);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 22px;
            font-size: 13.5px;
            color: var(--danger);
            animation: shake .4s ease;
        }
        .alert svg { flex-shrink: 0; margin-top: 1px; }

        @keyframes shake {
            0%,100%{ transform:translateX(0) }
            20%    { transform:translateX(-6px) }
            40%    { transform:translateX(6px) }
            60%    { transform:translateX(-4px) }
            80%    { transform:translateX(4px) }
        }

        .field { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 7px;
        }

        .input-wrap { position: relative; }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 13px 16px;
            background: var(--input-bg);
            border: 1.5px solid var(--border);
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            color: var(--text);
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0,0,0,.1);
            background: #fff;
        }
        input.is-error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(224,36,94,.1);
        }
        input.has-toggle { padding-right: 46px; }

        .field-error {
            font-size: 12px;
            color: var(--danger);
            margin-top: 5px;
            display: none;
        }
        .field-error.show { display: block; }

        /* ── Eye toggle — perfectly centered ── */
        .eye-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: var(--muted);
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: color .2s;
        }
        .eye-btn:hover { color: var(--text); }
        .eye-btn .eye-off { display: none; }
        .eye-btn.visible .eye-on  { display: none; }
        .eye-btn.visible .eye-off { display: block; }

        /* ── Password strength bar ── */
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: var(--border);
            margin-top: 8px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: width .3s, background .3s;
        }
        .strength-label {
            font-size: 11px;
            color: var(--muted);
            margin-top: 4px;
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: background .2s, transform .1s, box-shadow .2s;
            box-shadow: 0 4px 14px rgba(0,0,0,.2);
            letter-spacing: .2px;
            margin-top: 6px;
        }
        .btn:hover  { background: #222; box-shadow: 0 6px 18px rgba(0,0,0,.28); }
        .btn:active { transform: scale(.98); }
        .btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--muted);
            font-size: 12px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .footer {
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }
        .footer a {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
        }
        .footer a:hover { text-decoration: underline; }

        @media (max-width: 480px) {
            .card { padding: 36px 24px 32px; }
            h1 { font-size: 22px; }
        }
    </style>
</head>
<body>
<div class="card">

    <div class="logo">
        <div class="logo-icon"><img src="/assets/icon/maex.png" alt="Birr Wise" class="logo-img"></div>
        <span class="logo-text">Birr Wise</span>
    </div>

    <h1>Create account</h1>
    <p class="sub">Start tracking your spending today.</p>

    <?php if (!empty($error)): ?>
    <div class="alert" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <form id="registerForm" method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <!-- Full Name -->
        <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name"
                placeholder=""
                value="<?= htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="name" required>
            <div class="field-error" id="nameErr">Please enter your full name.</div>
        </div>

        <!-- Email -->
        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email"
                placeholder=""
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="email" required>
            <div class="field-error" id="emailErr">Please enter a valid email address.</div>
        </div>

        <!-- Password -->
        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input type="password" id="password" name="password"
                    placeholder="Min. 8 characters with a number"
                    class="has-toggle" autocomplete="new-password" required>
                <button type="button" class="eye-btn" data-target="password" aria-label="Toggle password visibility">
                    <svg class="eye-on" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <div class="strength-label" id="strengthLabel"></div>
            <div class="field-error" id="passErr">Min. 8 characters including a number.</div>
        </div>

        <!-- Confirm Password -->
        <div class="field">
            <label for="confirm_password">Confirm password</label>
            <div class="input-wrap">
                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="Repeat your password"
                    class="has-toggle" autocomplete="new-password" required>
                <button type="button" class="eye-btn" data-target="confirm_password" aria-label="Toggle confirm password visibility">
                    <svg class="eye-on" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                    <svg class="eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <div class="field-error" id="confirmErr">Passwords do not match.</div>
        </div>

        <button type="submit" class="btn" id="submitBtn">Create account</button>
    </form>

    <div class="divider">or</div>

    <div class="footer">
        Already have an account? <a href="login.php">Sign in</a>
    </div>
</div>

<script>
    // ── Eye toggles ─────────────────────────────────────────
    document.querySelectorAll('.eye-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.classList.toggle('visible', isHidden);
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });

    // ── Password strength ────────────────────────────────────
    const passInput    = document.getElementById('password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthLabel = document.getElementById('strengthLabel');

    const levels = [
        { test: p => p.length >= 8 && /[0-9]/.test(p) && /[A-Z]/.test(p) && /[^a-zA-Z0-9]/.test(p), label: 'Strong',  color: '#000000', width: '100%' },
        { test: p => p.length >= 8 && /[0-9]/.test(p) && /[A-Z]/.test(p),                             label: 'Good',    color: '#555555', width: '66%'  },
        { test: p => p.length >= 8 && /[0-9]/.test(p),                                                 label: 'Weak',    color: '#cc0000', width: '33%'  },
    ];

    passInput.addEventListener('input', () => {
        const v = passInput.value;
        if (!v) { strengthFill.style.width = '0'; strengthLabel.textContent = ''; return; }
        const lvl = levels.find(l => l.test(v)) || { label: 'Too weak', color: '#cc0000', width: '10%' };
        strengthFill.style.width    = lvl.width;
        strengthFill.style.background = lvl.color;
        strengthLabel.textContent   = lvl.label;
        strengthLabel.style.color   = lvl.color;
    });

    // ── Validation ───────────────────────────────────────────
    const form        = document.getElementById('registerForm');
    const nameIn      = document.getElementById('full_name');
    const emailIn     = document.getElementById('email');
    const confirmIn   = document.getElementById('confirm_password');
    const submitBtn   = document.getElementById('submitBtn');

    const nameErr    = document.getElementById('nameErr');
    const emailErr   = document.getElementById('emailErr');
    const passErr    = document.getElementById('passErr');
    const confirmErr = document.getElementById('confirmErr');

    function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
    function validatePass(v)  { return v.length >= 8 && /[0-9]/.test(v); }

    function setError(input, errEl, show) {
        input.classList.toggle('is-error', show);
        errEl.classList.toggle('show', show);
    }

    form.addEventListener('submit', e => {
        let ok = true;

        if (nameIn.value.trim().length < 2)          { setError(nameIn, nameErr, true);       ok = false; } else setError(nameIn, nameErr, false);
        if (!validateEmail(emailIn.value.trim()))     { setError(emailIn, emailErr, true);     ok = false; } else setError(emailIn, emailErr, false);
        if (!validatePass(passInput.value))           { setError(passInput, passErr, true);    ok = false; } else setError(passInput, passErr, false);
        if (confirmIn.value !== passInput.value)      { setError(confirmIn, confirmErr, true); ok = false; } else setError(confirmIn, confirmErr, false);

        if (!ok) { e.preventDefault(); return; }
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating account…';
    });

    // Live clear
    [nameIn, emailIn, passInput, confirmIn].forEach(input => {
        input.addEventListener('input', () => input.classList.remove('is-error'));
    });
</script>
</body>
</html>
