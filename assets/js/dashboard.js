const allowanceValue = document.getElementById('dashboard-allowance');
const spentValue = document.getElementById('dashboard-spent');
const remainingValue = document.getElementById('dashboard-remaining');
const todayValue = document.getElementById('dashboard-today');
const spentPercent = document.getElementById('dashboard-spent-percent');
const progressBar = document.getElementById('dashboard-progress');
const paceText = document.getElementById('dashboard-pace');
const predictionText = document.getElementById('dashboard-prediction');
const topCategoriesList = document.getElementById('top-categories-list');
const topCategoriesEmpty = document.getElementById('top-categories-empty');
const recentExpensesList = document.getElementById('recent-expenses-list');
const recentExpensesEmpty = document.getElementById('recent-expenses-empty');

function formatAmount(amount) {
  return `${amount.toFixed(2)} ETB`;
}

function getPaceLabel(status) {
  switch (status) {
    case 'warning':
      return 'Warning: spending is slightly ahead of schedule';
    case 'critical':
      return 'Critical: reduce spending to stay on track';
    default:
      return 'On track: you are managing your budget well';
  }
}

function getProgressClass(percent) {
  if (percent < 60) return 'green';
  if (percent < 80) return 'yellow';
  return 'red';
}

function renderTopCategories(categories) {
  if (!categories || categories.length === 0) {
    topCategoriesList.innerHTML = '';
    topCategoriesEmpty.classList.remove('hidden');
    return;
  }

  topCategoriesEmpty.classList.add('hidden');
  topCategoriesList.innerHTML = categories.map(category => `
    <div class="card">
      <div class="flex-between mb-12">
        <div>
          <div class="grid-item-emoji">${category.icon}</div>
          <h4>${category.name}</h4>
        </div>
        <span class="text-muted">${category.spent_formatted}</span>
      </div>
    </div>
  `).join('');
}

function renderRecentExpenses(expenses) {
  if (!expenses || expenses.length === 0) {
    recentExpensesList.innerHTML = '';
    recentExpensesEmpty.classList.remove('hidden');
    return;
  }

  recentExpensesEmpty.classList.add('hidden');
  recentExpensesList.innerHTML = expenses.map(expense => `
    <div class="card">
      <div class="flex-between mb-12">
        <div>
          <div class="grid-item-emoji">${expense.category_icon}</div>
          <h4>${expense.category_name}</h4>
          <p class="text-muted">${expense.note || 'No note'} · ${expense.expense_date_formatted}</p>
        </div>
        <div class="text-right">
          <strong>${expense.amount_formatted}</strong>
        </div>
      </div>
    </div>
  `).join('');
}

async function loadDashboard() {
  try {
    const response = await fetch('/api/get_analytics.php');
    const result = await response.json();
    if (!result.success) {
      showPopup({type: 'danger', title: 'Error', message: result.message || 'Could not load dashboard.'});
      return;
    }

    const data = result.data;
    allowanceValue.textContent = formatAmount(data.monthly_allowance);
    spentValue.textContent = formatAmount(data.month_spent);
    remainingValue.textContent = formatAmount(data.month_remaining);
    todayValue.textContent = formatAmount(data.today_remaining);
    spentPercent.textContent = `${data.spent_percent}%`;
    progressBar.style.width = `${data.spent_percent}%`;
    progressBar.className = `progress-bar ${getProgressClass(data.spent_percent)}`;
    paceText.textContent = getPaceLabel(data.pace_status);
    predictionText.textContent = `Projected month end: ${data.predicted_remaining}`;

    renderTopCategories(data.top_categories);
    renderRecentExpenses(data.recent_expenses);
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load dashboard.'});
  }
}

loadDashboard();

