<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Telegram\Models\TelegramUser;

class AuthenticateWithTelegram
{
  public function handle(Request $request, $next) {
    $telegramUser = session('telegram_user');
    if (!$telegramUser) {
      return redirect()->route('telegram.login')->withErrors('Data Telegram tidak ditemukan.');
    }

    // Cari telegram user di database
    $telegramUserModel = TelegramUser::where('telegram_id', $telegramUser['id'])->first();
    if (!$telegramUserModel) {
      return redirect()->route('telegram.login')->withErrors('Akun Telegram belum terhubung. Silakan login melalui web dan hubungkan akun Anda.');
    }

    // Cari social account
    $socialAccount = \Modules\SocialAccount\Models\SocialAccount::where('provider', 'telegram')
    ->where('providerable_id', $telegramUserModel->id)
    ->where('providerable_type', \Modules\Telegram\Entities\TelegramUser::class)
    ->first();

    if (!$socialAccount) {
      return redirect()->route('telegram.login')->withErrors('Akun Telegram belum terhubung dengan user manapun.');
    }

    Auth::login($socialAccount->user);
    return $next($request);
  }
}