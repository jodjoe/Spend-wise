<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$pdo     = getDB();
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT name, email, monthly_allowance, created_at FROM users WHERE id = :user_id');
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch();

$summary = ['total_expenses' => 0, 'total_spent' => 0, 'months_tracked' => 0, 'member_since' => ''];
if ($user) {
    $summary['member_since'] = date('F Y', strtotime($user['created_at']));
    $stmt = $pdo->prepare('SELECT COUNT(*) as total, COALESCE(SUM(amount),0) as spent FROM expenses WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $es = $stmt->fetch();
    $summary['total_expenses'] = intval($es['total']);
    $summary['total_spent']    = floatval($es['spent']);
    $stmt = $pdo->prepare('SELECT MIN(expense_date) as first_date FROM expenses WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $fd = $stmt->fetchColumn();
    if ($fd) {
        $diff = (new DateTime($fd))->diff(new DateTime());
        $summary['months_tracked'] = $diff->y * 12 + $diff->m + 1;
    } else {
        $summary['months_tracked'] = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Profile — Birr Wise</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

  <div class="top-bar">
    <h1><img src="/assets/icon/maex.png" alt="Birr Wise" class="site-logo"> Birr Wise</h1>
    <div style="display:flex;align-items:center;gap:10px;">
      <button class="theme-toggle" title="Toggle theme"><i class="fa fa-moon"></i></button>
      <a href="/auth/logout.php" class="header-avatar" title="Logout"><i class="fa fa-right-from-bracket"></i></a>
    </div>
  </div>

  <div class="page-wrapper">
    <div class="container">

      <div class="page-header">
        <a href="/pages/dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Dashboard</a>
        <h2>Profile</h2>
        <p>Update your account settings and monthly allowance.</p>
      </div>

      <!-- Personal info -->
      <div class="card section">
        <div class="card-header"><h3>Personal Information</h3></div>
        <form id="profile-form">
          <div class="form-group">
            <label for="profile-name">Name</label>
            <input id="profile-name" name="name" type="text" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="form-group">
            <label for="profile-email">Email</label>
            <input id="profile-email" name="email" type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        </form>
      </div>

      <!-- Security -->
      <div class="card section">
        <div class="card-header"><h3>Security</h3></div>
        <form id="password-form">
          <div class="form-group">
            <label for="current-password">Current Password</label>
            <input id="current-password" name="current_password" type="password" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="new-password">New Password</label>
            <input id="new-password" name="new_password" type="password" class="form-control" required>
          </div>
          <div class="form-group">
            <label for="confirm-password">Confirm New Password</label>
            <input id="confirm-password" name="confirm_password" type="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-outline btn-full">Change Password</button>
        </form>
      </div>

      <!-- Allowance -->
      <div class="card section">
        <div class="card-header"><h3>Monthly Allowance</h3></div>
        <form id="allowance-form">
          <div class="form-group">
            <label for="allowance-value">Amount (ETB)</label>
            <input id="allowance-value" name="monthly_allowance" type="number" step="0.01" class="form-control" value="<?= htmlspecialchars($user['monthly_allowance'] ?? 0, ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <button type="submit" class="btn btn-primary btn-full">Update Allowance</button>
        </form>
      </div>

      <!-- Account summary -->
      <div class="card section">
        <div class="card-header"><h3>Account Summary</h3></div>
        <div class="profile-stat-grid">
          <div class="profile-stat-item">
            <h4>Member Since</h4>
            <p><?= htmlspecialchars($summary['member_since'], ENT_QUOTES, 'UTF-8') ?></p>
          </div>
          <div class="profile-stat-item">
            <h4>Total Expenses</h4>
            <p><?= $summary['total_expenses'] ?></p>
          </div>
          <div class="profile-stat-item">
            <h4>Months Tracked</h4>
            <p><?= $summary['months_tracked'] ?></p>
          </div>
          <div class="profile-stat-item">
            <h4>Total Spent</h4>
            <p><?= number_format($summary['total_spent'], 2) ?> ETB</p>
          </div>
        </div>
      </div>

      <!-- Delete account -->
      <div class="card section">
        <div class="card-header"><h3>Delete Account</h3></div>
        <p class="text-muted mb-16">This will permanently remove all your data and cannot be undone.</p>
        <button id="delete-account-button" class="btn btn-danger btn-full">Delete My Account</button>
      </div>

    </div>
  </div>

  <nav class="bottom-nav">
    <a href="/pages/dashboard.php"><i class="fa fa-chart-pie"></i><span>Dashboard</span></a>
    <a href="/pages/expenses.php"><i class="fa fa-wallet"></i><span>Expenses</span></a>
    <a href="/pages/categories.php"><i class="fa fa-list"></i><span>Categories</span></a>
    <a href="/pages/budgets.php"><i class="fa fa-bullseye"></i><span>Budgets</span></a>
    <a href="/pages/profile.php" class="active"><i class="fa fa-user"></i><span>Profile</span></a>
  </nav>

  <script src="/assets/js/notifications.js"></script>
  <script>
    ['profile-form','password-form','allowance-form'].forEach(id => {
      const form = document.getElementById(id);
      const urls = { 'profile-form': '/api/update_profile.php', 'password-form': '/api/change_password.php', 'allowance-form': '/api/update_allowance.php' };
      form.addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(form);
        appendCsrfToken(fd);
        const r = await fetch(urls[id], { method: 'POST', body: fd }).then(r => r.json());
        if (r.success) { showToast(r.message || 'Saved.', 'success'); if (id === 'password-form') form.reset(); }
        else showToast(r.message || 'Error.', 'error');
      });
    });

    document.getElementById('delete-account-button').addEventListener('click', () => {
      showConfirm({
        title: 'Delete Account',
        message: 'This will permanently delete all your data. This cannot be undone.',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        type: 'danger',
        onConfirm: async () => {
          const password = prompt('Enter your current password to confirm:');
          if (!password) return;
          const fd = new FormData();
          fd.append('password', password);
          appendCsrfToken(fd);
          const r = await fetch('/api/delete_account.php', { method: 'POST', body: fd }).then(r => r.json());
          if (r.success) window.location.href = '/auth/login.php';
          else showToast(r.message || 'Failed.', 'error');
        }
      });
    });
  </script>
  <script src="/assets/js/theme.js"></script>
</body>
</html>
