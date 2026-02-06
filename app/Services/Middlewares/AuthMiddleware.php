<?php
namespace Modules\Telegram\Services\Middlewares;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;

class AuthMiddleware implements MiddlewareInterface
{
	protected TelegramService $service;
	protected TelegramApi $telegram;

	public function __construct(TelegramService $service, TelegramApi $telegam)
	{
		$this->service = $service;
		$this->telegram = $telegam;
	}

	public function handle(array $context, callable $next)
	{
		$chatId = $context["chat_id"];
		$username = $context["username"];

		Log::debug("AuthMiddleware checking", [
			"chat_id" => $chatId,
			"username" => $username,
		]);

		// Check if user exists
		$user = $this->service->getUserByChatId($chatId);

		if (!$user) {
			Log::warning("User not authenticated", [
				"chat_id" => $chatId,
				"username" => $username,
			]);

			$message =
				"❌ Anda belum terhubung.\n" .
				"Gunakan /start untuk instruksi linking.";

			$this->telegram->sendMessage($chatId, $message);

			return [
				"status" => "unauthorized",
				"message" =>
					"Anda perlu mendaftar terlebih dahulu. Gunakan /register untuk mendaftar.",
				"chat_id" => $chatId,
			];
		}

		// Add user to context for next middleware/command
		$context["user"] = $user;

		Log::debug("User authenticated", [
			"user_id" => $user->id,
			"username" => $username,
		]);

		return $next($context);
	}
}
