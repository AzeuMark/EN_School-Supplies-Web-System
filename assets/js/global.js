/**
 * E&N School Supplies — Global Utilities
 *
 * Toast notifications, PRG guard, CSRF injection, AJAX fetch wrapper.
 */

/* ── Toast Notifications ── */
const Toast = (() => {
  let container;

  function ensureContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  function show(message, type = 'info', duration = 4000) {
    const c = ensureContainer();
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    const icons = { success: '&#10004;', error: '&#10006;', warning: '&#9888;', info: '&#8505;' };

    toast.innerHTML = `
      <span class="toast-icon">${icons[type] || icons.info}</span>
      <span class="toast-message">${escapeHtml(message)}</span>
      <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    c.appendChild(toast);

    if (duration > 0) {
      setTimeout(() => {
        if (toast.parentElement) {
          toast.style.opacity = '0';
          toast.style.transform = 'translateX(100%)';
          toast.style.transition = '0.3s ease';
          setTimeout(() => toast.remove(), 300);
        }
      }, duration);
    }

    return toast;
  }

  function success(msg, dur) { return show(msg, 'success', dur); }
  function error(msg, dur)   { return show(msg, 'error', dur); }
  function warning(msg, dur) { return show(msg, 'warning', dur); }
  function info(msg, dur)    { return show(msg, 'info', dur); }

  return { show, success, error, warning, info };
})();


/* ── CSRF Token ── */
function getCSRFToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.content : '';
}


/* ── Base Path ── */
function getBasePath() {
  return document.body.dataset.basePath || '';
}


/* ── AJAX Fetch Wrapper ── */
async function apiFetch(url, options = {}) {
  const defaults = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCSRFToken()
    }
  };

  const mergedHeaders = { ...defaults.headers, ...(options.headers || {}) };

  // If body is FormData, remove Content-Type so browser sets boundary
  if (options.body instanceof FormData) {
    delete mergedHeaders['Content-Type'];
  }

  const config = {
    ...defaults,
    ...options,
    headers: mergedHeaders
  };

  // Prepend base path if URL is relative
  const fullUrl = url.startsWith('http') ? url : getBasePath() + url;

  try {
    const response = await fetch(fullUrl, config);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `Request failed (${response.status})`);
    }

    return data;
  } catch (err) {
    Toast.error(err.message || 'An unexpected error occurred.');
    throw err;
  }
}


/* ── PRG (Post/Redirect/Get) Guard ── */
function enablePRGGuard() {
  document.querySelectorAll('form[data-prg]').forEach(form => {
    form.addEventListener('submit', function () {
      const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
      submitBtns.forEach(btn => {
        btn.disabled = true;
        btn.dataset.originalText = btn.textContent;
        btn.textContent = 'Processing...';
      });
    });
  });
}


/* ── Double-click Prevention ── */
function preventDoubleClick(button, duration = 2000) {
  if (button.dataset.processing === 'true') return false;
  button.dataset.processing = 'true';
  button.disabled = true;
  const originalText = button.textContent;
  button.textContent = 'Processing...';

  setTimeout(() => {
    button.disabled = false;
    button.textContent = originalText;
    button.dataset.processing = 'false';
  }, duration);

  return true;
}


/* ── HTML Escaping ── */
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}


/* ── Modal Helpers ── */
function openModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) {
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const overlay = document.getElementById(id);
  if (overlay) {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }
}

// Close modal when clicking overlay background
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay') && e.target.classList.contains('active')) {
    e.target.classList.remove('active');
    document.body.style.overflow = '';
  }
});

// Close modal with Escape key
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const activeModal = document.querySelector('.modal-overlay.active');
    if (activeModal) {
      activeModal.classList.remove('active');
      document.body.style.overflow = '';
    }
  }
});


/* ── Flash Message Display ── */
function showFlashMessage() {
  const flash = document.getElementById('flash-data');
  if (flash) {
    const type = flash.dataset.type;
    const message = flash.dataset.message;
    if (type && message) {
      Toast.show(message, type);
    }
  }
}


/* ── Page Error Display ── */
function showPageError() {
  const el = document.getElementById('page-error');
  if (el && el.dataset.message) {
    el.textContent = el.dataset.message;
    el.classList.add('show');
  }
}


/* ── Format Price ── */
function formatPrice(amount) {
  return '₱' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}


/* ── Date/Time Updater (Navbar) ── */
function startDateTimeUpdater() {
  const el = document.getElementById('navbar-datetime');
  if (!el) return;

  function update() {
    const now = new Date();
    const options = {
      year: 'numeric', month: 'short', day: 'numeric',
      hour: '2-digit', minute: '2-digit', second: '2-digit',
      hour12: true
    };
    el.textContent = now.toLocaleString('en-PH', options);
  }

  update();
  setInterval(update, 1000);
}


/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
  enablePRGGuard();
  showFlashMessage();
  showPageError();
  startDateTimeUpdater();
});
