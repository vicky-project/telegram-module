<?php
namespace Modules\Telegram\Services\Handlers;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Support\TelegramApi;
use Illuminate\Support\Facades\Log;

class CommandDispatcher
{
	protected array $handlers = [];
	protected array $middlewares = [];
	protected TelegramApi $telegramApi;

	public function __construct(TelegramApi $telegramApi)
	{
		$this->telegramApi = $telegramApi;
	}

	// Register handler
	public function registerHandler(TelegramCommandInterface $handler): void
	{
		$this->handlers[$handler->getCommandName()] = $handler;
		Log::debug("Registered Telegram handler", [
			"command" => $handler->getCommandName(),
			"requires_linked_user" => $handler->requiresLinkedUser(),
		]);
	}

	// Register middleware
	public function registerMiddleware(
		TelegramMiddlewareInterface $middleware
	): void {
		$this->middlewares[] = $middleware;
		Log::debug("Registered Telegram middleware", [
			"middleware" => get_class($middleware),
		]);
	}

	// Main command handling dengan middleware pipeline
	public function handleCommand(
		int $chatId,
		string $text,
		?string $username
	): array {
		$parts = explode(" ", $text, 2);
		$command = strtolower(trim($parts[0], "/"));
		$argument = $parts[1] ?? null;

		Log::info("Dispatching command", [
			"chat_id" => $chatId,
			"command" => $command,
		]);

		if (!isset($this->handlers[$command])) {
			return $this->handleUnknownCommand($chatId);
		}

		$handler = $this->handlers[$command];

		return $this->execute($handler, $chatId, $command, $argument, $username);
	}

	protected function execute(
		TelegramCommandInterface $handler,
		int $chatId,
		string $command,
		?string $argument,
		?string $username
	): array {
		if (empty($this->middlewares)) {
			return $this->executeHandler($handler, $chatId, $argument, $username);
		}

		// Handler akhir: panggil handler dengan 5 parameter
		$pipeline = function (
			$chatId,
			$command,
			$argument,
			$username,
			$user = null
		) use ($handler) {
			return $this->executeHandler(
				$handler,
				$chatId,
				$argument,
				$username,
				$user
			);
		};

		// Balik urutan middleware (yang pertama didaftar dijalankan pertama)
		foreach (array_reverse($this->middlewares) as $middleware) {
			$pipeline = function (
				$chatId,
				$command,
				$argument,
				$username,
				$user = null
			) use ($middleware, $pipeline) {
				return $middleware->handle(
					$chatId,
					$command,
					$argument,
					$username,
					// Next callback - PENTING: harus menerima 5 parameter!
					function (
						$nextChatId,
						$nextCommand,
						$nextArgument,
						$nextUsername,
						$nextUser = null
					) use ($pipeline) {
						// Panggil pipeline berikutnya dengan 5 parameter
						return $pipeline(
							$nextChatId,
							$nextCommand,
							$nextArgument,
							$nextUsername,
							$nextUser
						);
					}
				);
			};
		}

		return $pipeline($chatId, $command, $argument, $username);
	}

	protected function executeHandler(
		TelegramCommandInterface $handler,
		int $chatId,
		?string $argument,
		?string $username,
		$user = null
	): array {
		try {
			return $handler->handle($chatId, $argument, $username, $user);
		} catch (\Exception $e) {
			return $handler->handle($chatId, $argument, $username);
		}
	}

	private function handleUnknownCommand(int $chatId): array
	{
		$message =
			"❌ Command tidak dikenali.\n" .
			"Gunakan /help untuk melihat daftar command yang tersedia.";
		$this->telegramApi->sendMessage($chatId, $message);

		return ["status" => "unknown_command"];
	}

	// Method untuk mendapatkan daftar command (untuk /help)
	public function getAvailableCommands(): array
	{
		$commands = [];

		foreach ($this->handlers as $command => $handler) {
			$commands[$command] = [
				"description" => $handler->getDescription(),
				"requires_linked" => $handler->requiresLinkedUser(),
			];
		}

		return $commands;
	}
}
