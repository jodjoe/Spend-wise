<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$user_name = $_SESSION['user_name'] ?? 'Student';
$overview_month = date('F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Dashboard — Birr Wise</title>
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
        <h2>Dashboard</h2>
        <p>Welcome back, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>. Review your budget health and expense trends.</p>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Monthly overview</h3>
          <span class="text-muted"><?php echo $overview_month; ?></span>
        </div>
        <div class="grid grid-2 gap-12">
          <div class="card metric-card">
            <h4>Allowance</h4>
            <p id="dashboard-allowance">– ETB</p>
          </div>
          <div class="card metric-card">
            <h4>Spent this month</h4>
            <p id="dashboard-spent">– ETB</p>
          </div>
          <div class="card metric-card">
            <h4>Remaining</h4>
            <p id="dashboard-remaining">– ETB</p>
          </div>
          <div class="card metric-card">
            <h4>Today left</h4>
            <p id="dashboard-today">– ETB</p>
          </div>
        </div>

        <div class="mb-16">
          <div class="flex-between mb-8">
            <span>Month spent</span>
            <span id="dashboard-spent-percent">0%</span>
          </div>
          <div class="progress-bar-wrap">
            <div id="dashboard-progress" class="progress-bar green" style="width: 0%;"></div>
          </div>
        </div>

        <div class="card">
          <div class="flex-between mb-12">
            <div>
              <h4>Pace status</h4>
              <p id="dashboard-pace" class="text-muted">Loading current pace...</p>
            </div>
            <div id="dashboard-prediction" class="text-muted">Projected month end: – ETB</div>
          </div>
        </div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Top categories</h3>
          <a href="/pages/analytics.php" class="btn btn-outline">View analytics</a>
        </div>
        <div id="top-categories-list" class="grid grid-3 gap-12"></div>
        <div id="top-categories-empty" class="empty-state hidden">
          <span class="emoji">📊</span>
          <p>No spending categories found for this month yet.</p>
        </div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Recent expenses</h3>
        </div>
        <div id="recent-expenses-list"></div>
        <div id="recent-expenses-empty" class="empty-state hidden">
          <span class="emoji">🧾</span>
          <p>No recent expenses yet. Add an expense to begin tracking.</p>
        </div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Quick actions</h3>
        </div>
        <div class="grid grid-3 gap-12">
          <a href="/pages/expenses.php" class="card card-link">
            <i class="fa fa-wallet"></i>
            <span>Expenses</span>
          </a>
          <a href="/pages/budgets.php" class="card card-link">
            <i class="fa fa-bullseye"></i>
            <span>Budgets</span>
          </a>
          <a href="/pages/categories.php" class="card card-link">
            <i class="fa fa-list"></i>
            <span>Categories</span>
          </a>
        </div>
      </div>
    </div>
  </div>

  <nav class="bottom-nav">
    <a href="/pages/dashboard.php" class="active"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
    <a href="/pages/expenses.php"><i class="fa fa-wallet"></i><span>Expenses</span></a>
    <a href="/pages/categories.php"><i class="fa fa-list"></i><span>Categories</span></a>
    <a href="/pages/budgets.php"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
    <a href="/pages/profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
  </nav>

  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/dashboard.js"></script>
</body>
</html>
