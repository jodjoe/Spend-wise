// ── State ────────────────────────────────────────────────────
let allExpenses  = [];
let currentYear  = new Date().getFullYear();
let searchQuery  = '';
const thisYear   = new Date().getFullYear();

// ── DOM ──────────────────────────────────────────────────────
const histList   = document.getElementById('history-list');
const yearLabel  = document.getElementById('year-label');
const yearTotal  = document.getElementById('year-total');
const prevBtn    = document.getElementById('prev-year');
const nextBtn    = document.getElementById('next-year');
const searchIn   = document.getElementById('hist-search');

// ── Helpers ──────────────────────────────────────────────────
const MONTHS = ['January','February','March','April','May','June',
                'July','August','September','October','November','December'];

function fmt(n) {
  return Number(n).toLocaleString('en-ET', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';
}

function shortDate(dateStr) {
  const d = new Date(dateStr + 'T00:00:00');
  return d.toLocaleDateString('en-ET', { day: 'numeric', month: 'short' });
}

// ── Group expenses by month ───────────────────────────────────
function groupByMonth(expenses) {
  const map = {};
  expenses.forEach(e => {
    const d   = new Date(e.expense_date + 'T00:00:00');
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    if (!map[key]) map[key] = [];
    map[key].push(e);
  });
  // Sort descending
  return Object.entries(map).sort((a, b) => b[0].localeCompare(a[0]));
}

// ── Category breakdown for a month ───────────────────────────
function catBreakdown(expenses) {
  const map = {};
  expenses.forEach(e => {
    if (!map[e.category_name]) map[e.category_name] = { icon: e.category_icon, total: 0 };
    map[e.category_name].total += parseFloat(e.amount);
  });
  return Object.entries(map)
    .sort((a, b) => b[1].total - a[1].total)
    .slice(0, 5);
}

// ── Render ───────────────────────────────────────────────────
function renderMonth(key, expenses, isFirst) {
  const [year, month] = key.split('-');
  const monthName = MONTHS[parseInt(month) - 1];
  const total     = expenses.reduce((s, e) => s + parseFloat(e.amount), 0);
  const avg       = total / expenses.length;
  const max       = Math.max(...expenses.map(e => parseFloat(e.amount)));
  const cats      = catBreakdown(expenses);
  const isOpen    = isFirst;

  const catPills = cats.map(([name, d]) =>
    `<div class="cat-pill"><span>${d.icon}</span>${name} · ${fmt(d.total)}</div>`
  ).join('');

  const catDots = cats.slice(0, 4).map(([, d]) =>
    `<span class="month-cat-dot">${d.icon}</span>`
  ).join('');

  const rows = expenses.map(e => `
    <div class="hist-item" data-note="${(e.note || '').toLowerCase()}" data-cat="${e.category_name.toLowerCase()}">
      <div class="hist-icon">${e.category_icon}</div>
      <div class="hist-info">
        <div class="hist-cat">${e.category_name}</div>
        <div class="hist-note">${e.note || 'No note'}</div>
      </div>
      <div class="hist-right">
        <div class="hist-amt">${e.amount_formatted}</div>
        <div class="hist-date">${shortDate(e.expense_date)}</div>
      </div>
    </div>
  `).join('');

  return `
    <div class="month-block${isOpen ? ' open' : ''}" data-key="${key}">
      <div class="month-trigger">
        <div class="month-dot"></div>
        <div class="month-trigger-info">
          <div class="month-name">${monthName} ${year}</div>
          <div class="month-meta">
            <span class="month-count">${expenses.length} expense${expenses.length !== 1 ? 's' : ''}</span>
            <div class="month-cats">${catDots}</div>
          </div>
        </div>
        <div class="month-right">
          <div class="month-total-amt">${fmt(total)}</div>
          <i class="fa fa-chevron-down month-chevron"></i>
        </div>
      </div>

      <div class="month-body">
        <div class="month-stats">
          <div class="month-stat">
            <div class="sv">${fmt(total)}</div>
            <div class="sl">Total spent</div>
          </div>
          <div class="month-stat">
            <div class="sv">${fmt(avg)}</div>
            <div class="sl">Avg / expense</div>
          </div>
          <div class="month-stat">
            <div class="sv">${fmt(max)}</div>
            <div class="sl">Largest</div>
          </div>
        </div>

        <div class="cat-breakdown">${catPills}</div>

        <div class="expense-rows">${rows}</div>
      </div>
    </div>`;
}

function applySearch() {
  const q = searchQuery.toLowerCase();
  document.querySelectorAll('.hist-item').forEach(item => {
    const match = !q || item.dataset.note.includes(q) || item.dataset.cat.includes(q);
    item.style.display = match ? '' : 'none';
  });

  // Show "no results" inside each open month if all rows hidden
  document.querySelectorAll('.month-block').forEach(block => {
    const rows    = block.querySelectorAll('.hist-item');
    const visible = [...rows].filter(r => r.style.display !== 'none');
    let noRes = block.querySelector('.no-results');
    if (visible.length === 0 && q) {
      if (!noRes) {
        noRes = document.createElement('div');
        noRes.className = 'no-results';
        noRes.textContent = 'No matching expenses.';
        block.querySelector('.expense-rows').appendChild(noRes);
      }
    } else if (noRes) {
      noRes.remove();
    }
  });
}

function render() {
  const yearExpenses = allExpenses.filter(e => {
    const y = new Date(e.expense_date + 'T00:00:00').getFullYear();
    return y === currentYear;
  });

  yearLabel.textContent = currentYear;
  nextBtn.disabled = currentYear >= thisYear;
  prevBtn.disabled = currentYear <= thisYear - 5;

  if (!yearExpenses.length) {
    const yearTotal_el = document.getElementById('year-total');
    if (yearTotal_el) yearTotal_el.textContent = '';
    histList.innerHTML = `
      <div class="hist-empty">
        <i class="fa fa-clock-rotate-left"></i>
        <p>No expenses recorded in ${currentYear}.</p>
      </div>`;
    return;
  }

  const total = yearExpenses.reduce((s, e) => s + parseFloat(e.amount), 0);
  yearTotal.innerHTML = `Total: <strong>${fmt(total)}</strong>`;

  const grouped = groupByMonth(yearExpenses);
  histList.innerHTML = grouped.map(([key, exps], i) => renderMonth(key, exps, i === 0)).join('');

  // Accordion toggle
  histList.querySelectorAll('.month-trigger').forEach(trigger => {
    trigger.addEventListener('click', () => {
      const block = trigger.closest('.month-block');
      block.classList.toggle('open');
    });
  });

  applySearch();
}

// ── Fetch ────────────────────────────────────────────────────
async function loadHistory() {
  try {
    // Fetch all years at once — use a wide date range
    const url = apiUrl(
      '/api/get_expenses.php?date_from=2020-01-01&date_to=2099-12-31',
      '/dev/mock_history.php'
    );
    const res  = await fetch(url);
    const json = await res.json();
    if (!json.success) { showToast(json.message || 'Failed to load.', 'error'); return; }
    allExpenses = json.data;
    render();
  } catch {
    showToast('Unable to load history.', 'error');
  }
}

// ── Year nav ─────────────────────────────────────────────────
prevBtn.addEventListener('click', () => { currentYear--; render(); });
nextBtn.addEventListener('click', () => { currentYear++; render(); });

// ── Search ───────────────────────────────────────────────────
searchIn.addEventListener('input', () => {
  searchQuery = searchIn.value.trim();
  applySearch();
});

// ── Init ─────────────────────────────────────────────────────
loadHistory();
