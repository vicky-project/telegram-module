<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Handlers\CommandDispatcher;

class HelpCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected CommandDispatcher $dispatcher;
	protected string $appName;

	public function __construct(
		TelegramApi $telegramApi,
		CommandDispatcher $dispatcher
	) {
		$this->telegramApi = $telegramApi;
		$this->dispatcher = $dispatcher;
		$this->appName = config("app.name", "Financial");
	}

	public function getName(): string
	{
		return "help";
	}

	public function getDescription(): string
	{
		return "Menampilkan bantuan dan daftar command";
	}

	public function handle(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array {
		$allCommands = $this->dispatcher->getCommands();

		$message = "📚 *Bantuan {$this->appName} Bot*\n\n";
		$message .= "*Command yang tersedia:*\n\n";

		foreach ($allCommands as $name => $command) {
			$message .= "• /{$name} - {$command->getDescription()}\n";
		}

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return ["status" => "help_sent", "command_count" => count($allCommands)];
	}
}
