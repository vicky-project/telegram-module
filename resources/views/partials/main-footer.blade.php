@auth()
<img src="{{ auth()->user()->profile?->image()}}" class="img-fluid rounded-circle" style="width: 50px; height: 50px;" alt="{{ $user->name}}">
@endauth

@guest()
@php
$tgService = app(\Modules\Telegram\Services\TelegramService::class);
@endphp

@if($tgService->checkDeviceKnown())
@include('telegram::auth.button')
@else 
<a href="{{ route('login') }}" class="btn btn-block" style="background-color: var(--tg-theme-button-text-color); color: var(--tg-theme-button-color);">Login</a>
@endif
@endguest