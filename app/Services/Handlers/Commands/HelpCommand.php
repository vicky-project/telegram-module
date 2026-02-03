<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\LinkService;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Handlers\CommandDispatcher;

class HelpCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected LinkService $linkService;
	protected CommandDispatcher $dispatcher;
	protected string $appName;

	public function __construct(
		TelegramApi $telegramApi,
		LinkService $linkService,
		CommandDispatcher $dispatcher
	) {
		$this->telegramApi = $telegramApi;
		$this->linkService = $linkService;
		$this->dispatcher = $dispatcher;
		$this->appName = config("app.name", "Financial");
	}

	public function getCommandName(): string
	{
		return "help";
	}

	public function getDescription(): string
	{
		return "Menampilkan bantuan dan daftar command";
	}

	public function requiresLinkedUser(): bool
	{
		return false;
	}

	public function handle(
		int $chatId,
		?string $argument,
		?string $username,
		$user = null
	): array {
		$allCommands = $this->dispatcher->getAvailableCommands();

		$message = "📚 *Bantuan {$this->appName} Bot*\n\n";

		if ($user) {
			$message .= "👋 Halo {$user->name}!\n\n";
			$message .= "*Command yang tersedia:*\n\n";

			// Tampilkan semua command untuk user yang sudah terhubung
			foreach ($allCommands as $cmd => $info) {
				$message .= "• /{$cmd} - {$info["description"]}\n";
			}
		} else {
			$message .= "*Untuk pengguna baru:*\n\n";

			// Hanya tampilkan command yang tidak memerlukan linking
			foreach ($allCommands as $cmd => $info) {
				if (!$info["requires_linked"]) {
					$message .= "• /{$cmd} - {$info["description"]}\n";
				}
			}

			$message .= "\n🔗 *Linking Account:*\n";
			$message .= "1. Login ke aplikasi web\n";
			$message .= "2. Buka Settings → Telegram Integration\n";
			$message .= "3. Generate kode verifikasi\n";
			$message .= "4. Kirim: /link <kode>\n\n";
			$message .= "Setelah terhubung, lebih banyak command akan tersedia.";
		}

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return ["status" => "help_sent", "user_linked" => (bool) $user];
	}
}
