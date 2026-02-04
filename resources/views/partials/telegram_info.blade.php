@if(Auth::user()->hasTelegram())
<div class="row">
  <div class="col-md-6 mb-3">
    <div class="border border-primary border-opacity-50 rounded-circle">
      <i class="bi bi-check display-1"></i>
    </div>
  </div>
  <div class="col-md-6 mb-3"></div>
</div>
@else
  <script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('telegram.bot.username') }}" data-size="large" data-auth-url="{{ config('telegram.widgets.redirect_url') }}" data-request-access="write"
  @if(config('telegram.widgets.userpic') === false)
  data-userpic="false"
  @endif
  ></script>
@endif