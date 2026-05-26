// ── Helpers ─────────────────────────────────────────────────
function fmt(n) {
  return Number(n).toLocaleString('en-ET', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';
}

function barClass(pct) {
  if (pct < 60) return 'green';
  if (pct < 85) return 'yellow';
  return 'red';
}

const paceConfig = {
  on_track: { cls: 'on_track', icon: 'fa-circle-check', label: 'On track' },
  warning:  { cls: 'warning',  icon: 'fa-triangle-exclamation', label: 'Slightly over pace' },
  critical: { cls: 'critical', icon: 'fa-circle-xmark', label: 'Over budget pace' },
};

// ── Render functions ─────────────────────────────────────────
function renderMetrics(d) {
  const set = (id, val, colorClass) => {
    const el = document.getElementById(id);
    el.textContent = val;
    el.classList.remove('loading');
    if (colorClass) el.style.color = colorClass;
  };

  set('dash-allowance', fmt(d.monthly_allowance));
  set('dash-spent',     fmt(d.month_spent),     d.spent_percent >= 85 ? '#cc0000' : null);
  set('dash-remaining', fmt(d.month_remaining), d.month_remaining === 0 ? '#cc0000' : '#000');
  set('dash-today',     fmt(d.today_remaining), d.today_remaining === 0 ? '#cc0000' : '#555');
}

function renderProgress(d) {
  const pct = d.spent_percent;
  document.getElementById('dash-pct').textContent = pct + '%';
  const bar = document.getElementById('dash-bar');
  bar.style.width = Math.min(pct, 100) + '%';
  bar.style.background = pct >= 85 ? '#cc0000' : pct >= 60 ? '#888' : '#000';

  const cfg = paceConfig[d.pace_status] || paceConfig.on_track;
  const paceEl = document.getElementById('dash-pace');
  paceEl.className = 'pace-chip ' + cfg.cls;
  paceEl.innerHTML = `<i class="fa ${cfg.icon}"></i> ${cfg.label}`;

  document.getElementById('dash-prediction').innerHTML =
    `<i class="fa fa-chart-line"></i> Projected: ${d.predicted_remaining}`;
}

function renderCategories(cats) {
  const grid  = document.getElementById('cat-grid');
  const empty = document.getElementById('cat-empty');

  if (!cats || cats.length === 0) {
    grid.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }
  empty.classList.add('hidden');
  grid.innerHTML = cats.map(c => `
    <div class="cat-chip">
      <span class="cat-emoji">${c.icon}</span>
      <span class="cat-name" title="${c.name}">${c.name}</span>
      <span class="cat-amount">${c.spent_formatted}</span>
    </div>
  `).join('');
}

function renderExpenses(expenses) {
  const list  = document.getElementById('exp-list');
  const empty = document.getElementById('exp-empty');

  if (!expenses || expenses.length === 0) {
    list.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }
  empty.classList.add('hidden');
  list.innerHTML = expenses.map(e => `
    <div class="exp-item">
      <div class="exp-icon">${e.category_icon}</div>
      <div class="exp-info">
        <div class="exp-cat">${e.category_name}</div>
        <div class="exp-meta">${e.note ? e.note + ' · ' : ''}${e.expense_date_formatted}</div>
      </div>
      <div class="exp-amount">${e.amount_formatted}</div>
    </div>
  `).join('');
}

// ── Load ─────────────────────────────────────────────────────
async function loadDashboard() {
  try {
    const res  = await fetch(apiUrl('/api/get_analytics.php', '/dev/mock_analytics.php'));
    const json = await res.json();

    if (!json.success) {
      if (typeof showPopup === 'function')
        showPopup({ type: 'danger', title: 'Error', message: json.message || 'Could not load dashboard.' });
      return;
    }

    const d = json.data;
    renderMetrics(d);
    renderProgress(d);
    renderCategories(d.top_categories);
    renderExpenses(d.recent_expenses);
  } catch (err) {
    if (typeof showPopup === 'function')
      showPopup({ type: 'danger', title: 'Error', message: 'Unable to load dashboard.' });
  }
}

loadDashboard();
