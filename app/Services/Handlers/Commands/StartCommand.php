<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;

class StartCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected string $appName;

	public function __construct(TelegramApi $telegramApi)
	{
		$this->telegramApi = $telegramApi;
		$this->appName = config("app.name", "Financial");
	}

	public function getName(): string
	{
		return "start";
	}

	public function getDescription(): string
	{
		return "Memulai bot dan melihat instruksi linking";
	}

	public function handle(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array {
		$service = app(TelegramService::class);
		$user = $service->getUserByChatId($chatId);

		$message = $user
			? $this->getWelcomeMessageForLinkedUser($user)
			: $this->getWelcomeMessageForNewUser();

		$this->telegramApi->sendMessage($chatId, $message, "Markdown", [
			"login_url" => config("telegram.widgets.redirect_url_login"),
		]);

		return ["status" => "start", "user_linked" => (bool) $user];
	}

	/**
	 * Get welcome message for linked user
	 */
	private function getWelcomeMessageForLinkedUser($user): string
	{
		return "👋 Halo {$user->name}!\n" .
			"Akun Anda sudah terhubung.\n\n" .
			"Gunakan /help untuk perintah lainnya.";
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
			"3. Klik pada tombol Telegram untuk menghubungkan\n" .
			"4. Atau klik link: https://vickyserver.my.id/server/settings\n\n" .
			"Gunakan /help untuk perintah lainnya.";
	}
}
