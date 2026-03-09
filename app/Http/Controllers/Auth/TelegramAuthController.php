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
      return response()->json(['success' => false, 'message' => 'Data telegram tidak ditemukan'], 400);
    }

    $user = $authService->authenticate($telegramInitData, config("telegram.bot.token"));

    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Akun telegram belum terhubung dengan user manapun. Silakan login melalui web terlebih dahulu.'], 400);
    }

    \Auth::guard("web")->login($user);
    session()->regenerate();
    session(["is_telegram_app" => true]);
    return response()->json(["success" => true, "redirect" => route("telegram.dashboard")]);
  }
}