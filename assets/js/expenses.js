const searchInput = document.getElementById('search-text');
const filterCategorySelect = document.getElementById('filter-category');
const filterDateFrom = document.getElementById('filter-date-from');
const filterDateTo = document.getElementById('filter-date-to');
const filterMinAmount = document.getElementById('filter-min-amount');
const filterMaxAmount = document.getElementById('filter-max-amount');
const expensesList = document.getElementById('expenses-list');
const expensesEmptyState = document.getElementById('expenses-empty');
const expenseModal = document.getElementById('expense-modal');
const openExpenseModalButton = document.getElementById('fab-add-expense');
const closeExpenseModalButton = document.getElementById('close-expense-modal');
const cancelExpenseButton = document.getElementById('cancel-expense');
const expenseForm = document.getElementById('expense-form');
const expenseIdInput = document.getElementById('expense-id');
const expenseAmountInput = document.getElementById('expense-amount');
const expenseCategorySelect = document.getElementById('expense-category');
const expenseDateInput = document.getElementById('expense-date');
const expenseNoteInput = document.getElementById('expense-note');
const tabs = Array.from(document.querySelectorAll('.tab'));
const manualTab = document.getElementById('manual-tab');
const smsTab = document.getElementById('sms-tab');
const parseSmsButton = document.getElementById('parse-sms');
const smsTextInput = document.getElementById('sms-text');
const smsPreview = document.getElementById('sms-preview');

let expensesData = [];
let debounceTimeout = null;

function toggleExpenseModal(show) {
  if (show) {
    expenseModal.classList.add('show');
    document.body.style.overflow = 'hidden';
  } else {
    expenseModal.classList.remove('show');
    document.body.style.overflow = '';
  }
}

function resetExpenseForm() {
  expenseIdInput.value = '0';
  expenseAmountInput.value = '';
  expenseCategorySelect.value = '';
  expenseDateInput.value = new Date().toISOString().split('T')[0];
  expenseNoteInput.value = '';
  smsTextInput.value = '';
  smsPreview.classList.add('hidden');
  tabs.forEach((tab, index) => {
    tab.classList.toggle('active', index === 0);
  });
  manualTab.classList.remove('hidden');
  smsTab.classList.add('hidden');
}

function switchTab(tabName) {
  tabs.forEach(tab => {
    tab.classList.toggle('active', tab.dataset.tab === tabName);
  });
  manualTab.classList.toggle('hidden', tabName !== 'manual');
  smsTab.classList.toggle('hidden', tabName !== 'sms');
}

async function loadCategories() {
  try {
    const response = await fetch('/api/get_categories.php');
    const result = await response.json();
    if (!result.success) {
      showPopup({type: 'danger', title: 'Error', message: result.message});
      return;
    }
    const options = result.data.map(category => `
      <option value="${category.id}">${category.icon} ${category.name}</option>
    `).join('');
    filterCategorySelect.innerHTML = `<option value="0">All categories</option>${options}`;
    expenseCategorySelect.innerHTML = `<option value="">Select category</option>${options}`;
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load categories.'});
  }
}

async function loadExpenses() {
  const params = new URLSearchParams();
  if (searchInput.value.trim()) params.append('search', searchInput.value.trim());
  if (filterCategorySelect.value) params.append('category_id', filterCategorySelect.value);
  if (filterDateFrom.value) params.append('date_from', filterDateFrom.value);
  if (filterDateTo.value) params.append('date_to', filterDateTo.value);
  if (filterMinAmount.value) params.append('amount_min', filterMinAmount.value);
  if (filterMaxAmount.value) params.append('amount_max', filterMaxAmount.value);

  try {
    const response = await fetch(`/api/get_expenses.php?${params.toString()}`);
    const result = await response.json();
    if (!result.success) {
      showPopup({type: 'danger', title: 'Error', message: result.message});
      return;
    }
    expensesData = result.data;
    renderExpenses();
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load expenses.'});
  }
}

function renderExpenses() {
  if (expensesData.length === 0) {
    expensesList.innerHTML = '';
    expensesEmptyState.classList.remove('hidden');
    return;
  }

  expensesEmptyState.classList.add('hidden');
  expensesList.innerHTML = expensesData.map(expense => `
    <div class="list-item">
      <div class="list-item-left">
        <div class="list-item-icon">${expense.category_icon}</div>
        <div class="list-item-content">
          <div class="list-item-title">${expense.category_name}</div>
          <div class="list-item-subtitle">${expense.note || 'No note'} · ${expense.expense_date_formatted}</div>
        </div>
      </div>
      <div class="text-right">
        <div class="list-item-amount">${expense.amount_formatted}</div>
        <div class="list-item-actions">
          <button type="button" class="edit-expense" data-id="${expense.id}"><i class="fa fa-pen"></i></button>
          <button type="button" class="delete-expense" data-id="${expense.id}"><i class="fa fa-trash"></i></button>
        </div>
      </div>
    </div>
  `).join('');
}

