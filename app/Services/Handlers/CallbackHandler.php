<?php
namespace Modules\Telegram\Services\Handlers;

use Telegram\Bot\Objects\CallbackQuery;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramCallbackHandlerInterface;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Handlers\Callbacks\GlobalCallbackParser;
use Modules\Telegram\Services\Support\TelegramApi;

class CallbackHandler
{
	/**
	 * Registered callback handlers
	 *
	 * @var array<string, CallbackHandlerInterface>
	 */
	private array $handlers = [];

	/**
	 * Registered middleware
	 *
	 * @var array<string, MiddlewareInterface>
	 */
	private array $middleware = [];

	// Global middleware
	private array $globalMiddlewares = ["ids", "callback-throttle"];

	/**
	 * Middleware stack for each handler pattern
	 *
	 * @var array<string, array>
	 */
	private array $handlerMiddleware = [];

	protected TelegramApi $telegramApi;

	public function __construct(TelegramApi $telegramApi)
	{
		$this->telegramApi = $telegramApi;
	}

	/**
	 * Register a callback handler
	 */
	public function registerHandler(
		TelegramCallbackHandlerInterface $handler,
		array $middleware = []
	): void {
		$pattern = $handler->getPattern();
		$this->handlers[$pattern] = $handler;

		if (!empty($middleware)) {
			$this->handlerMiddleware[$pattern] = $middleware;
		}

		Log::debug("Callback handler registered", [
			"pattern" => $pattern,
			"name" => $handler->getName(),
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
		Log::debug("Callback middleware registered", ["name" => $name]);
	}

	/**
	 * Handle incoming callback query
	 */
	public function handle(CallbackQuery $callbackQuery): array
	{
		try {
			$chatId = $callbackQuery
				->getMessage()
				->getChat()
				->getId();
			$messageId = $callbackQuery->getMessage()->getMessageId();
			$callbackData = $callbackQuery->getData();
			$callbackId = $callbackQuery->getId();

			// Get user information
			$from = $callbackQuery->getFrom();
			$userId = $from->getId();
			$username = $from->getUsername();
			$firstName = $from->getFirstName();
			$lastName = $from->getLastName();

			Log::info("Callback received", [
				"callback_id" => $callbackId,
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"user_id" => $userId,
				"username" => $username,
				"callback_data" => $callbackData,
			]);

			// Parse callback data
			$parsedData = $this->parseCallbackData($callbackData);

			// Find matching handler
			$handler = $this->findMatchingHandler($parsedData);

			if (!$handler) {
				Log::warning("No handler found for callback", [
					"callback_data" => $callbackData,
					"parsed_data" => $parsedData,
				]);

				return $this->handleUnknownCallback($callbackId, $chatId);
			}

			// Get middleware for this handler
			$middlewareStack = $this->getMiddlewareStack($handler->getPattern());

			// Create middleware pipeline
			$pipeline = $this->createPipeline($middlewareStack, function (
				$context
			) use ($handler, $parsedData) {
				return $handler->handle($parsedData, $context);
			});

			// Prepare context
			$context = [
				"callback_id" => $callbackId,
				"callback_query" => $callbackQuery,
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"user_id" => $userId,
				"username" => $username,
				"first_name" => $firstName,
				"last_name" => $lastName,
				"callback_data" => $callbackData,
				"parsed_data" => $parsedData,
				"timestamp" => now(),
			];

			// Execute pipeline
			$result = $pipeline($context);

			// Acknowledge callback query (to remove loading state)
			$this->answerCallbackQuery(
				$callbackId,
				$result["answer"] ?? ($result["message"] ?? "Ok")
			);

			Log::info("Callback handled successfully", [
				"callback_id" => $callbackId,
				"handler" => $handler->getName(),
				"result" => array_keys($result),
			]);

			return array_merge($result, [
				"status" => "success",
				"callback_id" => $callbackId,
				"handler" => $handler->getName(),
			]);
		} catch (\Exception $e) {
			Log::error("Failed to handle callback", [
				"callback_id" => $callbackQuery->getId() ?? "unknown",
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			// Answer with error to prevent hanging loading state
			try {
				$this->telegramApi->answerCallbackQuery(
					$callbackQuery->getId(),
					"Terjadi kesalahan saat memproses permintaan.",
					true
				);
			} catch (\Exception $alertError) {
				Log::error("Failed to answer callback query with error", [
					"callback_id" => $callbackQuery->getId() ?? "unknown",
					"error" => $alertError->getMessage(),
				]);
			}

			return [
				"status" => "error",
				"message" => "Terjadi kesalahan saat memproses callback.",
				"callback_id" => $callbackQuery->getId() ?? "unknown",
			];
		}
	}

	/**
	 * Parse callback data into structured format
	 */
	private function parseCallbackData(string $callbackData): array
	{
		$parser = app(GlobalCallbackParser::class);

		return $parser->parse($callbackData);
	}

	/**
	 * Find matching handler for parsed callback data
	 */
	private function findMatchingHandler(
		array $parsedData
	): ?TelegramCallbackHandlerInterface {
		$callbackData = $parsedData["full"];

		// First, try exact match
		if (isset($this->handlers[$callbackData])) {
			return $this->handlers[$callbackData];
		}

		// Then, try pattern matching
		foreach ($this->handlers as $pattern => $handler) {
			if ($this->patternMatches($pattern, $callbackData)) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * Check if callback data matches a pattern
	 */
	private function patternMatches(string $pattern, string $callbackData): bool
	{
		// Check for exact match if no wildcards
		if ($pattern === $callbackData) {
			return true;
		}

		// Check for pattern match
		$regex = $this->patternToRegex($pattern);
		if (preg_match($regex, $callbackData)) {
			return true;
		}

		return $this->matchesHierarchicalPattern($pattern, $parsedData);
	}

	private function patternToRegex(string $pattern): string
	{
		// Convert pattern to regex
		$pattern = str_replace("\*", ".*", preg_quote($pattern, "/"));
		$pattern = preg_replace("/\\\{(\w+)\\\}/", "[^:]+", $pattern);
		return '/^{$pattern}$/';
	}

	private function matchesHierarchicalPattern(
		string $pattern,
		array $parsedData
	): bool {
		$patternParts = explode(":", $pattern);
		$dataParts = explode(":", $parsedData["full"]);

		if (count($patternParts) !== count($parsedData)) {
			return false;
		}

		foreach ($patternParts as $index => $patternPart) {
			$dataPart = $dataParts[$index];

			if ($patternPart === "*") {
				continue;
			}

			if (
				strpos($patternPart, "{") === 0 &&
				strpos($patternPart, "}") === strlen($patternPart) - 1
			) {
				continue;
			}

			if ($patternPart !== $dataParts) {
				return false;
			}
		}

		return true;
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
	 * Get middleware stack for handler pattern
	 */
	private function getMiddlewareStack(string $pattern): array
	{
		$stack = [];

		// Add global middleware (optional)
		$stack = array_merge($stack, $this->globalMiddlewares);

		// Add handler-specific middleware
		if (isset($this->handlerMiddleware[$pattern])) {
			$stack = array_merge($stack, $this->handlerMiddleware[$pattern]);
		}

		return $stack;
	}

	/**
	 * Answer callback query to remove loading state
	 */
	private function answerCallbackQuery(string $callbackId, string $text): void
	{
		try {
			$this->telegramApi->answerCallbackQuery(
				$callbackId,
				$text,
				strlen($text) > 100
			);
		} catch (\Exception $e) {
			Log::error("Failed to answer callback query", [
				"callback_id" => $callbackId,
				"error" => $e->getMessage(),
			]);
		}
	}

	/**
	 * Handle unknown callback
	 */
	private function handleUnknownCallback(string $callbackId, int $chatId): array
	{
		$this->telegramApi->answerCallbackQuery(
			$callbackId,
			"Aksi tidak dikenali atau telah kadaluarsa.",
			true
		);

		Log::warning("Unknown callback handled", [
			"callback_id" => $callbackId,
			"chat_id" => $chatId,
		]);

		return [
			"status" => "unknown_callback",
			"callback_id" => $callbackId,
			"message" => "Callback tidak dikenali",
		];
	}

	/**
	 * Get all registered handlers
	 */
	public function getHandlers(): array
	{
		return $this->handlers;
	}

	/**
	 * Get middleware for a specific handler pattern
	 */
	public function getHandlerMiddleware(string $pattern): array
	{
		return $this->handlerMiddleware[$pattern] ?? [];
	}

	/**
	 * Process inline button callback
	 * Helper method for creating callback data
	 */
	public function createCallbackData(string $action, array $params = []): string
	{
		$parts = array_merge([$action], $params);
		return implode(":", $parts);
	}

	/**
	 * Generate inline keyboard with callback buttons
	 */
	public function createInlineKeyboard(array $buttons): array
	{
		$keyboard = [];

		foreach ($buttons as $row) {
			$keyboardRow = [];
			foreach ($row as $button) {
				$keyboardRow[] = [
					"text" => $button["text"],
					"callback_data" => $button["callback_data"],
				];
			}
			$keyboard[] = $keyboardRow;
		}

		return $keyboard;
	}
}
