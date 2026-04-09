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
  <div id="toastMessage" class="toast-message"></div>

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
    let toastTimeout;
    function showToast(message, duration = 2500) {
    const toastEl = document.getElementById('toastMessage');
    if (!toastEl) return;
    if (toastTimeout) clearTimeout(toastTimeout);
    toastEl.textContent = message;
    toastEl.classList.add('show');
    toastTimeout = setTimeout(() => toastEl.classList.remove('show'), duration);
    }

    // ----- Fetch with auth -----
    async function fetchWithAuth(url, options = {}) {
    const token = getToken();
    const headers = {
    'Accept': 'application/json',
    ...options.headers
    };
    if (token) {
    headers['Authorization'] = `Bearer ${token}`;
    }
    const response = await fetch(url, { ...options, headers });
    if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
    }
    return response.json();
    }

    // ----- Copy to clipboard -----
    function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(() => {
    showToast(`Disalin: ${text}`);
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
    showToast(`Disalin: ${text}`);
    } catch (err) {
    showToast('Gagal menyalin');
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
    escapeHtml
    };
    })();

    // Alias untuk kemudahan
    const tgApp = window.TelegramApp;
  </script>
  @stack('scripts')
</body>
</html>