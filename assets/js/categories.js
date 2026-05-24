const categoryModal = document.getElementById('category-modal');
const categoryForm = document.getElementById('category-form');
const categoryIdInput = document.getElementById('category-id');
const categoryNameInput = document.getElementById('category-name');
const categoryIconInput = document.getElementById('category-icon');
const emojiPicker = document.getElementById('emoji-picker');
const defaultCategoriesContainer = document.getElementById('default-categories');
const customCategoriesContainer = document.getElementById('custom-categories');
const categoriesEmptyState = document.getElementById('categories-empty');
const openAddCategoryButton = document.getElementById('open-add-category');
const fabAddCategory = document.getElementById('fab-add-category');
const closeCategoryModalButton = document.getElementById('close-category-modal');
const cancelCategoryButton = document.getElementById('cancel-category');

const emojiOptions = ['🍵', '🍛', '🚌', '👕', '📚', '📱', '☕', '🎮', '🏥', '🔀', '🍽️', '🛒', '🎓', '🧾', '🏠', '🛍️', '💡', '🎉', '🚕', '🧺'];
let categoriesData = [];

function toggleCategoryModal(show) {
  if (show) {
    categoryModal.classList.add('show');
    document.body.style.overflow = 'hidden';
  } else {
    categoryModal.classList.remove('show');
    document.body.style.overflow = '';
  }
}

function resetCategoryForm() {
  categoryIdInput.value = '0';
  categoryNameInput.value = '';
  categoryIconInput.value = '💰';
  categoryNameInput.focus();
  document.querySelectorAll('#emoji-picker button').forEach(button => button.classList.remove('selected'));
  const defaultButton = document.querySelector('#emoji-picker button[data-emoji="💰"]');
  if (defaultButton) {
    defaultButton.classList.add('selected');
  }
}

function buildEmojiPicker() {
  emojiPicker.innerHTML = emojiOptions.map(emoji => `
    <button type="button" class="grid-item${emoji === '💰' ? ' selected' : ''}" data-emoji="${emoji}">
      <div class="grid-item-emoji">${emoji}</div>
    </button>
  `).join('');
}

async function fetchCategories() {
  try {
    const response = await fetch('/api/get_categories.php');
    const result = await response.json();
    if (!result.success) {
      showPopup({type: 'danger', title: 'Error', message: result.message});
      return;
    }
    categoriesData = result.data;
    renderCategories();
  } catch (error) {
    showPopup({type: 'danger', title: 'Error', message: 'Unable to load categories.'});
  }
}

function renderCategories() {
  const defaultItems = categoriesData.filter(category => parseInt(category.is_default, 10) === 1);
  const customItems = categoriesData.filter(category => parseInt(category.is_default, 10) === 0);

  defaultCategoriesContainer.innerHTML = defaultItems.map(category => `
    <div class="grid-item">
      <div class="grid-item-emoji">${category.icon}</div>
      <div class="grid-item-label">${category.name}</div>
    </div>
  `).join('');

  customCategoriesContainer.innerHTML = customItems.map(category => `
    <div class="grid-item">
      <div class="grid-item-emoji">${category.icon}</div>
      <div class="grid-item-label">${category.name}</div>
      <div class="mt-12 flex gap-8 justify-center">
        <button type="button" class="btn btn-outline btn-icon edit-category" data-id="${category.id}"><i class="fa fa-pen"></i></button>
        <button type="button" class="btn btn-outline btn-icon delete-category" data-id="${category.id}"><i class="fa fa-trash"></i></button>
      </div>
    </div>
  `).join('');

  categoriesEmptyState.classList.toggle('hidden', customItems.length > 0);
}

function handleCategoryAction(event) {
  const editButton = event.target.closest('.edit-category');
  const deleteButton = event.target.closest('.delete-category');

  if (editButton) {
    const id = editButton.dataset.id;
    const category = categoriesData.find(item => item.id === id || item.id === parseInt(id, 10));
    if (!category) return;

    categoryIdInput.value = category.id;
    categoryNameInput.value = category.name;
    categoryIconInput.value = category.icon;
    document.querySelectorAll('#emoji-picker button').forEach(button => {
      button.classList.toggle('selected', button.dataset.emoji === category.icon);
    });
    document.getElementById('category-modal-title').textContent = 'Edit Category';
    toggleCategoryModal(true);
    return;
  }

  if (deleteButton) {
    const id = deleteButton.dataset.id;
    confirmDeleteCategory(id);
  }
}

