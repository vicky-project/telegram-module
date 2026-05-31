<?php

namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Telegram\Services\TelegramService;

class EnsureTelegramConnected
{
  /**
  * Handle an incoming request.
  */
  public function handle(Request $request, Closure $next) {
    $telegramId = TelegramService::findTelegramId($request->user()?->id);

    if (!$telegramId || $telegramId === null) {
      if ($request->expectsJson()) {
        return response()->json(['success' => false, 'message' => 'Akun Telegram belum terhubung. Silakan hubungkan di menu profile.'], 403);
      }

      return back()->with('error', 'Akun Telegram belum terhubung. Silakan hubungkan di menu profile.');
    }

    $request->merge(['telegram_id' => $telegramId]);

    return $next($request);
  }
}