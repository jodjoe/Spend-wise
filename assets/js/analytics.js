const weeklyTrendContainer = document.getElementById('weekly-trend');
const categoryBreakdownContainer = document.getElementById('category-breakdown');
const budgetUsageContainer = document.getElementById('budget-usage');
const weeklyEmpty = document.getElementById('weekly-empty');
const categoriesEmpty = document.getElementById('categories-empty');
const budgetEmpty = document.getElementById('budget-empty');
const projectedRemaining = document.getElementById('projected-remaining');
const forecastPace = document.getElementById('forecast-pace');

function formatAmount(amount) {
  return `${amount.toFixed(2)} ETB`;
}

function renderWeeklyTrend(data, maxValue) {
  if (!data || data.length === 0) {
    weeklyTrendContainer.innerHTML = '';
    weeklyEmpty.classList.remove('hidden');
    return;
  }

  weeklyEmpty.classList.add('hidden');
  weeklyTrendContainer.innerHTML = data.map(item => {
    const width = maxValue > 0 ? Math.max(4, (item.amount / maxValue) * 100) : 4;
    return `
      <div class="card">
        <div class="flex-between mb-8">
          <span>${item.label}</span>
          <strong>${item.amount_formatted}</strong>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar green" style="width: ${width}%;"></div>
        </div>
      </div>
    `;
  }).join('');
}

function renderCategoryBreakdown(data) {
  if (!data || data.length === 0) {
    categoryBreakdownContainer.innerHTML = '';
    categoriesEmpty.classList.remove('hidden');
    return;
  }

  categoriesEmpty.classList.add('hidden');
  categoryBreakdownContainer.innerHTML = data.map(item => `
    <div class="card">
      <div class="flex-between mb-8">
        <div>
          <div class="grid-item-emoji">${item.icon}</div>
          <h4>${item.name}</h4>
        </div>
        <strong>${item.amount_formatted}</strong>
      </div>
      <div class="progress-bar-wrap">
        <div class="progress-bar yellow" style="width: ${item.percentage}%;"></div>
      </div>
      <p class="text-muted">${item.percentage}% of monthly spending</p>
    </div>
  `).join('');
}

function renderBudgetUsage(data) {
  if (!data || data.length === 0) {
    budgetUsageContainer.innerHTML = '';
    budgetEmpty.classList.remove('hidden');
    return;
  }

  budgetEmpty.classList.add('hidden');
  budgetUsageContainer.innerHTML = data.map(item => `
    <div class="card">
      <div class="flex-between mb-8">
        <div>
          <div class="grid-item-emoji">${item.category_icon}</div>
          <h4>${item.category_name}</h4>
          <p class="text-muted">${item.period.charAt(0).toUpperCase() + item.period.slice(1)} budget</p>
        </div>
        <strong>${item.spent_formatted}</strong>
      </div>
      <div class="progress-bar-wrap">
        <div class="progress-bar ${item.level}" style="width: ${Math.min(item.percentage, 100)}%;"></div>
      </div>
      <div class="flex-between mt-8">
        <span>${item.percentage}% used</span>
        <span>${item.amount_formatted}</span>
      </div>
    </div>
  `).join('');
}

function getPaceLabel(status) {
  switch (status) {
    case 'warning':
      return 'Your spending is slightly ahead of the month.';
    case 'critical':
      return 'Your spending is ahead of the plan. Slow down now.';
    default:
      return 'You are on track with your allowance.';
  }
}

async function loadAnalysis() {
  try {
    const response = await fetch('/api/get_analysis.php');
    const result = await response.json();
    if (!result.success) {
      showPopup({type: 'danger', title: 'Error', message: result.message || 'Unable to load analytics.'});
      return;
    }

    const data = result.data;
    renderWeeklyTrend(data.weekly_spending, data.max_weekly);
    renderCategoryBreakdown(data.category_breakdown.filter(item => item.amount > 0));
    renderBudgetUsage(data.budget_usage);
    projectedRemaining.textContent = data.predicted_remaining !== null ? formatAmount(data.predicted_remaining) : 'N/A';
    forecastPace.textContent = getPaceLabel(data.pace_status);
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load analytics.'});
  }
}

loadAnalysis();
