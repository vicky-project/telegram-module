<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Telegram\Services\TelegramAuthService;

class ValidateTelegramWebAppData
{
  public function __construct(protected TelegramAuthService $service) {}

  public function handle(Request $request, Closure $next) {
    $initData = $request->input('initData') ?? $request->header('X-Telegram-Init-Data') ?? $request->query('initData');

    if (!$initData) {
      abort(403, 'Missing Telegram init data');
    }

    $botToken = config('telegram.bot.token');
    if (!$this->service->verifyTelegramData(is_array($initData) ? http_build_query($initData) : $initData, $botToken)) {
      abort(403, 'Invalid Telegram init data');
    }

    // Parse user data and store in session
    $userData = $this->parseUserData($initData);
    session(['telegram_user' => $userData, "init_data" => $initData]);

    return $next($request);
  }

  private function parseUserData(string $initData): array
  {
    parse_str($initData, $data);
    return json_decode($data['user'] ?? '{}', true);
  }
}