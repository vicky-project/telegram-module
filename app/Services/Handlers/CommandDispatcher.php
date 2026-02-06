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

		// Buat pipeline dengan middleware
		$pipeline = $this->createPipeline($handler);

		return $pipeline($chatId, $command, $argument, $username);
	}

	protected function createPipeline(TelegramCommandInterface $handler): callable
	{
		// Tambahkan handler sebagai akhir pipeline
		$handleCallable = function (
			$chatId,
			$command,
			$argument,
			$username,
			$user = null
		) use ($handler) {
			return $handler->handle($chatId, $argument, $username, $user);
		};

		$pipeline = $handleCallable;

		// Balik urutan middleware agar yang pertama didaftar dijalankan pertama
		foreach (array_reverse($this->middlewares) as $middleware) {
			$pipeline = function ($chatId, $command, $argument, $username) use (
				$middleware,
				$pipeline
			) {
				return $middleware->handle(
					$chatId,
					$command,
					$argument,
					$username,
					function ($chatId, $command, $argument, $username, $user = null) use (
						$pipeline
					) {
						return $pipeline($chatId, $command, $argument, $username, $user);
					}
				);
			};
		}

		return $pipeline;
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
