<script async src="https://telegram.org/js/telegram-widget.js?22" data-telegram-login="{{ config('telegram.bot.username') }}" data-size="{{ config('telegram.bot.size') }}" data-auth-url="{{ route('telegram.redirect-login') }}" data-request-access="write"
@if(config('telegram.widgets.userpic') === false)
data-userpic="false"
@endif
></script>