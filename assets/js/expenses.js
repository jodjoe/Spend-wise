/**
 * Expenses Page — Add, Edit, Delete, Filter
 * All DOM queries run after DOMContentLoaded to prevent null crashes.
 */

document.addEventListener('DOMContentLoaded', function () {

  // ── DOM refs ────────────────────────────────────────────────
  const searchInput          = document.getElementById('search-text');
  const filterCategorySelect = document.getElementById('filter-category');
  const filterDateFrom       = document.getElementById('filter-date-from');
  const filterDateTo         = document.getElementById('filter-date-to');
  const filterMinAmount      = document.getElementById('filter-min-amount');
  const filterMaxAmount      = document.getElementById('filter-max-amount');
  const clearFiltersBtn      = document.getElementById('clear-filters');
  const expensesList         = document.getElementById('expenses-list');
  const expensesLoading      = document.getElementById('expenses-loading');
  const expensesEmptyState   = document.getElementById('expenses-empty');
  const expenseCountLabel    = document.getElementById('expense-count-label');
  const expenseTotalLabel    = document.getElementById('expense-total-label');

  const expenseModal         = document.getElementById('expense-modal');
  const fabBtn               = document.getElementById('fab-add-expense');
  const closeModalBtn        = document.getElementById('close-expense-modal');
  const cancelBtn            = document.getElementById('cancel-expense');
  const expenseForm          = document.getElementById('expense-form');
  const expenseIdInput       = document.getElementById('expense-id');
  const amountInput          = document.getElementById('expense-amount');
  const categorySelect       = document.getElementById('expense-category');
  const dateInput            = document.getElementById('expense-date');
  const noteInput            = document.getElementById('expense-note');
  const amountError          = document.getElementById('amount-error');
  const categoryError        = document.getElementById('category-error');
  const modalTitle           = document.getElementById('expense-modal-title');
  const saveBtn              = document.getElementById('save-expense');

  // SMS tab elements (may not exist if removed from HTML)
  const parseSmsBtn  = document.getElementById('parse-sms');
  const smsTextInput = document.getElementById('sms-text');
  const smsPreview   = document.getElementById('sms-preview');
  const manualTab    = document.getElementById('manual-tab');
  const smsTab       = document.getElementById('sms-tab');
  const tabBtns      = Array.from(document.querySelectorAll('#expense-modal .tab'));

  let expensesData  = [];
  let debounceTimer = null;

  // ── CSRF ────────────────────────────────────────────────────
  function getCsrf() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }
  // expose both names used across the codebase
  window.appendCsrfToken = function (fd) { fd.append('csrf_token', getCsrf()); };
  function appendCsrf(fd) { fd.append('csrf_token', getCsrf()); }

  // ── Loading state ───────────────────────────────────────────
  function setLoading(on) {
    expensesLoading.classList.toggle('hidden', !on);
    expensesList.classList.toggle('hidden', on);
  }

  // ── Modal open / close ──────────────────────────────────────
  function openModal() {
    expenseModal.classList.add('show');
    document.body.style.overflow = 'hidden';
    // Focus first input after animation
    setTimeout(() => amountInput && amountInput.focus(), 150);
  }

  function closeModal() {
    expenseModal.classList.remove('show');
    document.body.style.overflow = '';
  }

  function resetForm() {
    expenseIdInput.value = '0';
    expenseForm.reset();
    dateInput.value = new Date().toISOString().split('T')[0];
    if (smsTextInput) smsTextInput.value = '';
    if (smsPreview)   smsPreview.classList.add('hidden');
    amountError.classList.remove('show');
    categoryError.classList.remove('show');
    switchTab('manual');
  }

  // ── Tab switching ───────────────────────────────────────────
  function switchTab(name) {
    tabBtns.forEach(t => t.classList.toggle('active', t.dataset.tab === name));
    if (manualTab) manualTab.classList.toggle('hidden', name !== 'manual');
    if (smsTab)    smsTab.classList.toggle('hidden',    name !== 'sms');
  }

  tabBtns.forEach(t => t.addEventListener('click', () => switchTab(t.dataset.tab)));

  // ── FAB — open Add modal ────────────────────────────────────
  fabBtn.addEventListener('click', () => {
    modalTitle.textContent = 'Add Expense';
    resetForm();
    openModal();
  });

  // ── Close modal ─────────────────────────────────────────────
  closeModalBtn.addEventListener('click', closeModal);
  cancelBtn.addEventListener('click', closeModal);
  expenseModal.addEventListener('click', e => {
    if (e.target === expenseModal) closeModal();
  });

  // ── Load categories into both selects ───────────────────────
  async function loadCategories() {
    try {
      const res  = await fetch('/api/get_categories.php');
      const json = await res.json();
      if (!json.success) return;

      const opts = json.data.map(c => {
        const isEmoji = c.icon && !c.icon.startsWith('/');
        const label   = isEmoji ? `${c.icon} ${c.name}` : c.name;
        return `<option value="${c.id}">${label}</option>`;
      }).join('');

      filterCategorySelect.innerHTML = `<option value="0">All categories</option>${opts}`;
      categorySelect.innerHTML       = `<option value="">Select a category</option>${opts}`;
    } catch (_) {
      showToast('Could not load categories.', 'error');
    }
  }

  // ── Fetch & render expenses ─────────────────────────────────
  async function loadExpenses() {
    setLoading(true);
    expensesEmptyState.classList.add('hidden');

    const p = new URLSearchParams();
    const search = searchInput.value.trim();
    const cat    = filterCategorySelect.value;
    const from   = filterDateFrom.value;
    const to     = filterDateTo.value;
    const min    = filterMinAmount.value;
    const max    = filterMaxAmount.value;

    if (search)          p.append('search',      search);
    if (cat && cat !== '0') p.append('category_id', cat);
    if (from)            p.append('date_from',   from);
    if (to)              p.append('date_to',     to);
    if (min)             p.append('amount_min',  min);
    if (max)             p.append('amount_max',  max);

    try {
      const res  = await fetch(`/api/get_expenses.php?${p}`);
      const json = await res.json();
      setLoading(false);
      if (!json.success) { showToast(json.message || 'Could not load expenses.', 'error'); return; }
      expensesData = json.data;
      renderExpenses();
    } catch (_) {
      setLoading(false);
      showToast('Could not load expenses.', 'error');
    }
  }

  function renderExpenses() {
    const total = expensesData.reduce((s, e) => s + e.amount, 0);
    expenseCountLabel.textContent = `${expensesData.length} expense${expensesData.length !== 1 ? 's' : ''}`;
    expenseTotalLabel.textContent = `Total: ${total.toFixed(2)} ETB`;

    if (expensesData.length === 0) {
      expensesList.innerHTML = '';
      expensesEmptyState.classList.remove('hidden');
      return;
    }
    expensesEmptyState.classList.add('hidden');

    // Group by date
    const groups = {};
    expensesData.forEach(e => {
      if (!groups[e.expense_date]) groups[e.expense_date] = [];
      groups[e.expense_date].push(e);
    });

    expensesList.innerHTML = Object.entries(groups).map(([, items]) => {
      const dayTotal = items.reduce((s, i) => s + i.amount, 0);
      const rows = items.map(ex => `
        <div class="list-item">
          <div class="list-item-left">
            <div class="list-item-icon">${ex.category_icon}</div>
            <div class="list-item-content">
              <div class="list-item-title">${ex.category_name}</div>
              <div class="list-item-subtitle">${ex.note || '<em style="color:var(--text-muted)">No note</em>'}</div>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
            <div class="list-item-amount">${ex.amount_formatted}</div>
            <div class="list-item-actions">
              <button type="button" class="edit-expense" data-id="${ex.id}" title="Edit"><i class="fa fa-pen"></i></button>
              <button type="button" class="delete-expense" data-id="${ex.id}" title="Delete" style="color:var(--danger);"><i class="fa fa-trash"></i></button>
            </div>
          </div>
        </div>`).join('');

      return `
        <div style="margin-bottom:4px;">
          <div class="flex-between" style="padding:6px 4px 4px;">
            <span style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">${items[0].expense_date_formatted}</span>
            <span style="font-size:12px;color:var(--text-muted);">${dayTotal.toFixed(2)} ETB</span>
          </div>
          ${rows}
        </div>`;
    }).join('');
  }

  // ── Edit / Delete delegation ────────────────────────────────
  expensesList.addEventListener('click', e => {
    const editBtn   = e.target.closest('.edit-expense');
    const deleteBtn = e.target.closest('.delete-expense');

    if (editBtn) {
      const ex = expensesData.find(x => x.id === parseInt(editBtn.dataset.id, 10));
      if (!ex) return;
      modalTitle.textContent  = 'Edit Expense';
      resetForm();
      expenseIdInput.value    = ex.id;
      amountInput.value       = ex.amount;
      dateInput.value         = ex.expense_date;
      noteInput.value         = ex.note || '';
      // Set category after a tick so options are rendered
      setTimeout(() => { categorySelect.value = String(ex.category_id); }, 0);
      switchTab('manual');
      openModal();
    }

    if (deleteBtn) {
      const id = parseInt(deleteBtn.dataset.id, 10);
      showConfirm({
        title: 'Delete Expense',
        message: 'Delete this expense? This cannot be undone.',
        confirmText: 'Delete',
        cancelText: 'Cancel',
        type: 'danger',
        onConfirm: async () => {
          try {
            const fd = new FormData();
            fd.append('id', id);
            appendCsrf(fd);
            const res  = await fetch('/api/delete_expense.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (!json.success) { showToast(json.message || 'Delete failed.', 'error'); return; }
            showToast('Expense deleted.', 'success');
            loadExpenses();
          } catch (_) {
            showToast('Delete failed.', 'error');
          }
        }
      });
    }
  });

  // ── Validation ──────────────────────────────────────────────
  function validateForm() {
    let ok = true;
    const amt = parseFloat(amountInput.value);
    if (!amt || amt <= 0 || amt >= 100000) {
      amountError.classList.add('show'); ok = false;
    } else {
      amountError.classList.remove('show');
    }
    if (!categorySelect.value) {
      categoryError.classList.add('show'); ok = false;
    } else {
      categoryError.classList.remove('show');
    }
    return ok;
  }

  // ── Save (add or edit) ──────────────────────────────────────
  expenseForm.addEventListener('submit', async e => {
    e.preventDefault();
    if (!validateForm()) return;

    saveBtn.disabled    = true;
    saveBtn.innerHTML   = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> Saving…';

    const id  = parseInt(expenseIdInput.value, 10);
    const url = id > 0 ? '/api/edit_expense.php' : '/api/add_expense.php';
    const fd  = new FormData(expenseForm);
    appendCsrf(fd);

    try {
      const res  = await fetch(url, { method: 'POST', body: fd });
      const json = await res.json();

      if (!json.success) {
        showToast(json.message || 'Could not save expense.', 'error');
        return;
      }

      closeModal();
      resetForm();
      loadExpenses();

      if (id > 0) {
        showToast('Expense updated.', 'success');
      } else if (json.data && json.data.popup) {
        showPopup(json.data.popup);
      } else {
        showToast('Expense saved!', 'success');
      }
    } catch (_) {
      showToast('Could not save expense.', 'error');
    } finally {
      saveBtn.disabled  = false;
      saveBtn.innerHTML = '<i class="fa fa-floppy-disk"></i> Save';
    }
  });

  // ── SMS parsing (optional — only if element exists) ─────────
  if (parseSmsBtn) {
    parseSmsBtn.addEventListener('click', async () => {
      const text = smsTextInput.value.trim();
      if (!text) { showToast('Paste an SMS first.', 'warning'); return; }

      parseSmsBtn.disabled  = true;
      parseSmsBtn.innerHTML = '<span class="spinner" style="width:14px;height:14px;border-width:2px;"></span> Parsing…';

      try {
        const fd = new FormData();
        fd.append('text', text);
        appendCsrf(fd);
        const res  = await fetch('/api/parse_sms.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (!json.success) { showToast(json.message || 'Could not parse SMS.', 'error'); return; }

        const d = json.data;
        if (d.amount)       amountInput.value = d.amount;
        if (d.note)         noteInput.value   = d.note;
        if (d.expense_date) dateInput.value   = d.expense_date;

        if (smsPreview) {
          smsPreview.textContent = '✓ Parsed! Review the fields below and save.';
          smsPreview.classList.remove('hidden');
        }
        switchTab('manual');
      } catch (_) {
        showToast('Could not parse SMS.', 'error');
      } finally {
        parseSmsBtn.disabled  = false;
        parseSmsBtn.innerHTML = '<i class="fa fa-wand-magic-sparkles"></i> Parse with AI';
      }
    });
  }

  // ── Filters ─────────────────────────────────────────────────
  function debounce(fn, ms = 350) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fn, ms);
  }

  searchInput.addEventListener('input',           () => debounce(loadExpenses));
  filterMinAmount.addEventListener('input',       () => debounce(loadExpenses));
  filterMaxAmount.addEventListener('input',       () => debounce(loadExpenses));
  filterCategorySelect.addEventListener('change', loadExpenses);
  filterDateFrom.addEventListener('change',       loadExpenses);
  filterDateTo.addEventListener('change',         loadExpenses);

  clearFiltersBtn.addEventListener('click', () => {
    searchInput.value          = '';
    filterCategorySelect.value = '0';
    filterDateFrom.value       = '';
    filterDateTo.value         = '';
    filterMinAmount.value      = '';
    filterMaxAmount.value      = '';
    loadExpenses();
  });

  // ── Init ────────────────────────────────────────────────────
  loadCategories().then(() => loadExpenses());

}); // end DOMContentLoaded
