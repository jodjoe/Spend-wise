// ── Emoji options (emoji chars only — no image paths) ────────
const EMOJIS = [
  '🍛','🍽️','🥤','🍵','🧃','🍕',
  '🚌','🚕','⛽','✈️','🚲','🛵',
  '👕','👟','🧥','👜','🛍️','💄',
  '📚','🎓','✏️','🖥️','📐','🔬',
  '📱','💡','🏠','🛒','🧺','🔧',
  '☕','🎮','🎬','🎵','🏋️','⚽',
  '🏥','💊','🧘','❤️','🌿','🔀',
];

const DEFAULT_ICON = '💰';

// ── State ────────────────────────────────────────────────────
let allCategories = [];
let searchQuery   = '';

// ── DOM refs ─────────────────────────────────────────────────
const defaultList  = document.getElementById('default-list');
const customList   = document.getElementById('custom-list');
const customEmpty  = document.getElementById('custom-empty');
const defaultCount = document.getElementById('default-count');
const customCount  = document.getElementById('custom-count');
const searchInput  = document.getElementById('cat-search');
const modal        = document.getElementById('cat-modal');
const modalTitle   = document.getElementById('modal-title');
const catForm      = document.getElementById('cat-form');
const catIdInput   = document.getElementById('cat-id');
const catName      = document.getElementById('cat-name');
const catIcon      = document.getElementById('cat-icon');
const nameErr      = document.getElementById('name-err');
const saveBtn      = document.getElementById('save-btn');
const emojiGrid    = document.getElementById('emoji-grid');

// ── Modal ────────────────────────────────────────────────────
function openModal(title = 'Add Category') {
  modalTitle.textContent = title;
  modal.classList.add('show');
  document.body.style.overflow = 'hidden';
  setTimeout(() => catName.focus(), 100);
}

function closeModal() {
  modal.classList.remove('show');
  document.body.style.overflow = '';
  catForm.reset();
  catIdInput.value = '0';
  catName.classList.remove('is-error');
  nameErr.classList.remove('show');
  setActiveEmoji(DEFAULT_ICON);
}

// ── Emoji picker ─────────────────────────────────────────────
function buildEmojiPicker() {
  emojiGrid.innerHTML = EMOJIS.map(e => `
    <button type="button" class="emoji-btn${e === DEFAULT_ICON ? ' active' : ''}" data-emoji="${e}">${e}</button>
  `).join('');
  catIcon.value = DEFAULT_ICON;
}

function setActiveEmoji(emoji) {
  const target = EMOJIS.includes(emoji) ? emoji : DEFAULT_ICON;
  emojiGrid.querySelectorAll('.emoji-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.emoji === target);
  });
  catIcon.value = target;
}

