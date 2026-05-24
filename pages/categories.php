<?php
require_once '../includes/session.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Categories — Birr Wise</title>
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
        <h2>Categories</h2>
        <p>Manage your default and custom spending categories.</p>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Default Categories</h3>
        </div>
        <div id="default-categories" class="grid"></div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Custom Categories</h3>
          <button class="btn btn-outline" id="open-add-category">Add Category</button>
        </div>
        <div id="custom-categories" class="grid"></div>
        <div id="categories-empty" class="empty-state hidden">
          <span class="emoji">📦</span>
          <p>No custom categories yet. Add a category to personalize your budget.</p>
        </div>
      </div>
    </div>
  </div>

  <button class="fab" id="fab-add-category"><i class="fa fa-plus"></i></button>

  <div class="modal-overlay" id="category-modal">
    <div class="modal">
      <div class="modal-header">
        <h2 id="category-modal-title">Add Category</h2>
        <button type="button" class="modal-close" id="close-category-modal">&times;</button>
      </div>
      <form id="category-form">
        <input type="hidden" id="category-id" name="id" value="0">
        <div class="form-group">
          <label for="category-name">Category Name</label>
          <input id="category-name" name="name" type="text" class="form-control" placeholder="Enter category name" required maxlength="50">
        </div>
        <div class="form-group">
          <label>Select Icon</label>
          <div id="emoji-picker" class="grid"></div>
          <input type="hidden" id="category-icon" name="icon" value="💰">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" id="cancel-category">Cancel</button>
          <button type="submit" class="btn btn-primary" id="save-category">Save Category</button>
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

  <script src="/assets/js/notifications.js"></script>
  <script src="/assets/js/categories.js"></script>
</body>
</html>

