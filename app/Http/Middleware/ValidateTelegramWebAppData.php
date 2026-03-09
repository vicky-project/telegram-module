<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ValidateTelegramWebAppData
{
  public function handle(Request $request, Closure $next) {
    \Log::debug("Incoming Request", [
      "request" => $request->all()
    ]);

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
    \Log::debug("Parse data:", ["data" => $data]);
    $hash = $data['hash'] ?? null;
    unset($data['hash']);
    \Log::debug("Found hash:" . $hash);

    if (!$hash) {
      return false;
    }

    ksort($data);
    $checkString = urldecode(http_build_query($data, "", "\n"));
    \Log::debug("Check string: ". $checkString);

    $secretKey = hash_hmac('sha256', 'WebAppData', $token, true);
    \Log::debug("Secret Key: ". $secretKey);
    $calculatedHash = hash_hmac('sha256', $checkString, $secretKey);
    \Log::debug("Calculated Hash: ". $calculatedHash);

    return hash_equals($calculatedHash, $hash);
  }

  private function parseUserData(string $initData): array
  {
    parse_str($initData, $data);
    return json_decode($data['user'] ?? '{}', true);
  }
}