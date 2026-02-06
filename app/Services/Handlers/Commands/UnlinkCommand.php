<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\LinkService;
use Modules\Telegram\Services\Support\TelegramApi;

class UnlinkCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected LinkService $linkService;
	protected string $appName;
	protected $user;

	public function __construct(
		TelegramApi $telegramApi,
		LinkService $linkService
	) {
		$this->telegramApi = $telegramApi;
		$this->linkService = $linkService;
		$this->appName = config("app.name", "Financial");
	}

	public function getCommandName(): string
	{
		return "unlink";
	}

	public function getDescription(): string
	{
		return "Memutuskan dari telegram.";
	}

	public function requiresLinkedUser(): bool
	{
		return true; // Tidak perlu user terhubung
	}

	public function setUser()
	{
	}

	public function handle(
		int $chatId,
		?string $argument,
		?string $username,
		$user = null
	): array {
		\Log::debug("Using user: " . $user->name);
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->telegramApi->sendMessage($chatId, "❌ Akun tidak terhubung.");

			return [
				"status" => "unlink_failed",
				"reason" => "not_linked",
			];
		}

		$user->unlinkTelegramAccount();

		$message =
			"✅ *Akun berhasil diputuskan.*\n\n" .
			"Anda bisa menghubungkan kembali melalui web app.\n" .
			"Terima kasih telah menggunakan bot kami! 👋";

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return [
			"status" => "unlink_success",
			"user_id" => $user->id,
		];
	}
}
