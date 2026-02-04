<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Bot Telegram | {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --telegram-blue: #0088cc;
            --telegram-blue-light: #e6f3ff;
        }
        
        .telegram-card {
            border-left: 4px solid var(--telegram-blue);
            border-top: 1px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            border-bottom: 1px solid #dee2e6;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
        }
        
        .instruction-box {
            background-color: var(--telegram-blue-light);
            border: 1px solid rgba(0, 136, 204, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            white-space: pre-line;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        
        .settings-card {
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .form-switch .form-check-input {
            width: 3em;
            height: 1.5em;
            cursor: pointer;
        }
        
        .form-switch .form-check-input:checked {
            background-color: var(--telegram-blue);
            border-color: var(--telegram-blue);
        }
        
        .master-toggle {
            background-color: rgba(0, 136, 204, 0.05);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .sub-setting {
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .sub-setting:last-child {
            border-bottom: none;
        }
        
        .btn-telegram {
            background-color: var(--telegram-blue);
            color: white;
        }
        
        .btn-telegram:hover {
            background-color: #0077b3;
            color: white;
        }
        
        .expiry-timer {
            font-size: 0.85rem;
            background-color: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 4px;
            padding: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .toast-success .toast-header {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .toast-error .toast-header {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .toast-info .toast-header {
            background-color: #cff4fc;
            color: #055160;
        }
        
        .spinner-container {
            display: inline-flex;
            align-items: center;
        }
        
        .bot-info-card {
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
        }
    </style>
</head>
<body>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-10 col-xl-8">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h1 class="h3 mb-1">
              <i class="bi bi-robot text-primary me-2"></i>
              Pengaturan Bot Telegram
            </h1>
            <p class="text-muted mb-0">Kelola koneksi dan notifikasi Telegram Anda</p>
          </div>
          <button id="refreshBtn" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>

        <!-- Status Card -->
        <div class="card mb-4 telegram-card">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h5 class="card-title mb-2">
                  <i class="bi bi-link-45deg me-2"></i>Status Koneksi
                </h5>

                @if($user->hasLinkedTelegram())
                  <div class="d-flex align-items-center mb-2">
                    <div class="me-3">
                      <span class="badge bg-success status-badge">
                        <i class="bi bi-check-circle me-1"></i> Terhubung
                      </span>
                    </div>
                    <div>
                      <p class="mb-0">
                        <span class="text-muted">Akun:</span>
                        <strong>{{ $user->telegram->username }}</strong>
                      </p>
                      <small class="text-muted">Chat ID: {{ $user->telegram_chat_id }}</small>
                    </div>
                  </div>
                @else
                  <div class="d-flex align-items-center mb-2">
                    <div class="me-3">
                      <span class="badge bg-danger status-badge">
                        <i class="bi bi-x-circle me-1"></i> Belum Terhubung
                      </span>
                    </div>
                    <div>
                      <p class="mb-0 text-muted">
                        Akun Telegram belum terhubung dengan sistem
                      </p>
                    </div>
                  </div>
                @endif
              </div>
              <div class="col-md-4 text-md-end mt-3 mt-md-0">
                @if($user->hasLinkedTelegram())
                  <button id="unlinkBtn" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-unlink me-1"></i>Putuskan Koneksi
                  </button>
                @else
                  <button id="generateCodeBtn" class="btn btn-telegram btn-sm">
                    <i class="bi bi-key me-1"></i>Generate Kode Linking
                  </button>
                @endif
              </div>
            </div>
          </div>
        </div>

        <!-- Instructions Section (Hidden by default) -->
        <div id="instructionsSection" class="card mb-4 d-none">
          <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
              <i class="bi bi-info-circle me-2"></i>Instruksi Linking
            </h6>
            <button type="button" class="btn-close" id="closeInstructions"></button>
          </div>
          <div class="card-body">
            <div id="instructionsContent" class="instruction-box mb-3"></div>
            <div class="d-flex align-items-center justify-content-between">
              <div class="expiry-timer d-flex align-items-center">
                <i class="bi bi-clock me-2"></i>
                <span>Kode berlaku selama <strong id="timerMinutes">10</strong> menit</span>
              </div>
              <button id="copyInstructionsBtn" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-copy me-1"></i>Salin Instruksi
              </button>
            </div>
          </div>
        </div>

        <!-- Notification Settings -->
        <div class="card mb-4 settings-card">
          <div class="card-header bg-white">
            <h5 class="mb-0">
              <i class="bi bi-bell text-primary me-2"></i>Pengaturan Notifikasi
            </h5>
          </div>
          <div class="card-body">
            <!-- Master Toggle -->
            <div class="master-toggle">
              <div class="form-check form-switch d-flex align-items-center">
                <input class="form-check-input me-3" type="checkbox" id="notificationsToggle" @checked($settings['notifications'] ?? true)>
                <div>
                  <label class="form-check-label fw-bold" for="notificationsToggle">
                   Aktifkan Notifikasi Telegram
                  </label>
                  <p class="text-muted small mb-0 mt-1">
                    Menonaktifkan ini akan mematikan semua notifikasi Telegram
                  </p>
                </div>
              </div>
            </div>

            <!-- Sub-settings -->
            <div id="notificationSettings" class="ps-2">
              <h6 class="mb-3 text-muted">Jenis Notifikasi:</h6>

              <div class="row">
                <div class="col-md-6">
                  <div class="sub-setting">
                    <div class="form-check form-switch">
                      <input class="form-check-input me-2" type="checkbox" id="newTransaction" @checked($settings['new_transaction'] ?? false) @readonly($settings['notifications'] ?? false)>
                      <label class="form-check-label" for="newTransaction">
                        <i class="bi bi-cash-coin me-2"></i>Transaksi Baru
                      </label>
                    </div>
                  </div>

                  <div class="sub-setting">
                    <div class="form-check form-switch">
                      <input class="form-check-input me-2" type="checkbox" id="dailySummary" @checked($settings['daily_summary'] ?? false) @readonly($settings['notifications'] ?? false)>
                      <label class="form-check-label" for="dailySummary">
                        <i class="bi bi-calendar-day me-2"></i>Ringkasan Harian
                      </label>
                    </div>
                  </div>

                  <div class="sub-setting">
                    <div class="form-check form-switch">
                      <input class="form-check-input me-2" type="checkbox" id="weeklySummary" @checked($settings['weekly_summary'] ?? false) @readonly($settings['notifications'] ?? false)>
                      <label class="form-check-label" for="weeklySummary">
                        <i class="bi bi-calendar-week me-2"></i>Ringkasan Mingguan
                      </label>
                    </div>
                  </div>
                </div>

                <div class="col-md-6">
                  <div class="sub-setting">
                    <div class="form-check form-switch">
                      <input class="form-check-input me-2" type="checkbox" id="budgetWarning" @checked($settings['budget_warning'] ?? false) @readonly($settings['notifications'] ?? false)>
                      <label class="form-check-label" for="budgetWarning">
                        <i class="bi bi-exclamation-triangle me-2"></i>Peringatan Budget
                      </label>
                    </div>
                  </div>
                                    
                  <div class="sub-setting">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="budgetExceeded" @checked($settings['budget_exceeded'] ?? false) @readonly($settings['notifications'] ?? false)>
                      <label class="form-check-label" for="budgetExceeded">
                        <i class="bi bi-exclamation-octagon me-2"></i>Budget Terlampaui
                      </label>
                    </div>
                  </div>
                                    
                  <div class="sub-setting">
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="lowBalance" @checked($settings['low_balance'] ?? false) @readonly($settings['notifications'] ?? false)>
                      <label class="form-check-label" for="lowBalance">
                        <i class="bi bi-coin me-2"></i>Saldo Rendah
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Save Button -->
            <div class="text-end mt-4 pt-3 border-top">
              <button id="saveSettingsBtn" class="btn btn-telegram">
                <i class="bi bi-save me-1"></i>Simpan Pengaturan
              </button>
            </div>
          </div>
        </div>

        <!-- Bot Info -->
        <div class="card bot-info-card mb-4">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0">
                <i class="bi bi-robot fs-2 text-secondary"></i>
              </div>
              <div class="flex-grow-1 ms-3">
                <h6 class="card-title mb-1">Informasi Bot Telegram</h6>
                <p class="card-text mb-2">
                  Nama Bot: <strong><span>@</span>{{ $botUsername }}</strong>
                </p>
                <small class="text-muted">
                  <i class="bi bi-info-circle me-1"></i>
                  Gunakan bot ini untuk menghubungkan akun Telegram Anda dengan sistem
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
      <div class="toast-header">
        <i id="toastIcon" class="bi bi-info-circle-fill me-2"></i>
        <strong id="toastTitle" class="me-auto">Notifikasi</strong>
        <small id="toastTime"></small>
        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
      </div>
      <div id="toastMessage" class="toast-body"></div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
  <script>
        // CSRF Token for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        // Toast functionality
        const toastEl = document.getElementById('liveToast');
        const toast = bootstrap.Toast ? new bootstrap.Toast(toastEl) : null;
        
        function showToast(title, message, type = 'info') {
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            const toastIcon = document.getElementById('toastIcon');
            const toastTime = document.getElementById('toastTime');
            
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            
            // Set current time
            const now = new Date();
            toastTime.textContent = now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            // Set icon and color based on type
            let iconClass = 'bi-info-circle-fill';
            let toastClass = 'toast-info';
            
            if (type === 'success') {
                iconClass = 'bi-check-circle-fill';
                toastClass = 'toast-success';
            } else if (type === 'error') {
                iconClass = 'bi-exclamation-circle-fill';
                toastClass = 'toast-error';
            } else if (type === 'warning') {
                iconClass = 'bi-exclamation-triangle-fill';
                toastClass = 'toast-warning';
            }
            
            toastIcon.className = `bi ${iconClass} me-2`;
            
            // Update toast header class
            toastEl.querySelector('.toast-header').className = 'toast-header ' + toastClass;
            
            if (toast) {
                toast.show();
            }
        }
        
        // Utility function to disable buttons with spinner
        function disableButton(button, text) {
            button.disabled = true;
            button.innerHTML = `
                <span class="spinner-container">
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    ${text}
                </span>
            `;
        }
        
        // Utility function to enable buttons
        function enableButton(button, html) {
            button.disabled = false;
            button.innerHTML = html;
        }
        
        // Timer for code expiry
        let timerInterval = null;
        
        function startTimer(minutes = 10) {
            let timeLeft = minutes * 60; // in seconds
            const timerElement = document.getElementById('timerMinutes');
            
            if (timerInterval) {
                clearInterval(timerInterval);
            }
            
            timerInterval = setInterval(() => {
                timeLeft--;
                const minutesLeft = Math.floor(timeLeft / 60);
                const secondsLeft = timeLeft % 60;
                
                if (timerElement) {
                    timerElement.textContent = `${minutesLeft}:${secondsLeft.toString().padStart(2, '0')}`;
                }
                
                if (timeLeft <= 0) {
                    clearInterval(timerInterval);
                    const instructionsSection = document.getElementById('instructionsSection');
                    if (instructionsSection && !instructionsSection.classList.contains('d-none')) {
                        showToast('Kode Expired', 'Kode linking telah kedaluwarsa', 'warning');
                        instructionsSection.classList.add('d-none');
                    }
                }
            }, 1000);
        }
        
        // Event Listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Master toggle for notifications
            const notificationsToggle = document.getElementById('notificationsToggle');
            if (notificationsToggle) {
                notificationsToggle.addEventListener('change', function() {
                    const isEnabled = this.checked;
                    const subSettings = document.querySelectorAll('#notificationSettings input[type="checkbox"]');
                    
                    subSettings.forEach(setting => {
                        setting.disabled = !isEnabled;
                    });
                });
            }
            
            // Generate linking code
            const generateCodeBtn = document.getElementById('generateCodeBtn');
            if (generateCodeBtn) {
                generateCodeBtn.addEventListener('click', async function() {
                    const originalHtml = generateCodeBtn.innerHTML;
                    disableButton(generateCodeBtn, 'Membuat kode...');
                    
                    try {
                        const response = await fetch('{{ secure_url(config("app.url")) }}/telegram/generate-code', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Show instructions
                            const instructionsSection = document.getElementById('instructionsSection');
                            const instructionsContent = document.getElementById('instructionsContent');
                            
                            if (instructionsSection && instructionsContent) {
                                instructionsContent.textContent = data.instructions;
                                instructionsSection.classList.remove('d-none');
                                
                                // Start countdown timer
                                startTimer();
                            }
                            
                            showToast('Berhasil!', 'Kode linking berhasil dibuat', 'success');
                        } else {
                            console.error(data.message, data);
                            showToast('Gagal', data.message || 'Terjadi kesalahan', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Error', 'Terjadi kesalahan jaringan', 'error');
                    } finally {
                        enableButton(generateCodeBtn, originalHtml);
                    }
                });
            }
            
            // Unlink Telegram account
            const unlinkBtn = document.getElementById('unlinkBtn');
            if (unlinkBtn) {
                unlinkBtn.addEventListener('click', async function() {
                    if (!confirm('Apakah Anda yakin ingin memutuskan koneksi Telegram?')) {
                        return;
                    }
                    
                    const originalHtml = unlinkBtn.innerHTML;
                    disableButton(unlinkBtn, 'Memproses...');
                    
                    try {
                        const response = await fetch('{{ secure_url(config("app.url")) }}/telegram/unlink', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast('Berhasil!', data.message, 'success');
                            // Reload page after 1.5 seconds
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showToast('Gagal', data.message, 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Error', 'Terjadi kesalahan jaringan', 'error');
                    } finally {
                        enableButton(unlinkBtn, originalHtml);
                    }
                });
            }
            
            // Save settings
            const saveSettingsBtn = document.getElementById('saveSettingsBtn');
            if (saveSettingsBtn) {
                saveSettingsBtn.addEventListener('click', async function() {
                    const settings = {
                        notifications: document.getElementById('notificationsToggle').checked ? 1 : 0,
                        new_transaction: document.getElementById('newTransaction').checked ? 1 : 0,
                        daily_summary: document.getElementById('dailySummary').checked ? 1 : 0,
                        weekly_summary: document.getElementById('weeklySummary').checked ? 1 : 0,
                        budget_warning: document.getElementById('budgetWarning').checked ? 1 : 0,
                        budget_exceeded: document.getElementById('budgetExceeded').checked ? 1 : 0,
                        low_balance: document.getElementById('lowBalance').checked ? 1 : 0
                    };
                    
                    const originalHtml = saveSettingsBtn.innerHTML;
                    disableButton(saveSettingsBtn, 'Menyimpan...');
                    
                    try {
                        const response = await fetch('{{ secure_url(config("app.url")) }}/telegram/update-settings', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(settings)
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast('Berhasil!', data.message, 'success');
                        } else {
                            console.error(data.message, data);
                            showToast('Gagal', data.message || 'Terjadi kesalahan', 'error');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('Error', 'Terjadi kesalahan jaringan', 'error');
                    } finally {
                        enableButton(saveSettingsBtn, originalHtml);
                    }
                });
            }
            
            // Close instructions
            const closeInstructionsBtn = document.getElementById('closeInstructions');
            if (closeInstructionsBtn) {
                closeInstructionsBtn.addEventListener('click', function() {
                    const instructionsSection = document.getElementById('instructionsSection');
                    if (instructionsSection) {
                        instructionsSection.classList.add('d-none');
                        if (timerInterval) {
                            clearInterval(timerInterval);
                        }
                    }
                });
            }
            
            // Copy instructions to clipboard
            const copyInstructionsBtn = document.getElementById('copyInstructionsBtn');
            if (copyInstructionsBtn) {
                copyInstructionsBtn.addEventListener('click', function() {
                    const instructionsContent = document.getElementById('instructionsContent');
                    if (instructionsContent) {
                        const text = instructionsContent.textContent;
                        navigator.clipboard.writeText(text).then(() => {
                            showToast('Berhasil!', 'Instruksi telah disalin ke clipboard', 'success');
                        }).catch(err => {
                            console.error('Failed to copy: ', err);
                            showToast('Gagal', 'Gagal menyalin ke clipboard', 'error');
                        });
                    }
                });
            }
            
            // Refresh page
            const refreshBtn = document.getElementById('refreshBtn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function() {
                    window.location.reload();
                });
            }
            
            // Auto-refresh if instructions are visible
            const instructionsSection = document.getElementById('instructionsSection');
            if (instructionsSection && !instructionsSection.classList.contains('d-none')) {
                // Start timer if page was refreshed and instructions are still visible
                startTimer();
                
                // Auto-hide after 10.5 minutes (630 seconds) just in case
                setTimeout(() => {
                    if (!instructionsSection.classList.contains('d-none')) {
                        instructionsSection.classList.add('d-none');
                        showToast('Info', 'Kode linking telah kedaluwarsa', 'info');
                    }
                }, 630 * 1000);
            }
        });
    </script>
</body>
</html>