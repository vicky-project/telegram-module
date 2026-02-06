<?php
namespace Modules\Telegram\Services\Middlewares;

use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CallbackThrottleMiddleware implements TelegramMiddlewareInterface
{
	public function handle(array $context, callable $next)
	{
		$userId = $context["user_id"] ?? $context["chat_id"];
		$callbackData = $context["callback_data"] ?? null;

		$cacheKey = "callback_throttle:{$userId}:{$callbackData}";
		$lastCall = Cache::get($cacheKey);

		if ($lastCall && time() - $lastCall < 1) {
			// 1 second throttle
			Log::warning("Callback throttled", [
				"user_id" => $userId,
				"callback_data" => $callbackData,
				"last_call" => $lastCall,
			]);

			return [
				"answer" => "Terlalu banyak permintaan. Tunggu sebentar.",
				"edit_message" => false,
				"block_handler" => true,
			];
		}

		// Record this call
		Cache::put($cacheKey, time(), 5); // Store for 5 seconds

		return $next($context);
	}
}