function formatExpenseForEdit(expense) {
  expenseIdInput.value = expense.id;
  expenseAmountInput.value = expense.amount;
  expenseCategorySelect.value = expense.category_id || expense.category_id;
  expenseDateInput.value = expense.expense_date;
  expenseNoteInput.value = expense.note;
}

function handleExpenseAction(event) {
  const editButton = event.target.closest('.edit-expense');
  const deleteButton = event.target.closest('.delete-expense');

  if (editButton) {
    const id = editButton.dataset.id;
    const expense = expensesData.find(item => item.id === parseInt(id, 10));
    if (!expense) return;
    switchTab('manual');
    expenseModal.querySelector('#expense-modal-title').textContent = 'Edit Expense';
    formatExpenseForEdit(expense);
    toggleExpenseModal(true);
    return;
  }

  if (deleteButton) {
    const id = deleteButton.dataset.id;
    deleteExpense(parseInt(id, 10));
  }
}

function deleteExpense(id) {
  showConfirm({
    title: 'Delete Expense',
    message: 'Are you sure you want to delete this expense?',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    type: 'danger',
    onConfirm: async () => {
      try {
        const formData = new FormData();
        formData.append('id', id);
        appendCsrfToken(formData);

        const response = await fetch('/api/delete_expense.php', {
          method: 'POST',
          body: formData
        }).then(res => res.json());

        if (!response.success) {
          showToast(response.message || 'Unable to delete expense.', 'error');
          return;
        }

        showToast('Deleted successfully', 'success');
        loadExpenses();
      } catch (error) {
        showToast('Unable to delete expense.', 'error');
      }
    }
  });
}

expenseForm.addEventListener('submit', async event => {
  event.preventDefault();
  const id = expenseIdInput.value;
  const formData = new FormData(expenseForm);
  appendCsrfToken(formData);
  const url = id && parseInt(id, 10) > 0 ? '/api/edit_expense.php' : '/api/add_expense.php';

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData
    }).then(res => res.json());

    if (!response.success) {
      showToast(response.message || 'Unable to save expense.', 'error');
      return;
    }

    const isEdit = id && parseInt(id, 10) > 0;
    if (isEdit) {
      showToast('Updated successfully', 'success');
    } else if (response.popup) {
      showPopup(response.popup);
    } else {
      showToast('Saved successfully', 'success');
    }

    resetExpenseForm();
    toggleExpenseModal(false);
    loadExpenses();
  } catch (error) {
    showToast('Unable to save expense.', 'error');
  }
});

openExpenseModalButton.addEventListener('click', () => {
  expenseModal.querySelector('#expense-modal-title').textContent = 'Add Expense';
  resetExpenseForm();
  switchTab('manual');
  toggleExpenseModal(true);
});

closeExpenseModalButton.addEventListener('click', () => toggleExpenseModal(false));
cancelExpenseButton.addEventListener('click', () => toggleExpenseModal(false));
expenseModal.addEventListener('click', event => {
  if (event.target === expenseModal) {
    toggleExpenseModal(false);
  }
});

tabs.forEach(tab => {
  tab.addEventListener('click', () => {
    switchTab(tab.dataset.tab);
  });
});

parseSmsButton.addEventListener('click', async () => {
  const text = smsTextInput.value.trim();
  if (!text) {
    showPopup({type: 'warning', title: 'Validation', message: 'Paste SMS text first.'});
    return;
  }

  try {
    const formData = new FormData();
    formData.append('text', text);
    appendCsrfToken(formData);
    const response = await fetch('/api/parse_sms.php', {
      method: 'POST',
      body: formData
    }).then(res => res.json());

    if (!response.success) {
      showPopup({type: 'danger', title: 'Error', message: response.message});
      return;
    }

    if (response.data.amount) {
      expenseAmountInput.value = response.data.amount;
    }
    if (response.data.note) {
      expenseNoteInput.value = response.data.note;
    }
    if (response.data.expense_date) {
      expenseDateInput.value = response.data.expense_date;
    }

    smsPreview.textContent = 'SMS parsed successfully. Check the manual entry tab to review values.';
    smsPreview.classList.remove('hidden');
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to parse SMS.'});
  }
});

function applyFilterEvents() {
  const onFilterChange = () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(loadExpenses, 300);
  };

  searchInput.addEventListener('input', onFilterChange);
  filterCategorySelect.addEventListener('change', loadExpenses);
  filterDateFrom.addEventListener('change', loadExpenses);
  filterDateTo.addEventListener('change', loadExpenses);
  filterMinAmount.addEventListener('input', onFilterChange);
  filterMaxAmount.addEventListener('input', onFilterChange);
}

expensesList.addEventListener('click', handleExpenseAction);

(async function init() {
  await loadCategories();
  applyFilterEvents();
  loadExpenses();
})();

