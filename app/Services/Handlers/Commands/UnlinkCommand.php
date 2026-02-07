<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;

class UnlinkCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected TelegramService $telegramService;
	protected string $appName;

	public function __construct(
		TelegramApi $telegramApi,
		TelegramService $telegramService
	) {
		$this->telegramApi = $telegramApi;
		$this->telegramService = $telegramService;
		$this->appName = config("app.name", "Financial");
	}

	public function getName(): string
	{
		return "unlink";
	}

	public function getDescription(): string
	{
		return "Memutuskan akun dari telegram.";
	}

	public function handle(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array {
		try {
			$user = $params["user"] ?? null;

			if (!$user) {
				$user = $this->telegramService->getUserByChatId($chatId);
			}

			\Log::debug("Using user: " . $user->name);
			$this->telegramService->unlink($user, $chatId);

			$message =
				"✅ *Akun berhasil diputuskan.* {$this->appName}\n\n" .
				"Anda bisa menghubungkan kembali melalui web app.\n" .
				"Terima kasih telah menggunakan bot kami! 👋";

			$this->telegramApi->sendMessage($chatId, $message, "Markdown");

			return [
				"status" => "unlink_success",
				"user_id" => $user->id,
			];
		} catch (\RuntimeException $e) {
			\Log::error("Failed to disconnecting to telegram.", [
				"chat_id" => $chatId,
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			$message =
				"*Error* {$this->appName}" .
				"Terjadi kesalahan saat memutuskan koneksi ke bot telegram.\nSilakan coba lagi atau hubungi administrator.";

			$this->telegramApi->sendMessage($chatId, $message, "Markdown");

			return [
				"status" => "unlink_failed",
				"chat_id" => $chatId,
				"message" => $e->getMessage(),
			];
		}
	}
}
