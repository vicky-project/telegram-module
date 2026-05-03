<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=no">
  <title>@yield('title', 'Telegram Mini App')</title>
  <script src="https://telegram.org/js/telegram-web-app.js?61"></script>

  <!-- Bootstrap 5 + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  @include('telegram::partials.miniapp-styles')

  @stack('styles')
</head>
<body>
  <div id="app">
    @yield('content')
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // ======================== GLOBAL TELEGRAM APP FUNCTIONS ========================
    window.TelegramApp = (function() {
    // ----- Token management -----
    let authToken = localStorage.getItem('telegram_token');
    const urlParams = new URLSearchParams(window.location.search);
    const tokenFromUrl = urlParams.get('token');
    if (tokenFromUrl && !authToken) {
    localStorage.setItem('telegram_token', tokenFromUrl);
    authToken = tokenFromUrl;
    }

    function getToken() {
    return authToken || localStorage.getItem('telegram_token');
    }

    // ----- Toast -----
    // Fungsi toast
    function showToast(message, type = 'success') {
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
    toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(toastContainer);

    const toastEl = document.createElement('div');
    toastEl.id = 'liveToast';
    toastEl.className = 'toast';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = `
    <div class="toast-header">
    <strong class="me-auto">Notifikasi</strong>
    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body"></div>
    `;
    toastContainer.appendChild(toastEl);
    }

    const toastEl = document.getElementById('liveToast');
    const toastBody = toastEl.querySelector('.toast-body');
    toastBody.textContent = message;

    toastEl.classList.remove('bg-success', 'bg-danger', 'text-white');
    if (type === 'success') {
    toastEl.classList.add('bg-success', 'text-white');
    } else if (type === 'danger') {
    toastEl.classList.add('bg-danger', 'text-white');
    } else if(type === 'warning') {
    toastEl.classList.add('bg-warning', 'text-white');
    } else {
    toastEl.classList.add('bg-info', 'text-white');
    }

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
    }

    // ----- Fetch with auth -----
    async function fetchWithAuth(url, options = {}) {
    const token = getToken();
    const headers = {
    'Accept': 'application/json',
    ...options.headers
    };
    if (token) headers['Authorization'] = `Bearer ${token}`;
    if (options.body && !headers['Content-Type']) {
    headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, { ...options, headers });

    // Coba parse JSON meskipun response tidak ok
    let data;
    try {
    data = await response.json();
    } catch (e) {
    data = null;
    }

    if (!response.ok) {
    // Lempar error dengan pesan dari server jika ada
    const message = data?.message || data?.error || `HTTP ${response.status}`;
    const error = new Error(message);
    error.status = response.status;
    error.data = data;
    throw error;
    }

    return data;
    }

    function renderPagination(containerId, currentPage, lastPage, onPageChange, scrollToTop = true) {
    const container = document.getElementById(containerId);
    if (!container) return;
    if (lastPage <= 1) {
    container.innerHTML = '';
    return;
    }

    let html = '<div class="pagination-wrapper"><ul class="pagination pagination-sm">';

    // Tombol Previous
    if (currentPage > 1) {
    html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">«</a></li>`;
    } else {
    html += `<li class="page-item disabled"><span class="page-link">«</span></li>`;
    }

    // Halaman pertama
    if (currentPage > 3) {
    html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
    if (currentPage > 4) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    // Halaman di sekitar current
    for (let i = Math.max(1, currentPage - 2); i <= Math.min(lastPage, currentPage + 2); i++) {
    if (i === currentPage) {
    html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
    } else {
    html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
    }
    }

    // Halaman terakhir
    if (currentPage < lastPage - 2) {
    if (currentPage < lastPage - 3) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    html += `<li class="page-item"><a class="page-link" href="#" data-page="${lastPage}">${lastPage}</a></li>`;
    }

    // Tombol Next
    if (currentPage < lastPage) {
    html += `<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">»</a></li>`;
    } else {
    html += `<li class="page-item disabled"><span class="page-link">»</span></li>`;
    }

    html += '</ul></div>';
    container.innerHTML = html;

    // Event listener untuk semua link pagination
    container.querySelectorAll('.page-link[data-page]').forEach(link => {
    link.addEventListener('click', (e) => {
    e.preventDefault();
    const page = parseInt(link.dataset.page);
    if (typeof onPageChange === 'function') {
    onPageChange(page);
    }
    if(scrollToTop) {
    window.scrollTo({ top: 0, behavior: 'smooth'});
    }
    });
    });
    }

    // ----- Copy to clipboard -----
    function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
    TelegramApp.showToast(`Disalin: ${text}`);
    }).catch(() => fallbackCopy(text));
    } else {
    fallbackCopy(text);
    }
    }

    function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    document.body.appendChild(textarea);
    textarea.select();
    try {
    document.execCommand('copy');
    TelegramApp.showToast(`Disalin: ${text}`);
    } catch (err) {
    TelegramApp.showToast('Gagal menyalin', 'danger');
    }
    document.body.removeChild(textarea);
    }

    // ----- Loading overlay (global) -----
    let loadingOverlay = null;
    function showLoading(message = 'Memuat...') {
    let overlay = document.getElementById('global-loading');
    if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'global-loading';
    overlay.style.cssText = 'position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:10000; flex-direction:column;';
    overlay.innerHTML = `
    <div class="spinner-border text-light" role="status"><span class="visually-hidden">Loading...</span></div>
    <div style="color:white; margin-top:10px;">${message}</div>
    `;
    document.body.appendChild(overlay);
    } else {
    overlay.style.display = 'flex';
    const msgDiv = overlay.querySelector('div:last-child');
    if (msgDiv) msgDiv.innerText = message;
    }
    }

    function hideLoading() {
    const overlay = document.getElementById('global-loading');
    if (overlay) overlay.style.display = 'none';
    }

    // ----- Escape HTML -----
    function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
    if (m === '&') return '&amp;';
    if (m === '<') return '&lt;';
    if (m === '>') return '&gt;';
    return m;
    });
    }

    // ----- Apply Telegram theme -----
    const tg = window.Telegram?.WebApp;
    if (tg) {
    tg.ready();
    tg.expand();
    const updateTheme = () => {
    const tp = tg.themeParams;
    if (tp) {
    const root = document.documentElement.style;
    root.setProperty('--tg-theme-bg-color', tp.bg_color || '#ffffff');
    root.setProperty('--tg-theme-text-color', tp.text_color || '#000000');
    root.setProperty('--tg-theme-hint-color', tp.hint_color || '#8e8e93');
    root.setProperty('--tg-theme-link-color', tp.link_color || '#007aff');
    root.setProperty('--tg-theme-button-color', tp.button_color || '#007aff');
    root.setProperty('--tg-theme-button-text-color', tp.button_text_color || '#ffffff');
    root.setProperty('--tg-theme-secondary-bg-color', tp.secondary_bg_color || '#f5f5f5');
    root.setProperty('--tg-theme-section-separator-color', tp.section_separator_color || '#e5e5ea');
    }
    };
    updateTheme();
    tg.onEvent('themeChanged', updateTheme);
    }

    // Public API
    return {
    getToken,
    fetchWithAuth,
    showToast,
    copyToClipboard,
    showLoading,
    hideLoading,
    escapeHtml,
    renderPagination
    };
    })();

    // Alias untuk kemudahan
    const tgApp = window.TelegramApp;
  </script>
  @stack('scripts')
</body>
</html>