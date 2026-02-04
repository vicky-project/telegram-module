<h1 class="card-title mb-4 pb-2 border-bottom border-success">Telegram</h1>
@if($user->hasTelegram())
<div class="row align-items-center">
  <div class="col-md-8">
    <div class="d-flex align-items-center mb-2">
      <div class="me-3">
        <span class="badge bg-success status-badge">
          <i class="
          @switch(config('telegram.injection.icon-provider', 'fontawesome'))
            @case('fontawesome')
              fas fa-check-circle
              @break
            @case('bootstrap-icon')
              bi bi-check-circle
              @break
          @endswitch
          me-1"></i> Terhubung
        </span>
      </div>
      <div>
        <p class="mb-0">
          <span class="text-muted">Akun:</span>
          <strong>{{ $user->telegram->username }}</strong>
        </p>
        <small class="text-muted">Chat ID: {{ $user->telegram->telegram_id }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-4 text-md-end mt-3 mt-md-0">
    <button id="unlinkBtn" class="btn btn-outline-danger btn-sm">
      <i class="bi bi-unlink me-1"></i>Putuskan Koneksi
    </button>
  </div>
</div>
@else
  <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('telegram.bot.username') }}" data-size="{{ config('telegram.widgets.size') }}" data-auth-url="{{ config('telegram.widgets.redirect_url') }}" data-request-access="write"
  @if(config('telegram.widgets.userpic') === false)
  data-userpic="false"
  @endif
  ></script>
@endif