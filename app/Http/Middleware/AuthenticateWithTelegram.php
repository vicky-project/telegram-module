<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Telegram\Models\TelegramUser;

class AuthenticateWithTelegram
{
  public function handle(Request $request, $next) {
    if (Auth::check()) {
      return $next($request);
    }

    $initData = $request->input("initData") ?? $request->query("initData");
    if (!$initData) {
      \Log::error("Data user telegram tidak ditemukan.", $request->all());
      return redirect()->route('telegram.entry')->with('error', 'Data Telegram tidak ditemukan.');
    }

    parse_str($initData, $data);
    $telegramUser = json_decode($data['user'] ?? '{}', true);
    dd($telegramUser);

    // Cari telegram user di database
    $telegramUserModel = TelegramUser::where('telegram_id', $telegramUser['id'])->first();
    if (!$telegramUserModel) {
      \Log::error("Akun telegram belum teehubung.");
      return redirect()->route('telegram.entry')->with('error', 'Akun Telegram belum terhubung. Silakan login melalui web dan hubungkan akun Anda.');
    }

    // Cari social account
    $socialAccount = \Modules\SocialAccount\Models\SocialAccount::where('provider', 'telegram')
    ->where('providerable_id', $telegramUserModel->id)
    ->where('providerable_type', TelegramUser::class)
    ->first();

    if (!$socialAccount) {
      \Log::error("Telegram tidak terhubung dengan social account");
      return redirect()->route('telegram.not-connected');
    }

    Auth::login($socialAccount->user);
    return $next($request);
  }
}