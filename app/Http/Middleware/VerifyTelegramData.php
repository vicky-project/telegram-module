<?php

namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Telegram\Services\TelegramAuthService;
use Illuminate\Support\Facades\Auth;

class VerifyTelegramData
{
	protected $telegramAuthService;

	public function __construct(TelegramAuthService $telegramAuthService)
	{
		$this->telegramAuthService = $telegramAuthService;
	}

	public function handle(Request $request, Closure $next)
	{
		if (Auth::check()) {
			return $next($request);
		}

		$authHeader = $request->header("Authorization");
		$initData = null;

		if ($authHeader && str_starts_with($authHeader, "tma ")) {
			$initData = substr($authHeader, 4);
		} elseif ($request->has("initData")) {
			$initData = $request->input("initData");
		}

		if ($initData) {
			$user = $this->telegramAuthService->authenticate(
				$initData,
				config("telegram.bot.token"),
				false, // Jangan buat user baru
			);

			if ($user) {
				Auth::login($user, true);
				return $next($request);
			}

			// Data valid tapi tidak ada relasi → simpan pending dan redirect ke login
			session(["telegram_pending_init" => $initData]);
			return redirect()
				->guest(route("login"))
				->with(
					"info",
					"Silakan login manual untuk menghubungkan akun Telegram Anda.",
				);
		}

		// Tidak ada initData
		if ($request->expectsJson()) {
			return response()->json(["error" => "Unauthenticated"], 401);
		}

		return redirect()->guest(route("login"));
	}
}
