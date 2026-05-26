<?php
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// ── Preview mode: skip login entirely ──
if (!empty($_SESSION['preview_mode'])) {
    header('Location: ../pages/dashboard.php');
    exit;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ── Rate limiting ────────────────────────────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rate_key = 'login_attempts_' . md5($ip);
if (!isset($_SESSION[$rate_key])) $_SESSION[$rate_key] = ['count' => 0, 'first' => time()];

$attempts = &$_SESSION[$rate_key];
// Reset window after 15 minutes
if (time() - $attempts['first'] > 900) {
    $attempts = ['count' => 0, 'first' => time()];
}
$locked = $attempts['count'] >= 5;
$lockout_remaining = $locked ? max(0, 900 - (time() - $attempts['first'])) : 0;

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$locked) {
    // CSRF check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please refresh and try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);

        if (empty($email) || empty($password)) {
            $error = 'Email and password are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            try {
                // ── Hardcoded test account (works without DB) ──
                $test_accounts = [
                    'abebe@test.com'  => ['id'=>1,'name'=>'Abebe Kebede', 'password'=>password_hash('password',PASSWORD_DEFAULT),'onboarding_complete'=>1],
                    'tigist@test.com' => ['id'=>2,'name'=>'Tigist Haile',  'password'=>password_hash('password',PASSWORD_DEFAULT),'onboarding_complete'=>1],
                    'dawit@test.com'  => ['id'=>3,'name'=>'Dawit Mengistu','password'=>password_hash('password',PASSWORD_DEFAULT),'onboarding_complete'=>1],
                ];

                $pdo  = getDB();

                // If DB is down, fall back to test accounts
                if ($pdo === null) {
                    $user = $test_accounts[$email] ?? null;
                } else {
                    $stmt = $pdo->prepare(
                        'SELECT id, name, password, onboarding_complete FROM users WHERE email = :email LIMIT 1'
                    );
                    $stmt->execute([':email' => $email]);
                    $user = $stmt->fetch();
                }

                if (!$user || !password_verify($password, $user['password'])) {
                    $attempts['count']++;
                    $remaining = 5 - $attempts['count'];
                    $error = $remaining > 0
                        ? "Invalid email or password. {$remaining} attempt(s) left."
                        : 'Too many failed attempts. Please wait 15 minutes.';
                } else {
                    // Success — reset attempts
                    $attempts = ['count' => 0, 'first' => time()];
                    session_regenerate_id(true);

                    $_SESSION['user_id']              = $user['id'];
                    $_SESSION['user_name']            = $user['name'];
                    $_SESSION['onboarding_complete']  = $user['onboarding_complete'];

                    // If DB is down, enable preview mode so pages skip DB
                    if ($pdo === null) {
                        $_SESSION['preview_mode'] = true;
                    }

                    // Remember me — store a simple cookie (30 days)
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + 2592000, '/', '', false, true);
                        // Persist token in DB (best-effort)
                        try {
                            $pdo->prepare('UPDATE users SET remember_token = :t WHERE id = :id')
                                ->execute([':t' => hash('sha256', $token), ':id' => $user['id']]);
                        } catch (PDOException $e) { /* column may not exist yet */ }
                    }

                    header('Location: ' . ($user['onboarding_complete'] == 0
                        ? 'onboarding.php'
                        : '../pages/dashboard.php'));
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
    <title>Sign in — Birr Wise</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #f5f5f5;
            --card:      #ffffff;
            --primary:   #000000;
            --primary-h: #222222;
            --danger:    #cc0000;
            --text:      #0a0a0a;
            --muted:     #666666;
            --border:    #d0d0d0;
            --input-bg:  #f9f9f9;
            --shadow:    0 8px 40px rgba(0,0,0,.14), 0 2px 8px rgba(0,0,0,.08);
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

        /* ── Card ── */
        .card {
            background: var(--card);
            border-radius: 20px;
            box-shadow: var(--shadow);
            padding: 48px 44px 40px;
            width: 100%;
            max-width: 400px;
        }

        /* ── Logo / Header ── */
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

        /* ── Alert ── */
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

        /* ── Form ── */
        .field { margin-bottom: 18px; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 7px;
        }

        .input-wrap { position: relative; }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
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
            box-shadow: 0 0 0 3px rgba(26,138,60,.12);
            background: #fff;
        }
        input.is-error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(224,36,94,.1);
        }
        /* extra right padding for password so text doesn't hide under toggle */
        input#password { padding-right: 46px; }

        .field-error {
            font-size: 12px;
            color: var(--danger);
            margin-top: 5px;
            display: none;
        }
        .field-error.show { display: block; }

        /* ── Eye toggle ── */
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
            transition: color .2s;
        }
        .eye-btn:hover { color: var(--text); }
        .eye-btn .eye-off { display: none; }
        .eye-btn.visible .eye-on  { display: none; }
        .eye-btn.visible .eye-off { display: block; }

        /* ── Remember me ── */
        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            font-size: 13.5px;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }
        .remember input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--primary);
            cursor: pointer;
            border-radius: 4px;
            padding: 0;
            border: none;
            box-shadow: none;
        }
        .remember input:focus { box-shadow: none; }

        /* ── Submit ── */
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
        }
        .btn:hover  { background: #222; box-shadow: 0 6px 18px rgba(0,0,0,.28); }
        .btn:active { transform: scale(.98); }
        .btn:disabled { opacity: .6; cursor: not-allowed; transform: none; }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--border);
            font-size: 12px;
            color: var(--muted);
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ── Footer ── */
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

    <!-- Logo -->
    <div class="logo">
        <div class="logo-icon"><img src="/assets/icon/maex.png" alt="Birr Wise" class="logo-img"></div>
        <span class="logo-text">Birr Wise</span>
    </div>

    <h1>Sign in</h1>
    <p class="sub">Track your spending, own your future.</p>

    <!-- Server error alert -->
    <?php if (!empty($error)): ?>
    <div class="alert" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
    <?php endif; ?>

    <?php if ($locked): ?>
    <div class="alert" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Account temporarily locked. Try again in <?= ceil($lockout_remaining / 60) ?> minute(s).
    </div>
    <?php endif; ?>

    <form id="loginForm" method="POST" action="" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <!-- Email -->
        <div class="field">
            <label for="email">Email address</label>
            <input
                type="email" id="email" name="email"
                placeholder=""
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="email" required
                <?= $locked ? 'disabled' : '' ?>
            >
            <div class="field-error" id="emailErr">Please enter a valid email address.</div>
        </div>

        <!-- Password -->
        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <input
                    type="password" id="password" name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password" required
                    <?= $locked ? 'disabled' : '' ?>
                >
                <button type="button" class="eye-btn" id="eyeBtn" aria-label="Toggle password visibility">
                    <!-- Eye open -->
                    <svg class="eye-on" width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                    <!-- Eye closed (slash) -->
                    <svg class="eye-off" width="20" height="20" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                        <line x1="1" y1="1" x2="23" y2="23"/>
                    </svg>
                </button>
            </div>
            <div class="field-error" id="passErr">Password is required.</div>
        </div>

        <!-- Remember me -->
        <label class="remember">
            <input type="checkbox" name="remember" <?= $locked ? 'disabled' : '' ?>>
            Keep me signed in
        </label>

        <button type="submit" class="btn" id="submitBtn" <?= $locked ? 'disabled' : '' ?>>
            Sign in
        </button>
    </form>

    <div class="divider">or</div>

    <div class="footer">
        Don't have an account? <a href="register.php">Create one</a>
    </div>
