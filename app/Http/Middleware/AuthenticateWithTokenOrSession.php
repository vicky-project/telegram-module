<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Modules\Telegram\Services\TelegramAuthService;

class AuthenticateWithTokenOrSession
{
  public function __construct(
    protected AuthFactory $auth,
    protected TelegramAuthService $authService
  ) {}

  public function handle($request, Closure $next) {
    // Coba autentikasi via token (Sanctum)
    $token = $request->bearerToken();
    if (!$token && $request->has("token")) {
      $token = $request->get("token");
      $request->headers->set("Authorization", "Bearer ".$token);
    }
    if ($request->bearerToken()) {
      if ($this->auth->guard('sanctum')->check()) {
        $this->auth->shouldUse('sanctum');
        return $next($request);
      }
    }

    // Jika tidak ada token atau token invalid, coba session (web)
    if ($this->auth->guard('web')->check()) {
      $this->auth->shouldUse('web');
      return $next($request);
    }

    $initData = $this->getInitData($request);
    if ($initData) {
      return $this->usingTelegram($request, $next);
    }

    throw new AuthenticationException('Unauthenticated.');
  }

  private function getInitData(Request $request) {
    return $request->input('initData') ?? $request->header('X-Telegram-Init-Data') ?? $request->query('initData');
  }

  private function usingTelegram(Request $request, Closure $next) {
    $initData = $this->getInitData($request);
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
    if (!$socialAccount) {
      return redirect()->route("telegram.not-connected");
    }

    $user = $socialAccount->user;

    $token = $user->createToken('telegram-token', ["*"], now()->plus(weeks: 1))->plainTextToken;
    $this->auth->shouldUse('sanctum');

    return $next($request);
  }
}