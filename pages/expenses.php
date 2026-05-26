<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Expenses — Birr Wise</title>
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
        <h2>Expenses</h2>
        <p>Track every birr you spend.</p>
      </div>

      <!-- Summary bar -->
      <div class="card" style="padding:12px 16px;margin-bottom:16px;">
        <div class="flex-between">
          <span class="text-muted" style="font-size:13px;">Showing</span>
          <strong id="expense-count-label" style="font-size:13px;">— expenses</strong>
          <span id="expense-total-label" class="text-muted" style="font-size:13px;">Total: — ETB</span>
        </div>
      </div>

      <!-- Filters -->
      <div class="card" style="margin-bottom:16px;">
        <div class="card-header">
          <h3><i class="fa fa-filter"></i> Filter</h3>
          <button class="btn btn-outline" id="clear-filters" style="font-size:12px;padding:6px 12px;">Clear</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div class="form-group" style="margin-bottom:0;">
            <label for="search-text">Search</label>
            <input id="search-text" type="text" class="form-control" placeholder="e.g. lunch">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label for="filter-category">Category</label>
            <select id="filter-category" class="form-control">
              <option value="0">All categories</option>
            </select>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
          <div class="form-group" style="margin-bottom:0;">
            <label for="filter-date-from">From</label>
            <input id="filter-date-from" type="date" class="form-control">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label for="filter-date-to">To</label>
            <input id="filter-date-to" type="date" class="form-control">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
          <div class="form-group" style="margin-bottom:0;">
            <label for="filter-min-amount">Min (ETB)</label>
            <input id="filter-min-amount" type="number" min="0" class="form-control" placeholder="0">
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label for="filter-max-amount">Max (ETB)</label>
            <input id="filter-max-amount" type="number" min="0" class="form-control" placeholder="Any">
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div id="expenses-loading" class="text-center" style="padding:32px 0;">
        <div class="spinner" style="margin:0 auto 10px;"></div>
        <p class="text-muted" style="font-size:14px;">Loading expenses…</p>
      </div>

      <!-- List -->
      <div id="expenses-list" class="hidden"></div>

      <!-- Empty -->
      <div id="expenses-empty" class="empty-state hidden">
        <span class="emoji">💸</span>
        <p>No expenses found. Tap <strong>+</strong> to add one.</p>
      </div>

    </div>
  </div>

  <!-- FAB -->
  <button class="fab" id="fab-add-expense" title="Add expense"><i class="fa fa-plus"></i></button>

  <!-- Modal -->
  <div class="modal-overlay" id="expense-modal">
    <div class="modal">
      <div class="modal-header">
        <h2 id="expense-modal-title">Add Expense</h2>
        <button type="button" class="modal-close" id="close-expense-modal">&times;</button>
      </div>
      <div class="tabs">
        <button class="tab active" data-tab="manual"><i class="fa fa-pen"></i> Manual</button>
        <button class="tab" data-tab="sms"><i class="fa fa-message"></i> Paste SMS</button>
      </div>
      <form id="expense-form">
        <input type="hidden" id="expense-id" name="id" value="0">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <div id="manual-tab">
          <div class="form-group">
            <label for="expense-amount">Amount (ETB) <span style="color:var(--danger);">*</span></label>
            <input id="expense-amount" name="amount" type="number" step="0.01" min="0.01" max="99999" class="form-control" placeholder="e.g. 150.00" required>
            <span class="form-error" id="amount-error">Please enter a valid amount (0.01 – 99,999)</span>
          </div>
          <div class="form-group">
            <label for="expense-category">Category <span style="color:var(--danger);">*</span></label>
            <select id="expense-category" name="category_id" class="form-control" required>
              <option value="">Select a category</option>
            </select>
            <span class="form-error" id="category-error">Please select a category</span>
          </div>
          <div class="form-group">
            <label for="expense-date">Date <span style="color:var(--danger);">*</span></label>
            <input id="expense-date" name="expense_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="form-group">
            <label for="expense-note">Note <span class="text-muted" style="font-weight:400;">(optional)</span></label>
            <textarea id="expense-note" name="note" class="form-control" placeholder="e.g. Lunch at cafeteria" style="min-height:72px;"></textarea>
          </div>
        </div>

        <div id="sms-tab" class="hidden">
          <div class="alert alert-warning show" style="margin-bottom:12px;font-size:13px;">
            <i class="fa fa-circle-info"></i> Paste a Telebirr or CBE Birr SMS. AI will extract the amount, date, and merchant.
          </div>
          <div class="form-group">
            <label for="sms-text">SMS text</label>
            <textarea id="sms-text" class="form-control" placeholder="Paste SMS here…" style="min-height:100px;"></textarea>
          </div>
          <button type="button" class="btn btn-outline btn-full" id="parse-sms">
            <i class="fa fa-wand-magic-sparkles"></i> Parse with AI
          </button>
          <div id="sms-preview" class="alert alert-success hidden" style="margin-top:12px;"></div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancel-expense">Cancel</button>
          <button type="submit" class="btn btn-primary" id="save-expense">
            <i class="fa fa-floppy-disk"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>

  <nav class="bottom-nav">
    <a href="/pages/dashboard.php"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
    <a href="/pages/expenses.php" class="active"><i class="fa fa-wallet"></i><span>Expenses</span></a>
    <a href="/pages/categories.php"><i class="fa fa-list"></i><span>Categories</span></a>
    <a href="/pages/budgets.php"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
    <a href="/pages/profile.php"><i class="fa fa-user"></i><span>Profile</span></a>
  </nav>

  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/expenses.js"></script>
  <script src="/assets/js/theme.js"></script>
</body>
</html>
