<h1 class="card-title mb-4 pb-2 border-bottom border-success">Telegram</h1>
@if($user->hasTelegram())
<div class="row align-items-center">
  <div class="col-md-6 mb-3 text-center">
    <div class="border border-primary border-opacity-50 rounded-circle text-center">
      <i class="
      @switch(config('telegram.injection.icon-provider', 'fontawesome'))
        @case('fontawesome')
          fas fa-check
          @break
        @case('bootstrap-icon')
          bi bi-check
          @break
      @endswitch
      display-1"></i>
    </div>
    <h2 class="fw-bold">Connected</h2>
  </div>
  <div class="col-md-6 mb-3">
     <p class="mb-0">
        <span class="text-muted">Akun:</span>
        <strong>{{ $user->telegram->username ?? $user->telegram->first_name.' '.$user->telegram->last_name }}</strong>
      </p>
      <small class="text-muted">Chat ID: {{ $user->telegram->telegram_id }}</small>
  </div>
</div>
@else
  <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('telegram.bot.username') }}" data-size="{{ config('telegram.widgets.size') }}" data-auth-url="{{ config('telegram.widgets.redirect_url') }}" data-request-access="write"
  @if(config('telegram.widgets.userpic') === false)
  data-userpic="false"
  @endif
  ></script>
@endif