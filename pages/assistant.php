<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$user_name = $_SESSION['user_name'] ?? 'Student';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Assistant — Birr Wise</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="top-bar">
    <h1>🇪🇹 Birr Wise</h1>
    <a href="/pages/profile.php"><i class="fa fa-user-circle"></i></a>
  </div>

  <div class="page-wrapper">
    <div class="container">
      <div class="page-header">
        <h2>Assistant</h2>
        <p>Ask Birr Wise for budget help, spending advice, and student money tips.</p>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Ask a question</h3>
        </div>
        <form id="assistant-form">
          <div class="form-group">
            <label for="assistant-prompt">What would you like help with?</label>
            <textarea id="assistant-prompt" class="form-control" rows="4" placeholder="e.g. How can I save on campus food this month?" required></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-full" id="assistant-send">Send</button>
        </form>
      </div>

      <div id="assistant-messages" class="card section">
        <div class="card-header">
          <h3>Conversation</h3>
        </div>
        <div id="assistant-history" class="assistant-history">
          <div class="assistant-empty">
            <span class="emoji">🤖</span>
            <p>Ask Birr Wise a question to get started.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <nav class="bottom-nav">
    <a href="/pages/dashboard.php"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
    <a href="/pages/expenses.php"><i class="fa fa-wallet"></i><span>Expenses</span></a>
    <a href="/pages/categories.php"><i class="fa fa-list"></i><span>Categories</span></a>
    <a href="/pages/budgets.php"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
    <a href="/pages/profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
  </nav>

  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/assistant.js"></script>
</body>
</html>
