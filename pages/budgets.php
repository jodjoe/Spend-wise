<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Budgets — Birr Wise</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="top-bar">
    <h1>Birr Wise</h1>
    <a href="/pages/profile.php"><i class="fa fa-user-circle"></i></a>
  </div>

  <div class="page-wrapper">
    <div class="container">
      <div class="page-header">
        <h2>Budgets</h2>
        <p>Set and monitor budgets for categories, weekly or monthly.</p>
      </div>

      <div id="budgets-grid" class="grid"></div>
      <div id="budgets-empty" class="empty-state hidden">
        <span class="emoji">🎯</span>
        <p>No budgets set yet. Create a budget to stay on track.</p>
      </div>
    </div>
  </div>

  <button class="fab" id="fab-add-budget"><i class="fa fa-plus"></i></button>

  <div class="modal-overlay" id="budget-modal">
    <div class="modal">
      <div class="modal-header">
        <h2 id="budget-modal-title">Add Budget</h2>
        <button type="button" class="modal-close" id="close-budget-modal">&times;</button>
      </div>
      <form id="budget-form">
        <input type="hidden" id="budget-id" name="id" value="0">
        <div class="form-group">
          <label for="budget-category">Category</label>
          <select id="budget-category" name="category_id" class="form-control" required>
            <option value="">Choose category</option>
          </select>
        </div>
        <div class="form-group">
          <label for="budget-amount">Amount</label>
          <input id="budget-amount" name="amount" type="number" step="0.01" class="form-control" placeholder="Enter budget amount" required>
        </div>
        <div class="form-group">
          <label for="budget-period">Period</label>
          <select id="budget-period" name="period" class="form-control" required>
            <option value="monthly">Monthly</option>
            <option value="weekly">Weekly</option>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancel-budget">Cancel</button>
          <button type="submit" class="btn btn-primary" id="save-budget">Save Budget</button>
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

  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/budgets.js"></script>
</body>
</html>

