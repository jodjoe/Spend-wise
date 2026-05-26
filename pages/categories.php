<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Categories — Birr Wise</title>
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
        <h2>Categories</h2>
        <p>Organise your spending into meaningful groups.</p>
      </div>

      <div class="search-wrap">
        <i class="fa fa-search"></i>
        <input type="text" class="search-input" id="cat-search" placeholder="Search categories…">
      </div>

      <div class="sec-head">
        <h3>Default</h3>
        <span class="count-badge" id="default-count">–</span>
      </div>
      <div class="cat-list" id="default-list">
        <?php for($i=0;$i<4;$i++): ?>
        <div class="skel-row">
          <div class="skeleton" style="width:46px;height:46px;flex-shrink:0"></div>
          <div style="flex:1">
            <div class="skeleton" style="width:40%;height:14px;margin-bottom:6px"></div>
            <div class="skeleton" style="width:25%;height:11px"></div>
          </div>
        </div>
        <?php endfor; ?>
      </div>

      <div class="sec-head">
        <h3>Custom</h3>
        <span class="count-badge" id="custom-count">–</span>
      </div>
      <div class="cat-list" id="custom-list"></div>
      <div class="cat-empty hidden" id="custom-empty">
        <i class="fa fa-layer-group"></i>
        No custom categories yet.
      </div>

      <button class="add-cat-btn" id="open-add">
        <i class="fa fa-plus"></i> Add custom category
      </button>

    </div>
  </div>

  <button class="fab" id="fab-add"><i class="fa fa-plus"></i></button>

  <!-- Modal -->
  <div class="modal-overlay" id="cat-modal">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-header">
        <h2 id="modal-title">Add Category</h2>
        <button class="modal-close" id="close-modal">&times;</button>
      </div>
      <form id="cat-form" novalidate>
        <input type="hidden" id="cat-id" value="0">
        <label class="form-label" for="cat-name">Category name</label>
        <input type="text" id="cat-name" class="form-input" placeholder="e.g. Gym, Rent, Books…" maxlength="50" required>
        <div class="form-err" id="name-err">Name is required (max 50 chars).</div>
        <label class="form-label">Choose icon</label>
        <div class="emoji-grid" id="emoji-grid"></div>
        <input type="hidden" id="cat-icon" value="💰">
        <div class="modal-foot">
          <button type="button" class="btn-cancel" id="cancel-modal">Cancel</button>
          <button type="submit" class="btn-save" id="save-btn">Save category</button>
        </div>
      </form>
    </div>
  </div>

  <nav class="bottom-nav">
    <a href="/pages/dashboard.php"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
    <a href="/pages/expenses.php"><i class="fa fa-wallet"></i><span>Expenses</span></a>
    <a href="/pages/categories.php" class="active"><i class="fa fa-list"></i><span>Categories</span></a>
    <a href="/pages/budgets.php"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
    <a href="/pages/profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
  </nav>

  <script>window.__PREVIEW__ = <?= !empty($_SESSION['preview_mode']) ? 'true' : 'false' ?>;</script>
  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/categories.js"></script>
  <script src="/assets/js/theme.js"></script>
</body>
</html>
