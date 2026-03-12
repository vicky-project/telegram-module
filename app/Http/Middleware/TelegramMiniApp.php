<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Telegram\Models\TelegramUser;
use Modules\Telegram\Services\TelegramAuthService;

class TelegramMiniApp {
  public function __construct(protected TelegramAuthService $authService) {}

  public function handle(Request $request, Closure $next) {
    // Ambil init data dari request
    $initData = $this->getInitData($request);

    if (!$initData) {
      // Init data tidak ditemukan di request
      \Log::error("Missing Telegram init data.", ["request" => $request->all(), "user" => Auth::user()]);
      abort(403, 'Missing Telegram init data');
    }

    $botToken = config('telegram.bot.token');

    // Verifikasi init data berasal dari telegram valid
    if (!$this->authService->verifyTelegramData($initData, $botToken)) {
      // Init data tidak valid dari telegram
      \Log::error("Invalid Telegram init data.", ['initData' => $initData]);
      abort(403, 'Invalid Telegram init data');
    }

    // Parsing init data lalu ambil telegram id
    $telegramUserData = $this->authService->parseUserData($initData);
    $telegramId = $telegramUserData["id"]??null;

    if (!$telegramId) {
      // Telegram id tidak ditemukan
      \Log::error("No telegram user id in init data", $data);
      abort(403, "Invalid user data");
    }

    $telegramUser = TelegramUser::where('telegram_id', $telegramId)->first();
    if (!$telegramUser) {
      // Akun telegram belum ada.
      return $this->buildNotConnectResponse($request);
    }

    // Ambil data social account dari telegram
    $socialAccount = $this->getSocialAccount($telegramUser);

    if (!$socialAccount) {
      // Telegram belum terhubung dengan social account
      \Log::error("Telegram tidak terhubung dengan social account.");
      return $this->buildNotConnectResponse($request);
    }

    // Jika belum login
    if (!Auth::check()) {
      // Telegram sudah terhubung dengan social account
      Auth::login($socialAccount->user);
      $request->session()->regenerate();
    }

    $request->merge([
      "telegram_user" => $telegramUser->toArray(),
      "initData" => $initData
    ]);
    session(["is_telegram_app" => true]);

    return $next($request);
  }

  private function getInitData(Request $request) {
    return $request->input('initData') ?? $request->header('X-Telegram-Init-Data') ?? $request->query('initData');
  }

  private function getSocialAccount(TelegramUser $telegramUser) {
    // Cari social account
    return \Modules\SocialAccount\Models\SocialAccount::where('provider', 'telegram')
    ->where('providerable_id', $telegramUser->id)
    ->where('providerable_type', get_class($telegramUser))
    ->first();
  }

  private function buildNotConnectResponse(Request $request) {
    $message = "Akun telegram belum terhubung. Silakan login melalui web dan hubungkan Akun telegram anda";

    return $request->expectsJson() ? response()->json(["success" => false, "message" => $message], 403) : redirect()->route('telegram.entry')->with('error', $message);
  }
}