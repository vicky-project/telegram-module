<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateTelegramWebAppData
{
  public function handle(Request $request, Closure $next) {
    $initData = $request->input('initData') ?? $request->header('X-Telegram-Init-Data') ?? $request->query('initData');

    if (!$initData) {
      abort(403, 'Missing Telegram init data');
    }

    $botToken = config('telegram.bot.token');
    if (!$this->validateInitData($initData, $botToken)) {
      abort(403, 'Invalid Telegram init data');
    }

    // Parse user data and store in session
    $userData = $this->parseUserData($initData);
    session(['telegram_user' => $userData]);

    return $next($request);
  }

  private function validateInitData(string $initData, string $token): bool
  {
    parse_str($initData, $data);
    $hash = $data['hash'] ?? null;
    unset($data['hash']);

    if (!$hash) {
      return false;
    }

    ksort($data);
    $checkString = urldecode(http_build_query($data, "", "\n"));

    $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
    $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);

    return hash_equals($calculatedHash, $hash);
  }

  private function parseUserData(string $initData): array
  {
    parse_str($initData, $data);
    return json_decode($data['user'] ?? '{}', true);
  }
}