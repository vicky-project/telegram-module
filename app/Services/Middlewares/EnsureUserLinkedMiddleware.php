<?php
namespace Modules\Telegram\Services\Middlewares;

use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;
use Illuminate\Support\Facades\Log;

class EnsureUserLinkedMiddleware implements TelegramMiddlewareInterface
{
	protected TelegramService $service;
	protected TelegramApi $telegramApi;

	public function __construct(
		TelegramService $service,
		TelegramApi $telegramApi
	) {
		$this->service = $service;
		$this->telegramApi = $telegramApi;
	}

	public function handle(
		int $chatId,
		string $command,
		?string $argument,
		?string $username,
		callable $next
	): array {
		// Command yang tidak memerlukan user terhubung
		$exceptCommand = config("telegram.commander.no_auth");

		if (in_array($command, $exceptCommand)) {
			return $next($chatId, $command, $argument, $username, null);
		}

		Log::debug("Using chat id: " . $chatId, [
			"chat_id" => $chatId,
			"command" => $command,
		]);

		$user = $this->service->getUserByChatId($chatId);

		if (!$user) {
			Log::warning("User not linked for command", [
				"chat_id" => $chatId,
				"command" => $command,
			]);

			$message =
				"❌ Anda belum terhubung.\n" .
				"Gunakan /link untuk menghubungkan akun terlebih dahulu.\n" .
				"Atau gunakan /start untuk instruksi linking.";

			$this->telegramApi->sendMessage($chatId, $message);

			return [
				"status" => "middleware_blocked",
				"reason" => "not_linked",
				"command" => $command,
			];
		}

		Log::debug("User found: " . $user->name, [
			"chat_id" => $chatId,
			"command" => $command,
			"user" => $user,
		]);

		// Kirim user object ke handler berikutnya
		return $next($chatId, $command, $argument, $username, $user);
	}
}
