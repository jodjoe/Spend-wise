// ── State ────────────────────────────────────────────────────
let allBudgets   = [];
let activePeriod = 'monthly';

// ── DOM ──────────────────────────────────────────────────────
const budgetList  = document.getElementById('budget-list');
const budgetEmpty = document.getElementById('budget-empty');
const modal       = document.getElementById('budget-modal');
const modalTitle  = document.getElementById('modal-title');
const budgetForm  = document.getElementById('budget-form');
const budgetId    = document.getElementById('budget-id');
const budgetCat   = document.getElementById('budget-category');
const budgetAmt   = document.getElementById('budget-amount');
const budgetPer   = document.getElementById('budget-period');
const catErr      = document.getElementById('cat-err');
const amtErr      = document.getElementById('amt-err');
const saveBtn     = document.getElementById('save-btn');

// ── Modal ────────────────────────────────────────────────────
function openModal(title = 'Add Budget') {
  modalTitle.textContent = title;
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  modal.classList.remove('show');
  document.body.style.overflow = '';
  budgetForm.reset();
  budgetId.value  = '0';
  budgetPer.value = 'monthly';
  setPeriodToggle('monthly');
  [budgetCat, budgetAmt].forEach(el => el.classList.remove('err'));
  [catErr, amtErr].forEach(el => el.classList.remove('show'));
}

// ── Period toggle in modal ───────────────────────────────────
function setPeriodToggle(val) {
  document.querySelectorAll('.pt-opt').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.val === val);
  });
  budgetPer.value = val;
}

document.querySelectorAll('.pt-opt').forEach(btn => {
  btn.addEventListener('click', () => setPeriodToggle(btn.dataset.val));
});

// ── Summary strip ────────────────────────────────────────────
function updateSummary(budgets) {
  const onTrack = budgets.filter(b => b.percentage < 80).length;
  const over    = budgets.filter(b => b.percentage >= 100).length;
  document.getElementById('sum-total').textContent    = budgets.length;
  document.getElementById('sum-on-track').textContent = onTrack;
  document.getElementById('sum-over').textContent     = over;
}

// ── Render ───────────────────────────────────────────────────
function levelClass(pct) {
  if (pct >= 100) return 'danger';
  if (pct >= 80)  return 'warn';
  return 'ok';
}

function renderCard(b) {
  const pct   = Math.min(b.percentage, 100);
  const lvl   = levelClass(b.percentage);
  const over  = b.remaining < 0;

  return `
    <div class="budget-card">
      <div class="bc-top">
        <div class="bc-icon">${b.category_icon}</div>
        <div class="bc-info">
          <div class="bc-name">${b.category_name}</div>
          <div class="bc-period">${b.period === 'monthly' ? 'Monthly' : 'Weekly'} · ${b.amount_formatted}</div>
        </div>
        <div class="bc-actions">
          <button class="bc-btn edit-btn" data-id="${b.id}" title="Edit"><i class="fa fa-pen"></i></button>
          <button class="bc-btn del delete-btn" data-id="${b.id}" title="Delete"><i class="fa fa-trash"></i></button>
        </div>
      </div>

      <div class="bc-progress">
        <div class="bc-bar-bg">
          <div class="bc-bar-fill ${lvl}" style="width:${pct}%"></div>
        </div>
        <div class="bc-stats">
          <span class="bc-spent"><strong>${b.spent_formatted}</strong> spent</span>
          <span class="bc-pct ${lvl}">${b.percentage}%</span>
        </div>
        <div class="bc-remaining${over ? ' over' : ''}">
          <span>${over ? 'Over by ' + formatETB(Math.abs(b.remaining)) : formatETB(b.remaining) + ' remaining'}</span>
          <span>of ${b.amount_formatted}</span>
        </div>
      </div>
    </div>`;
}

function renderBudgets() {
  const filtered = activePeriod === 'all'
    ? allBudgets
    : allBudgets.filter(b => b.period === activePeriod);

  updateSummary(filtered);

  if (!filtered.length) {
    budgetList.innerHTML = '';
    budgetEmpty.classList.remove('hidden');
    return;
  }
  budgetEmpty.classList.add('hidden');
  budgetList.innerHTML = filtered.map(renderCard).join('');
}

// ── Fetch budgets ────────────────────────────────────────────
async function loadBudgets() {
  try {
    const res  = await fetch(apiUrl('/api/get_budgets.php', '/dev/mock_budgets.php'));
    const json = await res.json();
    if (!json.success) { showToast(json.message || 'Failed to load.', 'error'); return; }
    allBudgets = json.data;
    renderBudgets();
  } catch {
    showToast('Unable to load budgets.', 'error');
  }
}

