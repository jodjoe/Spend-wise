<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Analytics — Birr Wise</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

  <div class="top-bar">
    <h1><img src="/assets/icon/maex.png" alt="Birr Wise" class="site-logo"> Birr Wise</h1>
    <div style="display:flex;align-items:center;gap:10px;">
      <button class="theme-toggle" title="Toggle theme"><i class="fa fa-moon"></i></button>
      <a href="/pages/profile.php" class="header-avatar" title="Profile"><i class="fa fa-user"></i></a>
    </div>
  </div>

  <div class="page-wrapper">
    <div class="container analytics">

      <div class="page-header">
        <a href="/pages/dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Dashboard</a>
        <h2>Analytics</h2>
        <p>Understand your spending habits and track your financial health.</p>
      </div>

      <div id="analytics-loading" class="text-center" style="padding:48px 0;">
        <div class="spinner" style="width:36px;height:36px;margin:0 auto 12px;"></div>
        <p class="text-muted">Loading your analytics…</p>
      </div>

      <div id="analytics-content" class="hidden">

        <!-- Stats -->
        <div class="analytics-stats-grid">
          <div class="stat-card">
            <div class="stat-icon"><i class="fa fa-wallet"></i></div>
            <div class="stat-body">
              <p class="stat-label">Allowance</p>
              <h3 class="stat-value" id="stat-allowance">—</h3>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon danger"><i class="fa fa-arrow-trend-up"></i></div>
            <div class="stat-body">
              <p class="stat-label">Spent</p>
              <h3 class="stat-value" id="stat-spent">—</h3>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon success"><i class="fa fa-piggy-bank"></i></div>
            <div class="stat-body">
              <p class="stat-label">Remaining</p>
              <h3 class="stat-value" id="stat-remaining">—</h3>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-icon" id="pace-icon-wrap"><i class="fa fa-gauge-high"></i></div>
            <div class="stat-body">
              <p class="stat-label">Pace</p>
              <h3 class="stat-value" id="stat-pace">—</h3>
            </div>
          </div>
        </div>

        <!-- Monthly progress -->
        <div class="card section">
          <div class="card-header">
            <h3><i class="fa fa-chart-bar"></i> Monthly Progress</h3>
            <span id="month-percent-badge" class="badge badge-green">0%</span>
          </div>
          <div class="progress-bar-wrap" style="height:14px;">
            <div class="progress-bar green" id="month-progress-bar" style="width:0%;"></div>
          </div>
          <div class="flex-between mt-8">
            <span class="text-muted" style="font-size:13px;">0 ETB</span>
            <span class="text-muted" style="font-size:13px;" id="month-allowance-label">— ETB</span>
          </div>
        </div>

        <!-- Weekly chart -->
        <div class="card section">
          <div class="card-header">
            <h3><i class="fa fa-calendar-week"></i> Last 7 Days</h3>
          </div>
          <div id="weekly-chart-wrap" style="position:relative;height:220px;">
            <canvas id="weeklyChart"></canvas>
          </div>
          <div id="weekly-empty" class="empty-state hidden">
            <p>No spending in the last 7 days.</p>
          </div>
        </div>

        <!-- Category breakdown -->
        <div class="card section">
          <div class="card-header">
            <h3><i class="fa fa-chart-pie"></i> Category Breakdown</h3>
            <span class="text-muted" style="font-size:13px;">This month</span>
          </div>
          <div id="categories-empty" class="empty-state hidden">
            <p>No category spending recorded this month.</p>
          </div>
          <div id="category-content">
            <div class="category-layout">
              <div class="chart-area" style="min-width:200px;max-width:260px;">
                <canvas id="categoryChart" style="max-width:260px;max-height:260px;"></canvas>
                <div id="category-legend" class="chart-legend"></div>
              </div>
              <div id="category-breakdown" class="category-list" style="flex:1;"></div>
            </div>
          </div>
        </div>

        <!-- Daily trend -->
        <div class="card section">
          <div class="card-header">
            <h3><i class="fa fa-chart-line"></i> Daily Trend</h3>
            <span class="text-muted" style="font-size:13px;">This month</span>
          </div>
          <div id="daily-chart-wrap" style="position:relative;height:220px;">
            <canvas id="dailyChart"></canvas>
          </div>
          <div id="daily-empty" class="empty-state hidden">
            <p>No daily spending data yet.</p>
          </div>
        </div>

        <!-- Budget health -->
        <div class="card section">
          <div class="card-header">
            <h3><i class="fa fa-bullseye"></i> Budget Health</h3>
          </div>
          <div id="budget-usage"></div>
          <div id="budget-empty" class="empty-state hidden">
            <p>Create a budget to monitor category spending against limits.</p>
            <a href="/pages/budgets.php" class="btn btn-primary" style="margin-top:12px;">
              <i class="fa fa-plus"></i> Add Budget
            </a>
          </div>
        </div>

        <!-- Forecast -->
        <div class="card section">
          <div class="card-header">
            <h3><i class="fa fa-chart-line"></i> End-of-Month Forecast</h3>
          </div>
          <div class="analytics-forecast-grid">
            <div class="forecast-item">
              <p class="stat-label">Projected Remaining</p>
              <h3 id="projected-remaining" class="stat-value">—</h3>
              <p class="text-muted" style="font-size:12px;">Based on avg daily spend</p>
            </div>
            <div class="forecast-item">
              <p class="stat-label">Projected Total</p>
              <h3 id="projected-total" class="stat-value">—</h3>
              <p class="text-muted" style="font-size:12px;">If current pace continues</p>
            </div>
            <div class="forecast-item">
              <p class="stat-label">Avg. Daily Spend</p>
              <h3 id="avg-daily" class="stat-value">—</h3>
              <p class="text-muted" style="font-size:12px;">So far this month</p>
            </div>
            <div class="forecast-item">
              <p class="stat-label">Pace Status</p>
              <h3 id="forecast-pace" class="stat-value">—</h3>
              <p id="forecast-pace-detail" class="text-muted" style="font-size:12px;"></p>
            </div>
          </div>
          <div style="margin-top:16px;">
            <div class="flex-between mb-8">
              <span style="font-size:13px;color:var(--text-muted);">Projected vs allowance</span>
              <span style="font-size:13px;font-weight:600;" id="forecast-percent-label">0%</span>
            </div>
            <div class="progress-bar-wrap" style="height:10px;">
              <div class="progress-bar green" id="forecast-bar" style="width:0%;"></div>
            </div>
          </div>
        </div>

        <!-- Top categories -->
        <div class="card section">
          <div class="card-header">
            <h3><i class="fa fa-trophy"></i> Top Categories</h3>
            <span class="text-muted" style="font-size:13px;">This month</span>
          </div>
          <div id="top-categories-list"></div>
          <div id="top-categories-empty" class="empty-state hidden">
            <p>No spending recorded this month.</p>
          </div>
        </div>

      </div>

      <div id="analytics-error" class="hidden">
        <div class="card" style="text-align:center;padding:40px 24px;">
          <i class="fa fa-triangle-exclamation" style="font-size:40px;color:var(--danger);margin-bottom:16px;display:block;"></i>
          <h3>Could not load analytics</h3>
          <p class="text-muted" style="margin-bottom:20px;">Check your connection and try again.</p>
          <button class="btn btn-primary" onclick="loadAnalytics()"><i class="fa fa-rotate-right"></i> Retry</button>
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="/assets/js/analytics.js"></script>
  <script src="/assets/js/theme.js"></script>
</body>
</html>