</div>

<script>
    // ── Eye toggle ──────────────────────────────────────────
    const eyeBtn  = document.getElementById('eyeBtn');
    const passInput = document.getElementById('password');

    eyeBtn.addEventListener('click', () => {
        const isHidden = passInput.type === 'password';
        passInput.type = isHidden ? 'text' : 'password';
        eyeBtn.classList.toggle('visible', isHidden);
        eyeBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });

    // ── Client-side validation ──────────────────────────────
    const form      = document.getElementById('loginForm');
    const emailIn   = document.getElementById('email');
    const emailErr  = document.getElementById('emailErr');
    const passErr   = document.getElementById('passErr');
    const submitBtn = document.getElementById('submitBtn');

    function validateEmail(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }

    function clearErrors() {
        [emailIn, passInput].forEach(i => i.classList.remove('is-error'));
        [emailErr, passErr].forEach(e => e.classList.remove('show'));
    }

    form.addEventListener('submit', function (e) {
        clearErrors();
        let ok = true;

        if (!validateEmail(emailIn.value.trim())) {
            emailIn.classList.add('is-error');
            emailErr.classList.add('show');
            ok = false;
        }
        if (!passInput.value.trim()) {
            passInput.classList.add('is-error');
            passErr.classList.add('show');
            ok = false;
        }

        if (!ok) { e.preventDefault(); return; }

        // Loading state
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing in…';
    });

    // Live clear on input
    emailIn.addEventListener('input', () => {
        emailIn.classList.remove('is-error');
        emailErr.classList.remove('show');
    });
    passInput.addEventListener('input', () => {
        passInput.classList.remove('is-error');
        passErr.classList.remove('show');
    });
</script>
</body>
</html>
