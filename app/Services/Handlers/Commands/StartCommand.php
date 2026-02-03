<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\LinkService;
use Modules\Telegram\Services\Support\TelegramApi;

class StartCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected LinkService $linkService;
	protected string $appName;

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
		return "start";
	}

	public function getDescription(): string
	{
		return "Memulai bot dan melihat instruksi linking";
	}

	public function requiresLinkedUser(): bool
	{
		return false; // Tidak perlu user terhubung
	}

	public function handle(
		int $chatId,
		?string $argument,
		?string $username,
		$user = null
	): array {
		// Logika handleStart seperti sebelumnya
		$message = $user
			? $this->getWelcomeMessageForLinkedUser($user)
			: $this->getWelcomeMessageForNewUser();

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return ["status" => "start", "user_linked" => (bool) $user];
	}

	/**
	 * Get welcome message for linked user
	 */
	private function getWelcomeMessageForLinkedUser($user): string
	{
		return "👋 Halo {$user->name}!\n" .
			"Akun Anda sudah terhubung.\n\n" .
			"Gunakan /help untuk informasi lebih lanjut.";
	}

	/**
	 * Get welcome message for new user
	 */
	private function getWelcomeMessageForNewUser(): string
	{
		return "👋 Selamat datang di {$this->appName} Bot!\n\n" .
			"Untuk menghubungkan akun Anda:\n" .
			"1. Login ke aplikasi web\n" .
			"2. Buka Menu Settings → Telegram Integration\n" .
			"3. Generate kode verifikasi\n" .
			"4. Kirim ke bot ini: /link <kode>\n\n" .
			"Gunakan /help untuk informasi lebih lanjut.";
	}
}
