const popupRootId = 'birr-wise-popup-root';
const toastRootId = 'birr-wise-toast-root';
const confirmRootId = 'birr-wise-confirm-root';

function createPopupContainer() {
  let container = document.getElementById(popupRootId);
  if (!container) {
    container = document.createElement('div');
    container.id = popupRootId;
    container.style.position = 'fixed';
    container.style.top = '80px';
    container.style.right = '16px';
    container.style.display = 'flex';
    container.style.flexDirection = 'column';
    container.style.gap = '12px';
    container.style.zIndex = '1000';
    container.style.pointerEvents = 'none';
    document.body.appendChild(container);
  }
  return container;
}

function createToastContainer() {
  let container = document.getElementById(toastRootId);
  if (!container) {
    container = document.createElement('div');
    container.id = toastRootId;
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  return container;
}

function createConfirmRoot() {
  let root = document.getElementById(confirmRootId);
  if (root) {
    root.remove();
  }

  root = document.createElement('div');
  root.id = confirmRootId;
  document.body.appendChild(root);
  return root;
}

function dismissPopup(popup) {
  if (!popup || !popup.parentNode) {
    return;
  }

  popup.classList.remove('show');
  const timer = parseInt(popup.dataset.dismissTimer, 10);
  if (!isNaN(timer)) {
    clearTimeout(timer);
  }

  setTimeout(() => {
    if (popup.parentNode) {
      popup.parentNode.removeChild(popup);
    }
  }, 300);
}

function showPopup(popupData) {
  const container = createPopupContainer();
  const existing = container.querySelector('.popup');
  if (existing) {
    existing.remove();
  }

  const {
    expense_label = '',
    remaining_today = 0,
    remaining_month = 0,
    budget_alerts = [],
    overall_status = 'on_track'
  } = popupData || {};

  const statusClass = overall_status === 'critical' ? 'danger' : overall_status === 'warning' ? 'warning' : 'success';
  const paceMessage = {
    on_track: '🟢 You\'re on track this month!',
    warning: '🟡 Spending a bit fast — slow down',
    critical: '🔴 Budget critical — careful spending!'
  }[overall_status] || '🟢 You\'re on track this month!';

  const budgetAlertsHtml = budget_alerts.length
    ? budget_alerts.map(alert => `
        <div class="popup-alert-item">
          <span>${alert.category}</span>
          <span class="popup-badge ${alert.level}">${alert.percent_used}% used</span>
        </div>
      `).join('')
    : '';

  const popup = document.createElement('div');
  popup.className = `popup ${statusClass}`;
  popup.setAttribute('aria-live', 'polite');
  popup.innerHTML = `
    <div class="popup-header">
      <div class="popup-title">✅ Expense Saved</div>
      <button type="button" class="popup-close" aria-label="Close">&times;</button>
    </div>
    <div class="popup-expense-label">${expense_label}</div>
    <div class="popup-divider"></div>
    <div class="popup-row${remaining_today < 0 ? ' over-limit' : ''}">
      <span>📅</span>
      <span>${remaining_today < 0 ? formatETB(Math.abs(remaining_today)) + ' over today\'s limit' : `Today: ${formatETB(remaining_today)} remaining`}</span>
    </div>
    <div class="popup-row${remaining_month < 0 ? ' over-limit' : ''}">
      <span>📊</span>
      <span>${remaining_month < 0 ? formatETB(Math.abs(remaining_month)) + ' over monthly allowance' : `Month: ${formatETB(remaining_month)} remaining`}</span>
    </div>
    ${budgetAlertsHtml ? `<div class="popup-alerts">${budgetAlertsHtml}</div>` : ''}
    <div class="popup-footer">${paceMessage}</div>
  `;

  const closeButton = popup.querySelector('.popup-close');
  closeButton.addEventListener('click', () => dismissPopup(popup));

  container.appendChild(popup);

  requestAnimationFrame(() => {
    popup.classList.add('show');
  });

  const timer = setTimeout(() => dismissPopup(popup), 5000);
  popup.dataset.dismissTimer = timer;
}

function showToast(message, type = 'success', duration = 3000) {
  const container = createToastContainer();
  const iconMap = {
    success: '✅',
    error: '❌',
    warning: '⚠️'
  };
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span class="toast-icon">${iconMap[type] || 'ℹ️'}</span><span>${message}</span>`;

  container.appendChild(toast);
  requestAnimationFrame(() => toast.classList.add('show'));

  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      if (toast.parentNode) {
        toast.parentNode.removeChild(toast);
      }
    }, 250);
  }, duration);
}

function showConfirm(options) {
  const root = createConfirmRoot();
  const {
    title = 'Confirm',
    message = '',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
    type = 'default',
    onConfirm,
    onCancel
  } = options || {};

  const overlay = document.createElement('div');
  overlay.className = 'confirm-overlay';
  overlay.innerHTML = `
    <div class="confirm-box">
      <div class="confirm-title">${title}</div>
      <div class="confirm-message">${message}</div>
      <div class="confirm-actions">
        <button type="button" class="btn btn-outline confirm-cancel">${cancelText}</button>
        <button type="button" class="btn confirm-confirm ${type === 'danger' ? 'btn-danger' : type === 'warning' ? 'btn-warning' : ''}">${confirmText}</button>
      </div>
    </div>
  `;

  function closeDialog() {
    if (root.parentNode) {
      root.parentNode.removeChild(root);
    }
  }

  overlay.addEventListener('click', event => {
    if (event.target === overlay) {
      onCancel?.();
      closeDialog();
    }
  });

  root.appendChild(overlay);

  const cancelButton = overlay.querySelector('.confirm-cancel');
  const confirmButton = overlay.querySelector('.confirm-confirm');

  cancelButton.addEventListener('click', () => {
    onCancel?.();
    closeDialog();
  });

  confirmButton.addEventListener('click', () => {
    onConfirm?.();
    closeDialog();
  });
}

function getCsrfToken() {
  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  return tokenMeta ? tokenMeta.content : '';
}

function appendCsrfToken(formData) {
  const token = getCsrfToken();
  if (token) {
    formData.append('csrf_token', token);
  }
  return formData;
}

function formatETB(amount) {
  const value = Number(amount) || 0;
  const negative = value < 0;
  const absolute = Math.abs(value).toFixed(2);
  const [integerPart, decimalPart] = absolute.split('.');
  const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return `${negative ? '-' : ''}${formattedInteger}.${decimalPart} ETB`;
}

function formatDate(dateStr) {
  if (!dateStr) {
    return '';
  }

  const date = new Date(dateStr + 'T00:00:00');
  if (Number.isNaN(date.getTime())) {
    return dateStr;
  }

  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);

  const target = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  const todayKey = `${today.getFullYear()}-${today.getMonth()}-${today.getDate()}`;
  const yesterdayKey = `${yesterday.getFullYear()}-${yesterday.getMonth()}-${yesterday.getDate()}`;
  const targetKey = `${target.getFullYear()}-${target.getMonth()}-${target.getDate()}`;

  if (targetKey === todayKey) {
    return 'Today';
  }
  if (targetKey === yesterdayKey) {
    return 'Yesterday';
  }

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

