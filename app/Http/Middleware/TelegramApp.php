<?php
namespace Modules\Telegram\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\TelegramService;

class TelegramApp
{
	protected TelegramService $telegramService;

	public function __construct(TelegramService $telegramService)
	{
		$this->telegramService = $telegramService;
	}

	public function handle(Request $request, Closure $next)
	{
		$initData = $request->header("X-Telegram-Init-Data");
		if (!$this->verifyTelegramInitData($initData)) {
			return response()->json(["error" => "Unauthorized"], 401);
		}

		$tgId = $request->header("X-Telegram-User-Id");
		$user = $this->telegramService->getUserByChatId($tgId);

		if (!$user) {
			return response()->json(["error" => "User not found."], 404);
		}

		$request->merge(["user" => $user]);

		return $next($request);
	}

	private function verifyTelegramInitData($initData): bool
	{
		$botToken = env("TELEGRAM_BOT_TOKEN");
		$secretKey = hash_hmac("sha256", $botToken, "WebAppData", true);

		parse_str($initData, $data);
		if (!isset($data["hash"])) {
			return false;
		}
		$hash = $data["hash"];
		unset($data["hash"]);

		ksort($data);
		$dataCheckString = urldecode(http_build_query($data));

		$calculatedHash = hash_hmac("sha256", $dataCheckString, $secretKey);
		return hash_equals($calculatedHash, $hash);
	}
}
