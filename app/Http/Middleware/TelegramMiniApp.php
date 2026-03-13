<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Telegram\Models\TelegramUser;
use Modules\Telegram\Services\TelegramAuthService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

class TelegramMiniApp {
  public function __construct(
    protected TelegramAuthService $authService,
    protected AuthFactory $auth
  ) {}

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

    $telegramUser = TelegramUser::firstOrCreate(
      ['telegram_id' => $telegramId],
      [
        'first_name' => $telegramUserData['first_name'] ?? '',
        'last_name' => $telegramUserData['last_name'] ?? '',
        'username' => $telegramUserData['username'] ?? '',
        'photo_url' => $telegramUserData['photo_url'] ?? '',
        'data' => $telegramUserData,
      ])->first();

    $socialAccount = $this->getSocialAccount($telegramUser);
    $token = null;
    $user = null;

    if ($socialAccount) {
      $user = $socialAccount->user;
      $token = $request->bearerToken();

      if (!$token && $request->has("token")) {
        $token = $request->get("token");
        $request->headers->set("Authorization", "Bearer ".$token);
      }

      if ($request->bearerToken()) {
        if ($this->auth->guard('sanctum')->check()) {
          $this->auth->shouldUse('sanctum');
        } else {
          $token = $user->createToken('telegram-token', ["*"], now()->plus(weeks: 1))->plainTextToken;
        }
      }

      if ($this->auth->guard('web')->check()) {
        $this->auth->shouldUse('web');
        $this->auth->guard("web")->login($user);
        session()->regenerate();
      }
    }

    $request->merge([
      "telegram_user" => $telegramUser->toArray(),
      "initData" => $initData,
      "token" => $token,
      "user" => $user
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