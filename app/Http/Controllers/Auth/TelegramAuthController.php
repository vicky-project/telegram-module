<?php
namespace Modules\Telegram\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Telegram\Services\TelegramAuthService;

class TelegramAuthController extends Controller
{
  public function authenticate(Request $request, TelegramAuthService $authService) {
    $telegramInitData = session('init_data');
    if (!$telegramInitData) {
      \Log::error("Data telegram tidak ditemukan.");
      return back()->with('error', 'Data telegram tidak ditemukan');
    }

    $user = $authService->authenticate($telegramInitData, config("telegram.bot.token"));

    if (!$user) {
      \Log::error("Akun telegram belum terhubung dengan user.");
      return back()->with('error', 'Akun telegram belum terhubung dengan user manapun. Silakan login melalui web terlebih dahulu.');
    }

    \Log::info("Login as: ", [$user]);
    \Auth::guard("web")->login($user);
    session(["is_telegram_app" => true]);
    return redirect()->route("telegram.dashboard");
  }
}