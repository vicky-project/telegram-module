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
		Log::debug("Creating pipeline for handler", [
			"handler" => get_class($handler),
			"middleware_count" => count($this->middlewares),
		]);

		// Handler akhir: panggil handler dengan 5 parameter
		$handlerCallable = function (
			$chatId,
			$command,
			$argument,
			$username,
			$user = null
		) use ($handler) {
			Log::debug("🎯 FINAL HANDLER CALLABLE INVOKED", [
				"chat_id" => $chatId,
				"command" => $command,
				"user_provided" => !is_null($user),
				"user_id" => $user ? $user->id : null,
				"handler_class" => get_class($handler),
			]);

			return $handler->handle($chatId, $argument, $username, $user);
		};

		// Bangun pipeline dari dalam ke luar
		$pipeline = $handlerCallable;

		// Balik urutan middleware (yang pertama didaftar dijalankan pertama)
		foreach (array_reverse($this->middlewares) as $index => $middleware) {
			$currentPipeline = $pipeline;
			$middlewareClass = get_class($middleware);

			$pipeline = function (
				$chatId,
				$command,
				$argument,
				$username,
				$user = null
			) use ($middleware, $currentPipeline, $middlewareClass, $index) {
				Log::debug("🔄 MIDDLEWARE #{$index} INVOKED: {$middlewareClass}", [
					"chat_id" => $chatId,
					"command" => $command,
					"user_incoming" => !is_null($user),
					"user_id" => $user ? $user->id : null,
				]);

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
					) use ($currentPipeline, $middlewareClass) {
						Log::debug("➡️  MIDDLEWARE {$middlewareClass} CALLING NEXT", [
							"user_passed" => !is_null($nextUser),
							"user_id" => $nextUser ? $nextUser->id : null,
							"next_params" => [
								"chatId",
								"command",
								"argument",
								"username",
								"user",
							],
						]);

						// Panggil pipeline berikutnya dengan 5 parameter
						return $currentPipeline(
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

		Log::debug("Pipeline created successfully", [
			"handler" => get_class($handler),
			"middleware_stack" => array_map(
				fn($mw) => get_class($mw),
				$this->middlewares
			),
		]);

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
