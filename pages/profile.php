<?php
require_once '../includes/session.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

$pdo = getDB();
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';

// Load user data
$stmt = $pdo->prepare('SELECT name, email, monthly_allowance, created_at FROM users WHERE id = :user_id');
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch();

// Activity summary
$summary = [
    'total_expenses' => 0,
    'total_spent' => 0,
    'months_tracked' => 0,
    'member_since' => ''
];

if ($user) {
    $summary['member_since'] = date('F Y', strtotime($user['created_at']));

    $stmt = $pdo->prepare('SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as spent FROM expenses WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $expense_stats = $stmt->fetch();
    $summary['total_expenses'] = intval($expense_stats['total']);
    $summary['total_spent'] = floatval($expense_stats['spent']);

    $stmt = $pdo->prepare('SELECT MIN(expense_date) as first_date FROM expenses WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $user_id]);
    $first_date = $stmt->fetchColumn();

    if ($first_date) {
        $first_month = new DateTime($first_date);
        $today = new DateTime();
        $interval = $first_month->diff($today);
        $summary['months_tracked'] = $interval->y * 12 + $interval->m + 1;
    } else {
        $summary['months_tracked'] = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
  <title>Profile — Birr Wise</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
  <div class="top-bar">
    <h1>🇪🇹 Birr Wise</h1>
    <a href="/auth/logout.php"><i class="fa fa-sign-out-alt"></i></a>
  </div>

  <div class="page-wrapper">
    <div class="container">
      <div class="page-header">
        <h2>Profile</h2>
        <p>Update your account settings, security, and monthly allowance.</p>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Personal Information</h3>
        </div>
        <form id="profile-form">
          <div class="form-group">
            <label for="profile-name">Name</label>
            <input id="profile-name" name="name" type="text" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <div class="form-group">
            <label for="profile-email">Email</label>
            <input id="profile-email" name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <button type="submit" class="btn btn-primary btn-full">Save Changes</button>
        </form>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Security</h3>
        </div>
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

      <div class="card section">
        <div class="card-header">
          <h3>Allowance</h3>
        </div>
        <form id="allowance-form">
          <div class="form-group">
            <label for="allowance-value">Monthly Allowance</label>
            <input id="allowance-value" name="monthly_allowance" type="number" step="0.01" class="form-control" value="<?php echo htmlspecialchars($user['monthly_allowance'] ?? 0, ENT_QUOTES, 'UTF-8'); ?>" required>
          </div>
          <button type="submit" class="btn btn-primary btn-full">Update Allowance</button>
        </form>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Account Summary</h3>
        </div>
        <div class="grid grid-2 gap-12">
          <div class="card">
            <h4>Member Since</h4>
            <p><?php echo htmlspecialchars($summary['member_since'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
          <div class="card">
            <h4>Total Expenses</h4>
            <p><?php echo $summary['total_expenses']; ?></p>
          </div>
          <div class="card">
            <h4>Months Tracked</h4>
            <p><?php echo $summary['months_tracked']; ?></p>
          </div>
          <div class="card">
            <h4>Total Spent</h4>
            <p><?php echo number_format($summary['total_spent'], 2); ?> ETB</p>
          </div>
        </div>
      </div>

      <div class="card section">
        <div class="card-header">
          <h3>Delete Account</h3>
        </div>
        <p class="text-muted mb-16">Deleting your account will remove all categories, expenses, budgets, and settings.</p>
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
    const profileForm = document.getElementById('profile-form');
    const passwordForm = document.getElementById('password-form');
    const allowanceForm = document.getElementById('allowance-form');
    const deleteButton = document.getElementById('delete-account-button');

    function handleResponse(response) {
      if (!response.success) {
        showPopup({type: 'danger', title: 'Error', message: response.message});
        return false;
      }
      showPopup({type: 'success', title: 'Success', message: response.message});
      return true;
    }

    profileForm.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(profileForm);
      appendCsrfToken(formData);
      const response = await fetch('/api/update_profile.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json());
      handleResponse(response);
    });

    passwordForm.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(passwordForm);
      appendCsrfToken(formData);
      const response = await fetch('/api/change_password.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json());
      if (handleResponse(response)) {
        passwordForm.reset();
      }
    });

    allowanceForm.addEventListener('submit', async event => {
      event.preventDefault();
      const formData = new FormData(allowanceForm);
      appendCsrfToken(formData);
      const response = await fetch('/api/update_allowance.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json());
      handleResponse(response);
    });

    deleteButton.addEventListener('click', async () => {
      const confirmed = confirm('Are you sure you want to delete your account? This cannot be undone.');
      if (!confirmed) {
        return;
      }
      const password = prompt('Enter your current password to confirm account deletion:');
      if (!password) {
        return;
      }
      const formData = new FormData();
      formData.append('password', password);
      appendCsrfToken(formData);
      const response = await fetch('/api/delete_account.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json());
      if (handleResponse(response)) {
        window.location.href = '/auth/login.php';
      }
    });
  </script>
</body>
</html>