// ── Fetch categories for select ──────────────────────────────
async function loadCategories() {
  try {
    const res  = await fetch(apiUrl('/api/get_categories.php', '/dev/mock_categories.php'));
    const json = await res.json();
    if (!json.success) return;
    budgetCat.innerHTML = '<option value="">Select a category…</option>' +
      json.data.map(c => {
        const isEmoji = c.icon && !c.icon.startsWith('/');
        const label   = isEmoji ? `${c.icon} ${c.name}` : c.name;
        return `<option value="${c.id}">${label}</option>`;
      }).join('');
  } catch { /* silent */ }
}

// ── Save ─────────────────────────────────────────────────────
budgetForm.addEventListener('submit', async e => {
  e.preventDefault();
  const id     = budgetId.value;
  const cat    = budgetCat.value;
  const amt    = parseFloat(budgetAmt.value);
  const period = budgetPer.value;
  let ok = true;

  if (!cat) {
    budgetCat.classList.add('err'); catErr.classList.add('show'); ok = false;
  } else {
    budgetCat.classList.remove('err'); catErr.classList.remove('show');
  }
  if (!amt || amt <= 0 || amt >= 100000) {
    budgetAmt.classList.add('err'); amtErr.classList.add('show'); ok = false;
  } else {
    budgetAmt.classList.remove('err'); amtErr.classList.remove('show');
  }
  if (!ok) return;

  const isEdit = parseInt(id) > 0;
  const url    = isEdit ? '/api/edit_budget.php' : '/api/add_budget.php';
  const fd     = new FormData();
  fd.append('category_id', cat);
  fd.append('amount', amt);
  fd.append('period', period);
  if (isEdit) fd.append('id', id);
  appendCsrfToken(fd);

  saveBtn.disabled    = true;
  saveBtn.textContent = 'Saving…';

  try {
    const res  = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if (!json.success) { showToast(json.message || 'Failed to save.', 'error'); return; }
    showToast(isEdit ? 'Budget updated.' : 'Budget added.', 'success');
    closeModal();
    loadBudgets();
  } catch {
    showToast('Unable to save budget.', 'error');
  } finally {
    saveBtn.disabled    = false;
    saveBtn.textContent = 'Save budget';
  }
});

// ── Delete ───────────────────────────────────────────────────
async function deleteBudget(id) {
  const fd = new FormData();
  fd.append('id', id);
  appendCsrfToken(fd);
  try {
    const res  = await fetch('/api/delete_budget.php', { method: 'POST', body: fd });
    const json = await res.json();
    if (!json.success) { showToast(json.message || 'Failed to delete.', 'error'); return; }
    showToast('Budget deleted.', 'success');
    loadBudgets();
  } catch {
    showToast('Unable to delete budget.', 'error');
  }
}

// ── Event delegation ─────────────────────────────────────────
budgetList.addEventListener('click', e => {
  const editBtn = e.target.closest('.edit-btn');
  const delBtn  = e.target.closest('.delete-btn');

  if (editBtn) {
    const b = allBudgets.find(x => String(x.id) === String(editBtn.dataset.id));
    if (!b) return;
    budgetId.value  = b.id;
    budgetAmt.value = b.amount;
    setPeriodToggle(b.period);
    // set category after a tick to ensure select options are rendered
    setTimeout(() => { budgetCat.value = String(b.category_id); }, 0);
    openModal('Edit Budget');
  }

  if (delBtn) {
    showConfirm({
      title: 'Delete Budget',
      message: 'Are you sure you want to delete this budget?',
      confirmText: 'Delete',
      cancelText: 'Cancel',
      type: 'danger',
      onConfirm: () => deleteBudget(delBtn.dataset.id)
    });
  }
});

// ── Period tabs ──────────────────────────────────────────────
document.querySelectorAll('.period-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    activePeriod = tab.dataset.period;
    renderBudgets();
  });
});

// ── Open / close ─────────────────────────────────────────────
document.getElementById('fab-add').addEventListener('click',     () => openModal('Add Budget'));
document.getElementById('empty-add-btn').addEventListener('click',() => openModal('Add Budget'));
document.getElementById('close-modal').addEventListener('click',  closeModal);
document.getElementById('cancel-modal').addEventListener('click', closeModal);
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

// ── Init ─────────────────────────────────────────────────────
(async () => {
  await loadCategories();
  await loadBudgets();
})();
