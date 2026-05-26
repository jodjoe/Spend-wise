<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Budgets — Birr Wise</title>
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
        <h2>Budgets</h2>
        <p>Set spending limits and track progress per category.</p>
      </div>

      <!-- Summary strip -->
      <div class="budget-summary">
        <div class="sum-card">
          <div class="sum-val" id="sum-total">–</div>
          <div class="sum-lbl">Total</div>
        </div>
        <div class="sum-card">
          <div class="sum-val" id="sum-on-track">–</div>
          <div class="sum-lbl">On track</div>
        </div>
        <div class="sum-card danger">
          <div class="sum-val" id="sum-over">–</div>
          <div class="sum-lbl">Over limit</div>
        </div>
      </div>

      <!-- Period tabs -->
      <div class="period-tabs">
        <button class="period-tab active" data-period="monthly">Monthly</button>
        <button class="period-tab" data-period="weekly">Weekly</button>
        <button class="period-tab" data-period="all">All</button>
      </div>

      <!-- Budget list -->
      <div class="budget-list" id="budget-list">
        <?php for($i=0;$i<3;$i++): ?>
        <div class="budget-card">
          <div class="bc-top">
            <div class="skeleton" style="width:44px;height:44px;flex-shrink:0"></div>
            <div style="flex:1">
              <div class="skeleton" style="width:45%;height:14px;margin-bottom:6px"></div>
              <div class="skeleton" style="width:25%;height:10px"></div>
            </div>
          </div>
          <div class="skeleton" style="height:8px;margin-bottom:10px"></div>
          <div style="display:flex;justify-content:space-between">
            <div class="skeleton" style="width:35%;height:11px"></div>
            <div class="skeleton" style="width:20%;height:11px"></div>
          </div>
        </div>
        <?php endfor; ?>
      </div>

      <div class="budget-empty hidden" id="budget-empty">
        <i class="fa fa-bullseye"></i>
        <p>No budgets yet. Create one to start tracking.</p>
        <button class="btn btn-primary" id="empty-add-btn"><i class="fa fa-plus"></i> Add budget</button>
      </div>

    </div>
  </div>

  <button class="fab" id="fab-add"><i class="fa fa-plus"></i></button>

  <!-- Modal -->
  <div class="modal-overlay" id="budget-modal">
    <div class="modal">
      <div class="modal-handle"></div>
      <div class="modal-head">
        <h2 id="modal-title">Add Budget</h2>
        <button class="modal-x" id="close-modal">&times;</button>
      </div>
      <form id="budget-form" novalidate>
        <input type="hidden" id="budget-id" value="0">
        <label class="f-label" for="budget-category">Category</label>
        <select id="budget-category" class="f-select" required>
          <option value="">Select a category…</option>
        </select>
        <div class="f-err" id="cat-err">Please select a category.</div>
        <label class="f-label" for="budget-amount">Amount (ETB)</label>
        <input type="number" id="budget-amount" class="f-input" placeholder="e.g. 500" min="1" max="99999" step="0.01" required>
        <div class="f-err" id="amt-err">Enter a valid amount (1 – 99,999).</div>
        <label class="f-label">Period</label>
        <div class="period-toggle">
          <button type="button" class="pt-opt active" data-val="monthly">Monthly</button>
          <button type="button" class="pt-opt" data-val="weekly">Weekly</button>
        </div>
        <input type="hidden" id="budget-period" value="monthly">
        <div class="modal-foot">
          <button type="button" class="btn-cancel" id="cancel-modal">Cancel</button>
          <button type="submit" class="btn-save" id="save-btn">Save budget</button>
        </div>
      </form>
    </div>
  </div>

  <nav class="bottom-nav">
    <a href="/pages/dashboard.php"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
    <a href="/pages/expenses.php"><i class="fa fa-wallet"></i><span>Expenses</span></a>
    <a href="/pages/categories.php"><i class="fa fa-list"></i><span>Categories</span></a>
    <a href="/pages/budgets.php" class="active"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
    <a href="/pages/profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
  </nav>

  <script>window.__PREVIEW__ = <?= !empty($_SESSION['preview_mode']) ? 'true' : 'false' ?>;</script>
  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/budgets.js"></script>
  <script src="/assets/js/theme.js"></script>
</body>
</html>
