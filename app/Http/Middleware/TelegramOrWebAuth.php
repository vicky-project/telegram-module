<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as AuthMiddleware;
use Modules\Telegram\Http\Middleware\TelegramMiniApp;

class TelegramOrWebAuth
{
  protected $telegramMiddleware;
  protected $authMiddleware;

  public function __construct() {
    // Inisialisasi kedua middleware
    $this->telegramMiddleware = app(TelegramMiniApp::class);
    $this->authMiddleware = app(AuthMiddleware::class);
  }

  /**
  * Handle an incoming request.
  *
  * @param  \Illuminate\Http\Request  $request
  * @param  \Closure  $next
  * @return mixed
  */
  public function handle(Request $request, Closure $next) {
    // Cek apakah ada parameter initData di query string
    $initData = $this->getInitData($request);

    if ($initData) {
      // Jalankan middleware TelegramMiniApp
      \Log::info("Init data exist in request. Using telegram mini app middleware");
      return $this->telegramMiddleware->handle($request, $next);
    }

    // Jika tidak, jalankan middleware auth default
    \log::info("Using middleware auth");
    return $this->authMiddleware->handle($request, $next);
  }

  private function getInitData(Request $request) {
    return $request->input('initData') ?? $request->header('X-Telegram-Init-Data') ?? $request->query('initData');
  }
}