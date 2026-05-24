<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Expenses — Birr Wise</title>
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
        <h2>Expenses</h2>
        <p>Track every expense and keep your spending under control.</p>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Search & Filter</h3>
        </div>
        <div class="grid grid-2 gap-12">
          <div class="form-group">
            <label for="search-text">Search</label>
            <input id="search-text" type="text" class="form-control" placeholder="Search notes">
          </div>
          <div class="form-group">
            <label for="filter-category">Category</label>
            <select id="filter-category" class="form-control">
              <option value="0">All categories</option>
            </select>
          </div>
        </div>
        <div class="grid grid-2 gap-12">
          <div class="form-group">
            <label for="filter-date-from">Date from</label>
            <input id="filter-date-from" type="date" class="form-control">
          </div>
          <div class="form-group">
            <label for="filter-date-to">Date to</label>
            <input id="filter-date-to" type="date" class="form-control">
          </div>
        </div>
        <div class="grid grid-2 gap-12">
          <div class="form-group">
            <label for="filter-min-amount">Min amount</label>
            <input id="filter-min-amount" type="number" class="form-control" placeholder="0">
          </div>
          <div class="form-group">
            <label for="filter-max-amount">Max amount</label>
            <input id="filter-max-amount" type="number" class="form-control" placeholder="100000">
          </div>
        </div>
      </div>

      <div id="expenses-list"></div>
      <div id="expenses-empty" class="empty-state hidden">
        <span class="emoji">🧾</span>
        <p>No expenses found. Add your first expense to start tracking.</p>
      </div>
    </div>
  </div>

  <button class="fab" id="fab-add-expense"><i class="fa fa-plus"></i></button>

  <div class="modal-overlay" id="expense-modal">
    <div class="modal">
      <div class="modal-header">
        <h2 id="expense-modal-title">Add Expense</h2>
        <button type="button" class="modal-close" id="close-expense-modal">&times;</button>
      </div>
      <div class="tabs">
        <button class="tab active" data-tab="manual">Manual Entry</button>
        <button class="tab" data-tab="sms">Paste SMS</button>
      </div>
      <form id="expense-form">
        <input type="hidden" id="expense-id" name="id" value="0">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <div id="manual-tab" class="tab-content">
          <div class="form-group">
            <label for="expense-amount">Amount</label>
            <input id="expense-amount" name="amount" type="number" step="0.01" class="form-control" placeholder="Enter amount" required>
          </div>
          <div class="form-group">
            <label for="expense-category">Category</label>
            <select id="expense-category" name="category_id" class="form-control" required>
              <option value="">Select category</option>
            </select>
          </div>
          <div class="form-group">
            <label for="expense-date">Date</label>
            <input id="expense-date" name="expense_date" type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <div class="form-group">
            <label for="expense-note">Note</label>
            <textarea id="expense-note" name="note" class="form-control" placeholder="Add a note"></textarea>
          </div>
        </div>
        <div id="sms-tab" class="tab-content hidden">
          <div class="form-group">
            <label for="sms-text">SMS text</label>
            <textarea id="sms-text" class="form-control" placeholder="Paste your expense SMS here"></textarea>
          </div>
          <div class="form-group">
            <button type="button" class="btn btn-outline" id="parse-sms">Parse SMS</button>
          </div>
          <div id="sms-preview" class="alert alert-success hidden"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancel-expense">Cancel</button>
          <button type="submit" class="btn btn-primary" id="save-expense">Save Expense</button>
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
</body>
</html>

