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
  <title>Analytics — Birr Wise</title>
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
        <h2>Analytics</h2>
        <p>Understand your spending habits and measure progress.</p>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Weekly spending</h3>
        </div>
        <div id="weekly-trend" class="grid grid-1 gap-12"></div>
        <div id="weekly-empty" class="empty-state hidden">
          <span class="emoji">📉</span>
          <p>Spend something this week to see trend data.</p>
        </div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Category breakdown</h3>
        </div>
        <div id="category-breakdown" class="grid grid-1 gap-12"></div>
        <div id="categories-empty" class="empty-state hidden">
          <span class="emoji">📦</span>
          <p>No category spending recorded for this month yet.</p>
        </div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Budget health</h3>
        </div>
        <div id="budget-usage" class="grid grid-1 gap-12"></div>
        <div id="budget-empty" class="empty-state hidden">
          <span class="emoji">🎯</span>
          <p>Create a budget to monitor category spending against limits.</p>
        </div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Forecast</h3>
        </div>
        <div class="flex-between mb-12">
          <div>
            <h4>Projected remaining</h4>
            <p id="projected-remaining">– ETB</p>
          </div>
          <div>
            <h4>Pace status</h4>
            <p id="forecast-pace" class="text-muted">Loading pace...</p>
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
  <script src="/assets/js/analytics.js"></script>
</body>
</html>
