const budgetsGrid = document.getElementById('budgets-grid');
const budgetsEmptyState = document.getElementById('budgets-empty');
const budgetModal = document.getElementById('budget-modal');
const openBudgetModalButton = document.getElementById('fab-add-budget');
const closeBudgetModalButton = document.getElementById('close-budget-modal');
const cancelBudgetButton = document.getElementById('cancel-budget');
const budgetForm = document.getElementById('budget-form');
const budgetIdInput = document.getElementById('budget-id');
const budgetCategorySelect = document.getElementById('budget-category');
const budgetAmountInput = document.getElementById('budget-amount');
const budgetPeriodSelect = document.getElementById('budget-period');

let budgetsData = [];

function toggleBudgetModal(show) {
  if (show) {
    budgetModal.classList.add('show');
    document.body.style.overflow = 'hidden';
  } else {
    budgetModal.classList.remove('show');
    document.body.style.overflow = '';
  }
}

function resetBudgetForm() {
  budgetIdInput.value = '0';
  budgetCategorySelect.value = '';
  budgetAmountInput.value = '';
  budgetPeriodSelect.value = 'monthly';
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
    budgetCategorySelect.innerHTML = `<option value="">Choose category</option>${options}`;
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load categories.'});
  }
}

async function loadBudgets() {
  try {
    const response = await fetch('/api/get_budgets.php');
    const result = await response.json();
    if (!result.success) {
      showPopup({type: 'danger', title: 'Error', message: result.message});
      return;
    }
    budgetsData = result.data;
    renderBudgets();
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load budgets.'});
  }
}

function renderBudgets() {
  if (budgetsData.length === 0) {
    budgetsGrid.innerHTML = '';
    budgetsEmptyState.classList.remove('hidden');
    return;
  }

  budgetsEmptyState.classList.add('hidden');
  budgetsGrid.innerHTML = budgetsData.map(budget => {
    const progressClass = budget.level === 'green' ? 'green' : budget.level === 'yellow' ? 'yellow' : 'red';
    return `
      <div class="card">
        <div class="flex-between mb-12">
          <div>
            <div class="grid-item-emoji">${budget.category_icon}</div>
            <h4>${budget.category_name}</h4>
            <div class="text-muted">${budget.period.charAt(0).toUpperCase() + budget.period.slice(1)} budget</div>
          </div>
          <div class="list-item-actions">
            <button type="button" class="edit-budget" data-id="${budget.id}"><i class="fa fa-pen"></i></button>
            <button type="button" class="delete-budget" data-id="${budget.id}"><i class="fa fa-trash"></i></button>
          </div>
        </div>
        <div class="mb-12">
          <div class="flex-between">
            <span>${budget.spent_formatted} spent</span>
            <span>${budget.remaining_formatted} left</span>
          </div>
          <div class="progress-bar-wrap">
            <div class="progress-bar ${progressClass}" style="width: ${Math.min(budget.percentage, 100)}%;"></div>
          </div>
        </div>
        <div class="flex-between">
          <span>${budget.percentage}% used</span>
          <span>${budget.amount_formatted}</span>
        </div>
      </div>
    `;
  }).join('');
}

function handleBudgetAction(event) {
  const editButton = event.target.closest('.edit-budget');
  const deleteButton = event.target.closest('.delete-budget');

  if (editButton) {
    const id = editButton.dataset.id;
    const budget = budgetsData.find(item => item.id === parseInt(id, 10));
    if (!budget) return;
    budgetIdInput.value = budget.id;
    budgetCategorySelect.value = budget.category_id;
    budgetAmountInput.value = budget.amount;
    budgetPeriodSelect.value = budget.period;
    document.getElementById('budget-modal-title').textContent = 'Edit Budget';
    toggleBudgetModal(true);
    return;
  }

  if (deleteButton) {
    const id = deleteButton.dataset.id;
    deleteBudget(parseInt(id, 10));
  }
}

function deleteBudget(id) {
  showConfirm({
    title: 'Delete Budget',
    message: 'Do you want to delete this budget?',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    type: 'danger',
    onConfirm: async () => {
      try {
        const formData = new FormData();
        formData.append('id', id);
        appendCsrfToken(formData);
        const response = await fetch('/api/delete_budget.php', {
          method: 'POST',
          body: formData
        }).then(res => res.json());

        if (!response.success) {
          showToast(response.message || 'Unable to delete budget.', 'error');
          return;
        }

        showToast('Deleted successfully', 'success');
        loadBudgets();
      } catch (error) {
        showToast('Unable to delete budget.', 'error');
      }
    }
  });
}

budgetForm.addEventListener('submit', async event => {
  event.preventDefault();
  const id = budgetIdInput.value;
  const formData = new FormData(budgetForm);
  appendCsrfToken(formData);
  const url = id && parseInt(id, 10) > 0 ? '/api/edit_budget.php' : '/api/add_budget.php';

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData
    }).then(res => res.json());

    if (!response.success) {
      showToast(response.message || 'Unable to save budget.', 'error');
      return;
    }

    const isEdit = id && parseInt(id, 10) > 0;
    showToast(isEdit ? 'Updated successfully' : 'Saved successfully', 'success');
    toggleBudgetModal(false);
    resetBudgetForm();
    loadBudgets();
  } catch (error) {
    showToast('Unable to save budget.', 'error');
  }
});

openBudgetModalButton.addEventListener('click', () => {
  document.getElementById('budget-modal-title').textContent = 'Add Budget';
  resetBudgetForm();
  toggleBudgetModal(true);
});

closeBudgetModalButton.addEventListener('click', () => toggleBudgetModal(false));
cancelBudgetButton.addEventListener('click', () => toggleBudgetModal(false));
budgetModal.addEventListener('click', event => {
  if (event.target === budgetModal) {
    toggleBudgetModal(false);
  }
});

budgetsGrid.addEventListener('click', handleBudgetAction);

(async function init() {
  await loadCategories();
  loadBudgets();
})();

