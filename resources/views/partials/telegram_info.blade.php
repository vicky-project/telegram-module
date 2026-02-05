<h1 class="card-title mb-4 pb-2 border-bottom border-success">Telegram</h1>
<div id="telegram-info" class="row align-items-center">
  <div class="col-md-8">
    <div class="d-flex align-items-center mb-2">
      <div class="me-3">
        <span class="badge bg-success status-badge">
          <i class="me-1" id="telegram-connected-icon"></i> <span id="telegram-connected-status"></span>
        </span>
      </div>
      <div>
        <p class="mb-0">
          <span class="text-muted">Akun:</span>
          <strong id="telegram-username"></strong>
        </p>
        <small class="text-muted" id="telegram-chat-id">Chat ID: -</small>
      </div>
    </div>
  </div>
  <div class="col-md-4 text-md-end mt-3 mt-md-0" id="switch-connect-container">
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
    <div class="d-flex align-items-center">
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
        fs-2 text-secondary"></i>
      </div>
      <div class="flex-grow-1 ms-3">
        <h6 class="card-title mb-1">Informasi Bot Telegram</h6>
        <p class="card-text mb-2">
          Nama Bot: <a href="https://t.me/{{ config('telegram.bot.username') }}">
          <strong><span>@</span>{{ config('telegram.bot.username') }}</strong>
          </a>
        </p>
        <small class="text-muted">
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
  
  const toastExists = typeof window.showToast === 'function';
  const disableBtnExists = typeof window.disableButton === 'function';
    const enableBtnExists = typeof window.enableButton === 'function';
  
  async function onTelegramAuth(user){
    const redirectUrl = "{{ config('telegram.widgets.redirect_url_auth') }}";
    
    if(toastExists) {
      showToast('Connecting', 'Process conncting...');
    }
    
    try {
      const response = await fetch("{{ secure_url(config('app.url')) }}" + redirectUrl, {
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
  
  async function disconnect() {
    if (!confirm('Apakah Anda yakin ingin memutuskan koneksi Telegram?')) {
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
    const elems = {
      container: document.getElementById('telegram-info'),
      username: document.getElementById('telegram-username'),
      chatId: document.getElementById('telegram-chat-id'),
      connectedIcon: document.getElementById('telegram-connected-icon'),
      connectedStatus: document.getElementById('telegram-connected-status'),
      telegramBtnLink: document.getElementById('telegram-btn-connect'),
      switchConnectContainer: document.getElementById('switch-connect-container'),
    };
    
    const telegram = @json($telegram);
    if(!telegram.telegram_id) {
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
      elems.switchConnectContainer.innerHTML = `
        <button type="button" id="btn-disconnect" class="btn btn-sm btn-outline-danger" onclick="disconnect();">Disconnect</button>
      `;
    }
  });
</script>