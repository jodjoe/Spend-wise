<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$user_name     = $_SESSION['user_name'] ?? 'Student';
$first_name    = explode(' ', $user_name)[0];
$overview_month = date('F Y');
$greeting      = (date('H') < 12) ? 'Good morning' : ((date('H') < 17) ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Dashboard — Birr Wise</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="app-body">

  <!-- ═══════════════════════════════════════════
       SIDEBAR
  ════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
      <img src="/assets/icon/maex.png" alt="Birr Wise" class="sidebar-logo">
      <span class="sidebar-brand-name">Birr Wise</span>
    </div>

    <!-- Nav links -->
    <nav class="sidebar-nav">
      <a href="/pages/dashboard.php" class="sidebar-link active">
        <i class="fa fa-chart-pie"></i>
        <span>Dashboard</span>
      </a>
      <a href="/pages/expenses.php" class="sidebar-link">
        <i class="fa fa-wallet"></i>
        <span>Expenses</span>
      </a>
      <a href="/pages/categories.php" class="sidebar-link">
        <i class="fa fa-list"></i>
        <span>Categories</span>
      </a>
      <a href="/pages/budgets.php" class="sidebar-link">
        <i class="fa fa-bullseye"></i>
        <span>Budgets</span>
      </a>
      <a href="/pages/analytics.php" class="sidebar-link">
        <i class="fa fa-chart-bar"></i>
        <span>Analytics</span>
      </a>
      <a href="/pages/history.php" class="sidebar-link">
        <i class="fa fa-clock-rotate-left"></i>
        <span>History</span>
      </a>
      <a href="/pages/assistant.php" class="sidebar-link">
        <i class="fa fa-robot"></i>
        <span>AI Assistant</span>
      </a>
    </nav>

    <!-- Sidebar footer -->
    <div class="sidebar-footer">
      <a href="/pages/profile.php" class="sidebar-link">
        <i class="fa fa-user"></i>
        <span>Profile</span>
      </a>
      <a href="/auth/logout.php" class="sidebar-link sidebar-logout">
        <i class="fa fa-right-from-bracket"></i>
        <span>Logout</span>
      </a>
    </div>
  </aside>

  <!-- ═══════════════════════════════════════════
       MAIN AREA
  ════════════════════════════════════════════ -->
  <div class="main-wrap">

    <!-- Top header bar -->
    <header class="main-header">
      <button class="sidebar-toggle" id="sidebar-toggle" title="Toggle menu">
        <i class="fa fa-bars"></i>
      </button>
      <div class="header-greeting">
        <span><?= $greeting ?>, <strong><?= htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8') ?></strong></span>
      </div>
      <div class="header-actions">
        <button class="theme-toggle" title="Toggle theme"><i class="fa fa-moon"></i></button>
        <a href="/pages/profile.php" class="header-avatar" title="Profile">
          <i class="fa fa-user"></i>
        </a>
      </div>
    </header>

    <!-- Scrollable content -->
    <main class="main-content">

      <!-- ── Page title ── -->
      <div class="dash-page-title">
        <div>
          <h2>Dashboard</h2>
          <p class="text-muted"><?= $overview_month ?> · Your financial overview</p>
        </div>
        <a href="/pages/expenses.php" class="btn-add-expense">
          <i class="fa fa-plus"></i> Add Expense
        </a>
      </div>

      <!-- ── Metric cards ── -->
      <div class="metrics-grid">
        <div class="metric-card mc-purple">
          <div class="mc-icon"><i class="fa fa-wallet"></i></div>
          <div class="mc-body">
            <p class="mc-label">Monthly Allowance</p>
            <h3 class="mc-value loading" id="dash-allowance">–</h3>
          </div>
        </div>
        <div class="metric-card mc-red">
          <div class="mc-icon"><i class="fa fa-arrow-trend-up"></i></div>
          <div class="mc-body">
            <p class="mc-label">Spent This Month</p>
            <h3 class="mc-value loading" id="dash-spent">–</h3>
          </div>
        </div>
        <div class="metric-card mc-green">
          <div class="mc-icon"><i class="fa fa-piggy-bank"></i></div>
          <div class="mc-body">
            <p class="mc-label">Remaining</p>
            <h3 class="mc-value loading" id="dash-remaining">–</h3>
          </div>
        </div>
        <div class="metric-card mc-blue">
          <div class="mc-icon"><i class="fa fa-sun"></i></div>
          <div class="mc-body">
            <p class="mc-label">Today's Limit Left</p>
            <h3 class="mc-value loading" id="dash-today">–</h3>
          </div>
        </div>
      </div>

      <!-- ── Two-column row: Progress + Quick Actions ── -->
      <div class="dash-row-2">

        <!-- Monthly progress card -->
        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fa fa-gauge-high"></i> Monthly Progress</h3>
            <span class="dash-badge" id="dash-pct">0%</span>
          </div>
          <div class="progress-track">
            <div class="progress-fill" id="dash-bar" style="width:0%"></div>
          </div>
          <div class="pace-row">
            <span class="pace-chip on_track" id="dash-pace">
              <i class="fa fa-circle-check"></i> Loading…
            </span>
            <span class="prediction-chip" id="dash-prediction">
              <i class="fa fa-chart-line"></i> Projected: –
            </span>
          </div>
        </div>

        <!-- Quick actions -->
        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fa fa-bolt"></i> Quick Actions</h3>
          </div>
          <div class="quick-grid">
            <a href="/pages/expenses.php" class="quick-btn qb-orange">
              <i class="fa fa-wallet"></i>
              <span>Expenses</span>
            </a>
            <a href="/pages/budgets.php" class="quick-btn qb-blue">
              <i class="fa fa-bullseye"></i>
              <span>Budgets</span>
            </a>
            <a href="/pages/analytics.php" class="quick-btn qb-purple">
              <i class="fa fa-chart-bar"></i>
              <span>Analytics</span>
            </a>
            <a href="/pages/categories.php" class="quick-btn qb-green">
              <i class="fa fa-list"></i>
              <span>Categories</span>
            </a>
            <a href="/pages/history.php" class="quick-btn qb-pink">
              <i class="fa fa-clock-rotate-left"></i>
              <span>History</span>
            </a>
            <a href="/pages/assistant.php" class="quick-btn qb-teal">
              <i class="fa fa-robot"></i>
              <span>AI Chat</span>
            </a>
          </div>
        </div>

      </div>

      <!-- ── Two-column row: Categories + Recent Expenses ── -->
      <div class="dash-row-2">

        <!-- Top categories -->
        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fa fa-fire"></i> Top Categories</h3>
            <a href="/pages/analytics.php" class="dash-link">View all →</a>
          </div>
          <div class="cat-grid" id="cat-grid">
            <?php for($i=0;$i<3;$i++): ?>
            <div class="cat-chip">
              <div class="skeleton" style="width:36px;height:36px;margin:0 auto 8px;"></div>
              <div class="skeleton" style="width:60%;height:10px;margin:0 auto 4px;"></div>
              <div class="skeleton" style="width:40%;height:10px;margin:0 auto;"></div>
            </div>
            <?php endfor; ?>
          </div>
          <div class="empty-state hidden" id="cat-empty">
            <p>No spending this month yet.</p>
          </div>
        </div>

        <!-- Recent expenses -->
        <div class="dash-card">
          <div class="dash-card-header">
            <h3><i class="fa fa-receipt"></i> Recent Expenses</h3>
            <a href="/pages/expenses.php" class="dash-link">See all →</a>
          </div>
          <div id="exp-list">
            <?php for($i=0;$i<4;$i++): ?>
            <div class="exp-item">
              <div class="exp-icon skeleton"></div>
              <div class="exp-info">
                <div class="skeleton" style="width:50%;height:13px;margin-bottom:6px;"></div>
                <div class="skeleton" style="width:70%;height:11px;"></div>
              </div>
              <div class="skeleton" style="width:70px;height:15px;"></div>
            </div>
            <?php endfor; ?>
          </div>
          <div class="empty-state hidden" id="exp-empty">
            <p>No expenses yet. Start tracking!</p>
          </div>
        </div>

      </div>

    </main>
  </div>

  <!-- Mobile bottom nav (phones only) -->
  <nav class="bottom-nav">
    <a href="/pages/dashboard.php" class="active"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
    <a href="/pages/expenses.php"><i class="fa fa-wallet"></i><span>Expenses</span></a>
    <a href="/pages/categories.php"><i class="fa fa-list"></i><span>Categories</span></a>
    <a href="/pages/budgets.php"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
    <a href="/pages/profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
  </nav>

  <script>window.__PREVIEW__ = <?= !empty($_SESSION['preview_mode']) ? 'true' : 'false' ?>;</script>
  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/dashboard.js"></script>
  <script src="/assets/js/theme.js"></script>
  <script>
    // Sidebar toggle for mobile/tablet
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', e => {
      if (window.innerWidth < 1024 && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  </script>
</body>
</html>
