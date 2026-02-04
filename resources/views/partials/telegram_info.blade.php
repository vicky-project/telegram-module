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
  <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('telegram.bot.username') }}" data-size="{{ config('telegram.widgets.size') }}" data-auth-url="{{ config('telegram.widgets.redirect_url') }}" data-request-access="write"
  @if(config('telegram.widgets.userpic') === false)
  data-userpic="false"
  @endif
  ></script>
</div>

<script type="text/javascript">
  function disconnect() {
    alert('Disconnect!');
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
    if(telegram.length == 0) {
      telegramBtnLink.classList.remove('d-none');
      container.classList.remove('d-none');
      container.classList.add('d-none');
    } else {
      telegramBtnLink.classList.remove('d-none');
      telegramBtnLink.classList.add('d-none');
      container.classList.remove('d-none');
      
      username.textContent = `@${telegram.username}`;
      chatId.textContent = `Chat ID: ${telegram.telegram_id}`;
      connectedIcon.className = getIconConnected(true) + ' me-1';
      connectedStatus.textContent = 'Connected';
      switchConnectContainer.innerHtml = `
        <button class="btn btn-sm btn-outline-danger" onclick="disconnect();">Disconnect</button>
      `;
    }
  });
</script>