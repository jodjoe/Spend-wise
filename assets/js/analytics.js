const weeklyTrendContainer = document.getElementById('weekly-trend');
const categoryBreakdownContainer = document.getElementById('category-breakdown');
const budgetUsageContainer = document.getElementById('budget-usage');
const weeklyEmpty = document.getElementById('weekly-empty');
const categoriesEmpty = document.getElementById('categories-empty');
const budgetEmpty = document.getElementById('budget-empty');
const projectedRemaining = document.getElementById('projected-remaining');
const forecastPace = document.getElementById('forecast-pace');
let categoryChartInstance = null;

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

function renderCategoryBreakdown(data, total = 0) {
  if (!data || data.length === 0) {
    categoryBreakdownContainer.innerHTML = '';
    categoriesEmpty.classList.remove('hidden');
    return;
  }

  categoriesEmpty.classList.add('hidden');
  const totalCard = `
    <div class="card">
      <div class="flex-between mb-8">
        <div>
          <h4>Total category spending</h4>
          <p class="text-muted">This month</p>
        </div>
        <strong>${formatAmount(total)}</strong>
      </div>
    </div>
  `;

  const itemsHtml = data.map(item => `
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

  categoryBreakdownContainer.innerHTML = totalCard + itemsHtml;
}

function renderCategoryChart(data) {
  const chartCanvas = document.getElementById('categoryChart');
  const legendContainer = document.getElementById('category-legend');
  if (!chartCanvas || !legendContainer) return;

  if (!data || data.length === 0) {
    chartCanvas.style.display = 'none';
    legendContainer.innerHTML = '';
    categoriesEmpty.classList.remove('hidden');
    return;
  }

  categoriesEmpty.classList.add('hidden');
  chartCanvas.style.display = 'block';

  const labels = data.map(d => d.name);
  const values = data.map(d => d.amount);
  const total = values.reduce((a, b) => a + b, 0) || 1;

  const baseColors = ['#FF6384','#36A2EB','#FFCE56','#8E44AD','#3498DB','#FFA07A','#6B8E23','#FF00FF','#FFD700','#00FFFF'];
  const backgroundColor = labels.map((_, i) => baseColors[i % baseColors.length]);

  if (categoryChartInstance) {
    categoryChartInstance.destroy();
    categoryChartInstance = null;
  }

  const ctx = chartCanvas.getContext('2d');
  categoryChartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        data: values,
        backgroundColor: backgroundColor,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: function(context) {
              const idx = context.dataIndex;
              const item = data[idx];
              const percentage = ((item.amount / total) * 100).toFixed(2);
              return `${item.name}: ${percentage}% — ${item.amount_formatted || formatAmount(item.amount)}`;
            }
          }
        }
      }
    }
  });

  // build legend
  legendContainer.innerHTML = '';
  data.forEach((item, i) => {
    const pct = ((item.amount / total) * 100).toFixed(2);
    const row = document.createElement('div');
    row.className = 'chart-legend-item';
    const colorBox = document.createElement('div');
    colorBox.className = 'chart-legend-color-box';
    colorBox.style.backgroundColor = backgroundColor[i % backgroundColor.length];
    const label = document.createElement('div');
    label.className = 'chart-legend-label';
    label.textContent = `${item.name}: ${pct}% • ${item.amount_formatted || formatAmount(item.amount)}`;
    row.appendChild(colorBox);
    row.appendChild(label);
    legendContainer.appendChild(row);
  });
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
    const nonZeroCategories = data.category_breakdown.filter(item => item.amount > 0);
    renderCategoryBreakdown(nonZeroCategories, data.total_category_spent || 0);
    renderCategoryChart(nonZeroCategories);
    renderBudgetUsage(data.budget_usage);
    projectedRemaining.textContent = data.predicted_remaining !== null ? formatAmount(data.predicted_remaining) : 'N/A';
    forecastPace.textContent = getPaceLabel(data.pace_status);
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load analytics.'});
  }
}

loadAnalysis();