emojiGrid.addEventListener('click', e => {
  const btn = e.target.closest('.emoji-btn');
  if (!btn) return;
  emojiGrid.querySelectorAll('.emoji-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  catIcon.value = btn.dataset.emoji;
});

// ── Render helpers ───────────────────────────────────────────
function filter(list) {
  if (!searchQuery) return list;
  const q = searchQuery.toLowerCase();
  return list.filter(c => c.name.toLowerCase().includes(q));
}

function usageBar(spent, budget) {
  const pct   = budget > 0 ? Math.min(100, Math.round((spent / budget) * 100)) : 0;
  const color = pct >= 85 ? '#cc0000' : pct >= 60 ? '#555' : '#000';
  return `
    <div class="usage-wrap">
      <div class="usage-bar-bg">
        <div class="usage-bar-fill" style="width:${pct}%;background:${color}"></div>
      </div>
      <div class="usage-pct">${pct}%</div>
    </div>`;
}

function renderRow(cat, isDefault) {
  const spent   = parseFloat(cat.month_spent  || 0);
  const budget  = parseFloat(cat.budget_amount || 0);
  const spentFmt = spent > 0
    ? spent.toLocaleString('en-ET', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB'
    : 'No spending';

  const actions = isDefault ? '' : `
    <div class="cat-actions">
      <button class="cat-action-btn edit-btn"   data-id="${cat.id}" title="Edit"><i class="fa fa-pen"></i></button>
      <button class="cat-action-btn danger delete-btn" data-id="${cat.id}" title="Delete"><i class="fa fa-trash"></i></button>
    </div>`;

  return `
    <div class="cat-row" data-id="${cat.id}">
      <div class="cat-icon-wrap">${cat.icon}</div>
      <div class="cat-info">
        <div class="cat-name">${cat.name}</div>
        <div class="cat-meta">
          <span class="cat-spent">${spentFmt}</span>
          <span class="cat-badge${isDefault ? ' default' : ''}">${isDefault ? 'Default' : 'Custom'}</span>
        </div>
      </div>
      ${budget > 0 ? usageBar(spent, budget) : ''}
      ${actions}
    </div>`;
}

function renderCategories() {
  const defaults = filter(allCategories.filter(c => parseInt(c.is_default) === 1));
  const customs  = filter(allCategories.filter(c => parseInt(c.is_default) === 0));

  defaultCount.textContent = defaults.length;
  customCount.textContent  = customs.length;

  defaultList.innerHTML = defaults.length
    ? defaults.map(c => renderRow(c, true)).join('')
    : '<div class="cat-empty"><i class="fa fa-inbox"></i><br>No default categories found.</div>';

  if (customs.length) {
    customList.innerHTML = customs.map(c => renderRow(c, false)).join('');
    customEmpty.classList.add('hidden');
  } else {
    customList.innerHTML = '';
    customEmpty.classList.remove('hidden');
  }
}

// ── Fetch ────────────────────────────────────────────────────
async function fetchCategories() {
  try {
    const res  = await fetch(apiUrl('/api/get_categories.php', '/dev/mock_categories.php'));
    const json = await res.json();
    if (!json.success) { showToast(json.message || 'Failed to load.', 'error'); return; }
    allCategories = json.data;
    renderCategories();
  } catch {
    showToast('Unable to load categories.', 'error');
  }
}

// ── Save ─────────────────────────────────────────────────────
catForm.addEventListener('submit', async e => {
  e.preventDefault();
  const id   = catIdInput.value;
  const name = catName.value.trim();
  const icon = catIcon.value || DEFAULT_ICON;

  if (!name || name.length > 50) {
    catName.classList.add('is-error');
    nameErr.classList.add('show');
    catName.focus();
    return;
  }
  catName.classList.remove('is-error');
  nameErr.classList.remove('show');

  const isEdit = parseInt(id) > 0;
  const url    = isEdit ? '/api/edit_category.php' : '/api/add_category.php';
  const fd     = new FormData();
  fd.append('name', name);
  fd.append('icon', icon);
  if (isEdit) fd.append('id', id);
  appendCsrfToken(fd);

  saveBtn.disabled    = true;
  saveBtn.textContent = 'Saving…';

  try {
    const res  = await fetch(url, { method: 'POST', body: fd });
    const json = await res.json();
    if (!json.success) { showToast(json.message || 'Failed to save.', 'error'); return; }
    showToast(isEdit ? 'Category updated.' : 'Category added.', 'success');
    closeModal();
    fetchCategories();
  } catch {
    showToast('Unable to save category.', 'error');
  } finally {
    saveBtn.disabled    = false;
    saveBtn.textContent = 'Save category';
  }
});

// ── Delete ───────────────────────────────────────────────────
async function deleteCategory(id, force = false) {
  const fd = new FormData();
  fd.append('id', id);
  if (force) fd.append('confirm', '1');
  appendCsrfToken(fd);

  try {
    const res  = await fetch('/api/delete_category.php', { method: 'POST', body: fd });
    const json = await res.json();

    if (!json.success && json.warning) {
      showConfirm({
        title: 'Expenses exist',
        message: `${json.message} Move them to "Other" and delete?`,
        confirmText: 'Move & Delete',
        cancelText: 'Cancel',
        type: 'danger',
        onConfirm: () => deleteCategory(id, true)
      });
      return;
    }
    if (!json.success) { showToast(json.message || 'Failed to delete.', 'error'); return; }
    showToast('Category deleted.', 'success');
    fetchCategories();
  } catch {
    showToast('Unable to delete category.', 'error');
  }
}

// ── Event delegation ─────────────────────────────────────────
function handleListClick(e) {
  const editBtn   = e.target.closest('.edit-btn');
  const deleteBtn = e.target.closest('.delete-btn');

  if (editBtn) {
    const cat = allCategories.find(c => String(c.id) === String(editBtn.dataset.id));
    if (!cat) return;
    catIdInput.value = cat.id;
    catName.value    = cat.name;
    setActiveEmoji(cat.icon);
    openModal('Edit Category');
  }

  if (deleteBtn) {
    showConfirm({
      title: 'Delete Category',
      message: 'Are you sure you want to delete this category?',
      confirmText: 'Delete',
      cancelText: 'Cancel',
      type: 'danger',
      onConfirm: () => deleteCategory(deleteBtn.dataset.id)
    });
  }
}

defaultList.addEventListener('click', handleListClick);
customList.addEventListener('click', handleListClick);

// ── Search ───────────────────────────────────────────────────
searchInput.addEventListener('input', () => {
  searchQuery = searchInput.value.trim();
  renderCategories();
});

// ── Open / close ─────────────────────────────────────────────
document.getElementById('open-add').addEventListener('click', () => { buildEmojiPicker(); openModal('Add Category'); });
document.getElementById('fab-add').addEventListener('click',  () => { buildEmojiPicker(); openModal('Add Category'); });
document.getElementById('close-modal').addEventListener('click', closeModal);
document.getElementById('cancel-modal').addEventListener('click', closeModal);
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

// ── Init ─────────────────────────────────────────────────────
buildEmojiPicker();
fetchCategories();
