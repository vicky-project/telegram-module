<?php
namespace Modules\Telegram\Services\Middlewares;

use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\LinkService;
use Modules\Telegram\Services\Support\TelegramApi;
use Illuminate\Support\Facades\Log;

class EnsureUserLinkedMiddleware implements TelegramMiddlewareInterface
{
	protected LinkService $linkService;
	protected TelegramApi $telegramApi;

	public function __construct(
		LinkService $linkService,
		TelegramApi $telegramApi
	) {
		$this->linkService = $linkService;
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
		$exemptCommands = ["start", "help", "link"];

		if (in_array($command, $exemptCommands)) {
			return $next($chatId, $command, $argument, $username);
		}

		$user = $this->linkService->getUserByChatId($chatId);

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

		// Kirim user object ke handler berikutnya
		return $next($chatId, $command, $argument, $username, $user);
	}
}
