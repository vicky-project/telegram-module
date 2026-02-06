<?php
namespace Modules\Telegram\Services\Handlers;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Support\TelegramApi;

class CommandDispatcher
{
	/**
	 * Registered commands
	 *
	 * @var array<string, CommandInterface>
	 */
	private array $commands = [];

	/**
	 * Registered middleware
	 *
	 * @var array<string, MiddlewareInterface>
	 */
	private array $middleware = [];

	// Global middleware
	private array $globalMiddlewares = ["ids"];

	/**
	 * Middleware stack for each command
	 *
	 * @var array<string, array>
	 */
	private array $commandMiddleware = [];

	/**
	 * Register a command
	 */
	public function registerCommand(
		TelegramCommandInterface $command,
		array $middleware = []
	): void {
		$commandName = $command->getName();
		$this->commands[$commandName] = $command;

		if (!empty($middleware)) {
			$this->commandMiddleware[$commandName] = $middleware;
		}

		Log::debug("Command registered", [
			"command" => $commandName,
			"middleware_count" => count($middleware),
		]);
	}

	/**
	 * Register middleware
	 */
	public function registerMiddleware(
		string $name,
		TelegramMiddlewareInterface $middleware
	): void {
		$this->middleware[$name] = $middleware;
		Log::debug("Middleware registered", ["name" => $name]);
	}

	/**
	 * Handle incoming command
	 */
	public function handleCommand(
		int $chatId,
		string $text,
		?string $username = null
	): array {
		try {
			$parsed = $this->parseCommand($text);
			$commandName = $parsed["command"];
			$params = $parsed["params"];

			Log::info("Processing command", [
				"command" => $commandName,
				"chat_id" => $chatId,
				"username" => $username,
			]);

			// Check if command exists
			if (!isset($this->commands[$commandName])) {
				return $this->handleUnknownCommand($chatId, $commandName);
			}

			$command = $this->commands[$commandName];

			// Prepare context
			$context = [
				"chat_id" => $chatId,
				"text" => $text,
				"username" => $username,
				"params" => $params,
				"command" => $commandName,
				"timestamp" => now(),
			];

			// Get middleware for this command
			$middlewareStack = $this->getMiddlewareStack($commandName);

			// Create middleware pipeline
			$pipeline = $this->createPipeline($middlewareStack, function (
				$context
			) use ($command) {
				return $command->handle(
					$context["chat_id"],
					$context["text"],
					$context["username"],
					$context["params"]
				);
			});

			// Execute pipeline
			$result = $pipeline($context);

			Log::info("Command executed successfully", [
				"command" => $commandName,
				"chat_id" => $chatId,
				"result" => $result,
			]);

			return $result;
		} catch (\Exception $e) {
			Log::error("Failed to handle command", [
				"chat_id" => $chatId,
				"command" => $text,
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return [
				"status" => "error",
				"message" => "Terjadi kesalahan saat memproses perintah.",
				"chat_id" => $chatId,
			];
		}
	}

	/**
	 * Create middleware pipeline
	 */
	private function createPipeline(
		array $middleware,
		callable $handler
	): callable {
		$pipeline = array_reduce(
			array_reverse($middleware),
			function ($next, $middlewareName) {
				return function ($context) use ($middlewareName, $next) {
					if (!isset($this->middleware[$middlewareName])) {
						Log::warning("Middleware not found", ["name" => $middlewareName]);
						return $next($context);
					}

					$middleware = $this->middleware[$middlewareName];
					return $middleware->handle($context, $next);
				};
			},
			$handler
		);

		return $pipeline;
	}

	/**
	 * Get middleware stack for command
	 */
	private function getMiddlewareStack(string $commandName): array
	{
		$stack = [];

		// Add global middleware (optional, if you want to add later)
		$stack = array_merge($stack, $this->globalMiddlewares);

		// Add command-specific middleware
		if (isset($this->commandMiddleware[$commandName])) {
			$stack = array_merge($stack, $this->commandMiddleware[$commandName]);
		}

		return $stack;
	}

	/**
	 * Parse command and parameters
	 */
	private function parseCommand(string $text): array
	{
		$parts = explode(" ", $text, 2);
		$command = strtolower(trim($parts[0], "/"));
		$params = [];

		if (isset($parts[1])) {
			// Parse parameters
			$paramString = trim($parts[1]);
			if (!empty($paramString)) {
				// Simple parsing - can be extended as needed
				$params = explode(" ", $paramString);
			}
		}

		return [
			"command" => $command,
			"params" => $params,
			"raw" => $text,
		];
	}

	/**
	 * Handle unknown command
	 */
	private function handleUnknownCommand(int $chatId, string $commandName): array
	{
		Log::warning("Unknown command received", [
			"chat_id" => $chatId,
			"command" => $commandName,
		]);

		$availableCommands = array_keys($this->commands);
		$response = "Perintah `{$commandName}` tidak dikenali.\n\n";
		$response .= "Perintah yang tersedia:\n";

		foreach ($availableCommands as $cmd) {
			$command = $this->commands[$cmd];
			$response .= "/{$cmd} - {$command->getDescription()}\n";
		}

		$telegramApi = app(TelegramApi::class);
		$telegramApi->sendMessage($chatId, $response);

		return [
			"status" => "unknown_command",
			"chat_id" => $chatId,
			"response" => $response,
			"available_commands" => $availableCommands,
		];
	}

	/**
	 * Get all registered commands for help
	 */
	public function getCommands(): array
	{
		return $this->commands;
	}

	/**
	 * Get middleware for a specific command
	 */
	public function getCommandMiddleware(string $commandName): array
	{
		return $this->commandMiddleware[$commandName] ?? [];
	}
}
