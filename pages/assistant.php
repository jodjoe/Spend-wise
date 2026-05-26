<?php
require_once '../includes/session.php';
$first_name = explode(' ', $_SESSION['user_name'] ?? 'Student')[0];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>AI Assistant — Birr Wise</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    html, body { height: 100%; margin: 0; }
    body {
      min-height: 100%;
      display: flex;
      flex-direction: column;
      background: #050505;
      color: #f3f4f6;
      font-family: 'Orbitron', monospace;
    }

    .app-shell {
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      overflow: hidden;
      background: #050505;
    }

    .chat-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 0 18px;
      height: 68px;
      background: #111111;
      border-bottom: 1px solid #222222;
      box-shadow: 0 12px 30px rgba(0,0,0,.45);
      z-index: 10;
      flex-shrink: 0;
      font-family: 'Orbitron', monospace;
    }

    .chat-header-back {
      width: 40px;
      height: 40px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      text-decoration: none;
      border-radius: 14px;
      transition: color .2s, background .2s;
    }
    .chat-header-back:hover {
      color: var(--text);
      background: rgba(99,102,241,.08);
    }

    .chat-header-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: white;
      font-size: 18px;
      box-shadow: 0 4px 14px rgba(99,102,241,.16);
      flex-shrink: 0;
    }

    .chat-header-info {
      flex: 1;
      min-width: 0;
    }
    .chat-header-name {
      font-size: 15px;
      font-weight: 700;
      line-height: 1.2;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .chat-header-status {
      margin-top: 3px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      color: #22c55e;
    }
    .chat-header-status::before {
      content: '';
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #22c55e;
      display: inline-block;
    }

    .chat-header-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .chat-body {
      flex: 1;
      overflow-y: auto;
      padding: 22px 16px 0;
      display: flex;
      flex-direction: column;
      gap: 18px;
      scroll-behavior: smooth;
      background: radial-gradient(circle at top, rgba(96,165,250,.08), transparent 30%),
                  linear-gradient(180deg, #060606, #070707 55%, #0f172a 100%);
      box-shadow: inset 0 30px 100px rgba(0,0,0,.55);
      font-family: 'Orbitron', monospace;
    }

    .chat-inner {
      width: 100%;
      max-width: 960px;
      margin: 0 auto;
      display: grid;
      gap: 18px;
      justify-items: center;
      text-align: center;
    }

    .chat-date-divider {
      position: relative;
      text-align: center;
      font-size: 11px;
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: .5px;
    }
    .chat-date-divider::before,
    .chat-date-divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 30%;
      height: 1px;
      background: var(--border);
    }
    .chat-date-divider::before { left: 0; }
    .chat-date-divider::after { right: 0; }

    .welcome-card {
      background: #111111;
      border-radius: 22px;
      padding: 24px;
      box-shadow: 0 24px 80px rgba(0,0,0,.55);
      color: #f8fafc;
      border: 1px solid rgba(148,163,184,.08);
    }
    .welcome-card .welcome-icon {
      width: 48px;
      height: 48px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
      border-radius: 16px;
      background: rgba(255,255,255,.18);
      font-size: 26px;
    }
    .welcome-card h3 {
      font-size: 18px;
      font-weight: 700;
      margin: 0 0 10px;
    }
    .welcome-card p {
      margin: 0;
      font-size: 14px;
      line-height: 1.65;
      opacity: .95;
    }

    .suggestions-wrap {
      display: grid;
      gap: 12px;
    }
    .suggestions-label {
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .4px;
      color: var(--text-muted);
    }
    .chips-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .chip {
      appearance: none;
      border: 1px solid rgba(148,163,184,.2);
      background: #141414;
      color: #e2e8f0;
      padding: 10px 14px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: transform .18s, background .18s, border-color .18s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    .chip:hover {
      transform: translateY(-1px);
      border-color: #6366f1;
      background: rgba(99,102,241,.08);
      color: #111827;
    }
    .chip i { color: #6366f1; font-size: 12px; }

    .chat-footer {
      flex-shrink: 0;
      background: #111111;
      border-top: 1px solid #222222;
      padding: 14px 16px 18px;
    }

    .input-row {
      display: flex;
      gap: 10px;
      align-items: flex-end;
      border: 1.5px solid rgba(148,163,184,.18);
      border-radius: 28px;
      padding: 10px 12px;
      background: #121212;
      transition: border-color .2s, box-shadow .2s;
    }
    .input-row:focus-within {
      border-color: #6366f1;
      box-shadow: 0 0 0 4px rgba(99,102,241,.12);
    }

    .chat-textarea {
      width: 100%;
      min-height: 34px;
      max-height: 80px;
      resize: none;
      border: none;
      background: transparent;
      outline: none;
      font-family: inherit;
      font-size: 14px;
      color: #e5e7eb;
      line-height: 1.6;
      padding: 0;
    }
    .chat-textarea::placeholder { color: var(--text-muted); }

    .send-btn {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      border: none;
      background: #6366f1;
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      transition: background .2s, transform .15s;
      flex-shrink: 0;
    }
    .send-btn:hover:not(:disabled) { background: #4f46e5; transform: scale(1.05); }
    .send-btn:disabled { background: var(--border); cursor: not-allowed; }

    .chat-hint-text {
      margin-top: 10px;
      font-size: 12px;
      color: var(--text-muted);
      text-align: center;
    }

    .chat-body::-webkit-scrollbar { width: 5px; }
    .chat-body::-webkit-scrollbar-track { background: transparent; }
    .chat-body::-webkit-scrollbar-thumb { background: rgba(0,0,0,.1); border-radius: 999px; }

    @media (min-width: 640px) {
      .chat-body { padding: 28px 24px 0; }
      .chat-footer { padding: 18px 24px 22px; }
      .chat-inner { gap: 20px; }
      .msg-bubble { max-width: 58%; }
    }
  </style>
</head>
<body>
  <div class="app-shell">
    <header class="chat-header">
      <a href="/pages/dashboard.php" class="chat-header-back" aria-label="Back to dashboard"><i class="fa fa-arrow-left"></i></a>
      <div class="chat-header-avatar">🤖</div>
      <div class="chat-header-info">
        <div class="chat-header-name">Birr Wise AI</div>
        <div class="chat-header-status">Online</div>
      </div>
      <div class="chat-header-actions">
        <button class="theme-toggle" title="Toggle theme"><i class="fa fa-moon"></i></button>
      </div>
    </header>

    <main class="chat-body" id="chat-messages">
      <div class="chat-inner">
        <div class="chat-date-divider">Today</div>

        <section class="welcome-card">
          <h3>Hi <?= htmlspecialchars($first_name, ENT_QUOTES, 'ENT_QUOTES', 'UTF-8') ?> I'm your finance assistant.</h3>
          <p>Ask me about your spending budgets or how to save money as a student in Ethiopia.</p>
        </section>

        <section class="suggestions-wrap" id="suggestions">
          <div class="suggestions-label">Try asking</div>
          <div class="chips-row">
            <button type="button" class="chip" data-msg="How am I doing with my budget this month?">
              <i class="fa fa-chart-pie"></i> Budget check
            </button>
            <button type="button" class="chip" data-msg="Where am I spending the most money?">
              <i class="fa fa-fire"></i> Top spending
            </button>
            <button type="button" class="chip" data-msg="Give me 5 tips to save money on food as a student">
              <i class="fa fa-lightbulb"></i> Save on food
            </button>
            <button type="button" class="chip" data-msg="How do I set a weekly budget in Birr Wise?">
              <i class="fa fa-circle-question"></i> How to budget
            </button>
            <button type="button" class="chip" data-msg="What is Telebirr and how do I use it wisely?">
              <i class="fa fa-mobile"></i> Telebirr tips
            </button>
            <button type="button" class="chip" data-msg="Am I on track to finish the month within my allowance?">
              <i class="fa fa-gauge-high"></i> Pace check
            </button>
          </div>
        </section>
      </div>
    </main>

    <footer class="chat-footer">
      <div class="input-row">
        <textarea
          id="chat-input"
          class="chat-textarea"
          placeholder="Ask about your finances…"
          rows="1"
        ></textarea>
        <button class="send-btn" id="chat-send" disabled>
          <i class="fa fa-paper-plane"></i>
        </button>
      </div>
      <p class="chat-hint-text">Powered by Gemini. If Gemini does not respond, try a free open-source alternative such as Hugging Face Inference models or a local Llama-based runtime.</p>
    </footer>

    <nav class="bottom-nav">
      <a href="/pages/dashboard.php"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
      <a href="/pages/expenses.php"><i class="fa fa-wallet"></i><span>Expenses</span></a>
      <a href="/pages/categories.php"><i class="fa fa-list"></i><span>Categories</span></a>
      <a href="/pages/budgets.php"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
      <a href="/pages/profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
    </nav>
  </div>

  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/assistant.js"></script>
  <script src="/assets/js/theme.js"></script>
</body>
</html>
