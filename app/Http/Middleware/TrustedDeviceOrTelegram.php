<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrustedDeviceOrTelegram
{
  public function handle(Request $request, Closure $next) {
    // Pastikan user sudah login (middleware ini dijalankan setelah auth)
    if (!auth()->check()) {
      \Log::warning("Not authenticate");
      return $next($request);
    }

    if (auth()->guard('sanctum')->check()) {
      \Log::info("Check sanctum guard");
      return $next($request);
    }

    // Cek apakah request berasal dari Telegram Mini App
    if ($request->session()->get('is_telegram_app') || $this->getInitData($request)) {
      // Jika dari Telegram, langsung lanjutkan tanpa verifikasi perangkat tepercaya
      \Log::info("Init data exists");
      return $next($request);
    }

    // Jika bukan dari Telegram, jalankan middleware RequireTrustedDevice
    if (class_exists($requireTrustedDevice = \Rappasoft\LaravelAuthenticationLog\Middleware\RequireTrustedDevice::class)) {
      \Log::info("Check trusted device");
      $middleware = new $requireTrustedDevice();
      return $middleware->handle($request, $next);
    }

    return $next($request);
  }

  private function getInitData(Request $request) {
    return $request->input('initData') ?? $request->header('X-Telegram-Init-Data') ?? $request->query('initData');
  }
}