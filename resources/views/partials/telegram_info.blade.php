<h5 class="card-title mb-0 fw-bold" style="color: var(--tg-theme-text-color);">
  <i class="bi bi-telegram me-2" style="color: var(--tg-theme-button-color);"></i>
  Telegram
</h5>
<div id="telegram-info" class="row align-items-center">
  <div class="col-md-8">
    <div class="d-flex align-items-center mb-2">
      <div class="me-3">
        <span class="badge status-badge" id="telegram-status-badge" style="background-color: var(--tg-theme-button-color);color: var(--tg-theme-button-text-color);">
          <i class="bi me-1" id="telegram-connected-icon"></i> <span id="telegram-connected-status"></span>
        </span>
      </div>
      <div>
        <p class="mb-0" style="color: var(--tg-theme-text-color);">
          <span style="color: var(--tg-theme-hint-color) !important;">Akun:</span>
          <strong id="telegram-username"></strong>
        </p>
        <small style="color: var(--tg-theme-hint-color) !important;" id="telegram-chat-id">Chat ID: -</small>
      </div>
    </div>
  </div>
  <div class="col-md-4 text-md-end mt-3 mt-md-0" id="btn-disconnect-container">
  </div>
</div>
<div class="d-none" id="telegram-btn-connect">
  <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('telegram.bot.username') }}" data-size="{{ config('telegram.widgets.size') }}" data-onauth="onTelegramAuth(user)" data-request-access="write"
  @if(config('telegram.widgets.userpic') === false)
  data-userpic="false"
  @endif
  ></script>
</div>

