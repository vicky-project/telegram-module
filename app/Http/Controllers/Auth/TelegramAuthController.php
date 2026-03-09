<?php
namespace Modules\Telegram\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Telegram\Services\TelegramAuthService;

class TelegramAuthController extends Controller
{
  public function authenticate(Request $request, TelegramAuthService $authService) {
    $telegramUserData = session('telegram_user');
    if (!$telegramUserData) {
      return response()->json(['success' => false, 'message' => 'Data telegram tidak ditemukan'], 400);
    }

    \Log::debug("Telegram user data:", ["data" => $telegramUserData]);

    $user = $authService->authenticate($telegramUserData, config("telegram.bot.token"));

    if (!$user) {
      return response()->json(['success' => false, 'message' => 'Akun telegram belum terhubung dengan user manapun. Silakan login melalui web terlebih dahulu.'], 400);
    }

    \Auth::login($user);
    session(["is_telegram_app" => true]);
    return response()->json(["success" => true, "user" => $user]);
  }
}