function confirmDeleteCategory(id) {
  const runDelete = async (force = false) => {
    try {
      const formData = new FormData();
      formData.append('id', id);
      if (force) {
        formData.append('confirm', '1');
      }
      appendCsrfToken(formData);

      const response = await fetch('/api/delete_category.php', {
        method: 'POST',
        body: formData
      }).then(res => res.json());

      if (!response.success) {
        showToast(response.message || 'Unable to delete category.', 'error');
        return;
      }

      showToast('Deleted successfully', 'success');
      fetchCategories();
    } catch (error) {
      showToast('Unable to delete category.', 'error');
    }
  };

  showConfirm({
    title: 'Delete Category',
    message: 'Are you sure you want to delete this category?',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    type: 'danger',
    onConfirm: async () => {
      try {
        const formData = new FormData();
        formData.append('id', id);
        appendCsrfToken(formData);
        const response = await fetch('/api/delete_category.php', {
          method: 'POST',
          body: formData
        }).then(res => res.json());

        if (!response.success && response.warning) {
          showConfirm({
            title: 'Move Expenses',
            message: `${response.message}\n\nDo you want to move expenses to Other and delete this category?`,
            confirmText: 'Move and delete',
            cancelText: 'Cancel',
            type: 'warning',
            onConfirm: () => runDelete(true)
          });
          return;
        }

        if (!response.success) {
          showToast(response.message || 'Unable to delete category.', 'error');
          return;
        }

        showToast('Deleted successfully', 'success');
        fetchCategories();
      } catch (error) {
        showToast('Unable to delete category.', 'error');
      }
    }
  });
}

categoryForm.addEventListener('submit', async event => {
  event.preventDefault();
  const id = categoryIdInput.value;
  const name = categoryNameInput.value.trim();
  const icon = categoryIconInput.value;

  if (!name) {
    showToast('Category name is required.', 'warning');
    return;
  }

  const formData = new FormData();
  formData.append('name', name);
  formData.append('icon', icon);
  appendCsrfToken(formData);

  let url = '/api/add_category.php';
  if (id && parseInt(id, 10) > 0) {
    url = '/api/edit_category.php';
    formData.append('id', id);
  }

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData
    }).then(res => res.json());

    if (!response.success) {
      showToast(response.message || 'Unable to save category.', 'error');
      return;
    }

    const isEdit = id && parseInt(id, 10) > 0;
    showToast(isEdit ? 'Updated successfully' : 'Saved successfully', 'success');
    toggleCategoryModal(false);
    resetCategoryForm();
    fetchCategories();
  } catch (error) {
    showToast('Unable to save category.', 'error');
  }
});

emojiPicker.addEventListener('click', event => {
  const target = event.target.closest('button[data-emoji]');
  if (!target) return;
  document.querySelectorAll('#emoji-picker button').forEach(button => button.classList.remove('selected'));
  target.classList.add('selected');
  categoryIconInput.value = target.dataset.emoji;
});

openAddCategoryButton.addEventListener('click', () => {
  document.getElementById('category-modal-title').textContent = 'Add Category';
  resetCategoryForm();
  toggleCategoryModal(true);
});

fabAddCategory.addEventListener('click', () => {
  document.getElementById('category-modal-title').textContent = 'Add Category';
  resetCategoryForm();
  toggleCategoryModal(true);
});

closeCategoryModalButton.addEventListener('click', () => toggleCategoryModal(false));
cancelCategoryButton.addEventListener('click', () => toggleCategoryModal(false));
categoryModal.addEventListener('click', event => {
  if (event.target === categoryModal) {
    toggleCategoryModal(false);
  }
});
customCategoriesContainer.addEventListener('click', handleCategoryAction);

buildEmojiPicker();
fetchCategories();

