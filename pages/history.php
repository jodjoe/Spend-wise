<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>History — Birr Wise</title>
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
    <div class="container">

      <div class="page-header">
        <a href="/pages/dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Dashboard</a>
        <h2>History</h2>
        <p>Your complete spending record, month by month.</p>
      </div>

      <div class="year-row">
        <div class="year-nav">
          <button class="year-btn" id="prev-year"><i class="fa fa-chevron-left"></i></button>
          <span class="year-label" id="year-label"><?= date('Y') ?></span>
          <button class="year-btn" id="next-year" disabled><i class="fa fa-chevron-right"></i></button>
        </div>
        <div class="year-total" id="year-total"></div>
      </div>

      <div class="search-wrap">
        <i class="fa fa-search"></i>
        <input type="text" class="search-input" id="hist-search" placeholder="Search expenses…">
      </div>

      <div id="history-list">
        <?php for($i=0;$i<4;$i++): ?>
        <div class="skel-month">
          <div class="skeleton" style="width:10px;height:10px;flex-shrink:0"></div>
          <div style="flex:1">
            <div class="skeleton" style="width:35%;height:14px;margin-bottom:6px"></div>
            <div class="skeleton" style="width:20%;height:11px"></div>
          </div>
          <div class="skeleton" style="width:80px;height:16px"></div>
        </div>
        <?php endfor; ?>
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

  <script>window.__PREVIEW__ = <?= !empty($_SESSION['preview_mode']) ? 'true' : 'false' ?>;</script>
  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/history.js"></script>
  <script src="/assets/js/theme.js"></script>
</body>
</html>
