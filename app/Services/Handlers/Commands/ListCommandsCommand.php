<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\Handlers\CommandDispatcher;

class ListCommandsCommand implements TelegramCommandInterface
{
	protected CommandDispatcher $dispatcher;

	public function __construct(CommandDispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	public function getCommandName(): string
	{
		return "list";
	}
	public function getDescription(): string
	{
		return "Daftar semua command yang terdaftar (debug)";
	}
	public function requiresLinkedUser(): bool
	{
		return true;
	}
	public function getCategory(): string
	{
		return "debug";
	}

	public function handle(
		int $chatId,
		?string $argument,
		?string $username,
		$user = null
	): array {
		$commands = $this->dispatcher->getAvailableCommands();

		$message = "📋 *Daftar Command Terdaftar:*\n\n";
		$message .= "Total: " . count($commands) . " command(s)\n\n";

		foreach ($commands as $cmd => $handler) {
			$message .= "• /{$cmd}\n";
			$message .= "  └─ " . get_class($this) . "\n";
			$message .=
				"  └─ Requires linked user: " .
				($handler->requiresLinkedUser() ? "Ya" : "Tidak") .
				"\n\n";
		}

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return ["status" => "list_sent", "count" => count($commands)];
	}
}