<div class="row mt-3">
  <div class="col-md-12">
    <div class="d-flex align-items-start">
      <div class="flex-shrink-0">
        <i class="
        @switch(config('telegram.injection.icon-provider', 'fontawesome'))
          @case('fontawesome')
            fas fa-bot
            @break
          @case('bootstrap-icon')
            bi bi-robot
            @break
        @endswitch
        fs-2" style="color: var(--tg-theme-button-color);"></i>
      </div>
      <div class="flex-grow-1 ms-3">
        <h6 class="fw-semibold mb-1" style="color: var(--tg-theme-text-color);">Informasi Bot Telegram</h6>
        <p class="mb-2" style="color: var(--tg-theme-text-color);">
          Nama Bot: <a href="https://t.me/{{ config('telegram.bot.username') }}" style="color: var(--tg-theme-button-color);text-decoration: none;">
          <strong><span>@</span>{{ config('telegram.bot.username') }}</strong>
          </a>
        <small style="color: var(--tg-theme-hint-color);">
        </p>
          <i class="
          @switch(config('telegram.injection.icon-provider', 'fontawesome'))
            @case('fontawesome')
              fas fa-info-circle
              @break
            @case('bootstrap-icon')
              bi bi-info-circle
            @break
          @endswitch
          me-1"></i>
          Gunakan bot ini untuk menghubungkan akun Telegram Anda dengan sistem
        </small>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  if(typeof window.csrfToken === 'undefined') {
    // CSRF Token for AJAX requests
    window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
  }
  
  let toastExists, disableBtnExists, enableBtnExists;
  
  async function onTelegramAuth(user){
    const redirectUrl = "{{ config('telegram.widgets.redirect_url_auth') }}";
    
    if(toastExists) {
      showToast('Connecting', 'Process conncting...');
    }
    
    try {
      const response = await fetch(redirectUrl + '?' + new URLSearchParams(user), {
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '{{ csrf_token() }}',
          'Accept': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (data.success) {
        if(toastExists) {
        showToast('Berhasil!', data.message, 'success');
        } else {
          alert('Berhasil: '+ data.message);
        }
        // Reload page after 1.5 seconds
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        if(toastExists) {
          showToast('Gagal', data.message, 'error');
        } else {
          alert('Gagal: ' + data.message);
        }
      }
    } catch(error) {
      if(toastExists){
        showToast('Error', error.message || 'Gagal menyambungkan telegram');
      } else {
        alert('Gagal menyambungkan telegram: '+ error.message);
      }
    }
  }
  
  async function disconnect(id) {
    if (!confirm('Apakah Anda yakin ingin memutuskan koneksi Telegram? Your ID: ' + id)) {
      return;
    }
    
    if(toastExists) {
      showToast('Disconnect', 'Proses disconnecting...', 'warning');
    } else {
      alert('Disconnect...');
    }
    
    const btnDisconnect = document.getElementById('btn-disconnect');
    const oldBtnDisconnect = btnDisconnect.innerHTML;
    
    if(btnDisconnect && disableBtnExists) {
      disableButton(btnDisconnect, 'Disconnecting...');
    }
    
    try {
      const response = await fetch('{{ secure_url(config("app.url")) }}/telegram/unlink', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ _token: csrfToken || '{{ csrf_token() }}', telegram_id: id})
      });
      
      const data = await response.json();
      if (data.success) {
        if(toastExists) {
        showToast('Berhasil!', data.message, 'success');
        } else {
          alert('Berhasil: '+ data.message);
        }
        // Reload page after 1.5 seconds
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      } else {
        if(toastExists) {
          showToast('Gagal', data.message, 'error');
        } else {
          alert('Gagal: ' + data.message);
        }
      }
    } catch(error) {
      console.error(error)
      if(toastExists) {
        showToast('Error', error.message || 'Failed to disconnect telegram', 'error')
      } else {
        alert('Error: ' + error.message);
      }
    } finally {
      if(btnDisconnect && enableBtnExists) {
        enableButton(btnDisconnect, oldBtnDisconnect);
      }
    }
  }
  
  function getIconConnected(connect = false) {
    let icon = '';
    @switch(config('telegram.injection.icon-provider', 'fontawesome'))
      @case('fontawesome')
        if(connect) {
          icon = 'fas fa-check';
        } else {
          icon = 'fas fa-times';
        }
        @break
      @case('bootstrap-icon')
        if(connect) {
          icon = 'bi bi-check';
        } else {
          icon = 'bi bi-x';
        }
        @break
    @endswitch
    return icon;
  }
  
  document.addEventListener('DOMContentLoaded', function() {
    toastExists = typeof window.showToast === 'function';
    disableBtnExists = typeof window.disableButton === 'function';
    enableBtnExists = typeof window.enableButton === 'function';
    
    const elems = {
      container: document.getElementById('telegram-info'),
      username: document.getElementById('telegram-username'),
      chatId: document.getElementById('telegram-chat-id'),
      connectedIcon: document.getElementById('telegram-connected-icon'),
      connectedStatus: document.getElementById('telegram-connected-status'),
      telegramBtnLink: document.getElementById('telegram-btn-connect'),
      btnDisconnectContainer: document.getElementById('btn-disconnect-container'),
      statusBadge: document.getElementById('telegram-status-badge'),
    };
    
    const telegram = @json($telegram);
    if(telegram.length === 0 && !telegram.telegram_id) {
      elems.telegramBtnLink.classList.remove('d-none');
      elems.container.classList.remove('d-none');
      elems.container.classList.add('d-none');
    } else {
      elems.telegramBtnLink.classList.remove('d-none');
      elems.telegramBtnLink.classList.add('d-none');
      elems.container.classList.remove('d-none');
      
      elems.username.textContent = `@${telegram.username}`;
      elems.chatId.textContent = `Chat ID: ${telegram.telegram_id}`;
      elems.connectedIcon.className = getIconConnected(true) + ' me-1';
      elems.connectedStatus.textContent = 'Connected';
      
      if(elems.statusBadge){
        elems.statusBadge.style.backgroundColor = '#28a745';
      }
      
      elems.btnDisconnectContainer.innerHTML = `
        <button type="button" id="btn-disconnect" class="btn btn-sm" style="background-color: transparent;color: var(--tg-theme-button-color);border: 1px solid var(--tg-theme-button-color);" onclick="disconnect('${telegram.telegram_id}');">Disconnect</button>
      `;
    }
  });
</script>

<style>
  #telegram-status-badge {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    border-radius: 50px;
  }
</style>