<?php
namespace Modules\Telegram\Services\Handlers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramReplyHandlerInterface;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Support\CacheReplyStateManager;

class ReplyDispatcher
{
	protected array $handlers = [];
	protected array $middleware = [];
	protected array $handlerMiddleware = [];

	/**
	 * Register reply handler dengan middleware opsional
	 */
	public function registerHandler(
		TelegramReplyHandlerInterface $handler,
		array $middleware = []
	): void {
		$identifier = $handler->getIdentifier();
		$this->handlers[$identifier] = $handler;

		if (!empty($middleware)) {
			$this->handlerMiddleware[$identifier] = $middleware;
		}

		Log::debug("Reply handler registered with middleware", [
			"identifier" => $identifier,
			"handler" => get_class($handler),
			"middleware" => $middleware,
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
		Log::debug("Reply middleware registered", ["name" => $name]);
	}

	/**
	 * Memproses balasan dari user dengan middleware pipeline
	 */
	public function handleReply(
		int $chatId,
		string $replyText,
		int $replyToMessageId
	): array {
		$state = CacheReplyStateManager::getReplyState($chatId, $replyToMessageId);

		if (!$state) {
			Log::warning("Nothing state saved.");
			return [
				"status" => "unknown_state",
				"answer" => "Tidak ada apa apa disni",
				"message" => "Nothing else here",
				"show_alert" => true,
			];
		}

		$handlerIdentifier = $state["handler"];

		if (!isset($this->handlers[$handlerIdentifier])) {
			Log::error("Reply handler not found", ["handler" => $handlerIdentifier]);
			CacheReplyStateManager::forgetReply($chatId, $replyToMessageId);
			return [
				"status" => "unknown_handler",
				"chat_id" => $chatId,
				"answer" => "Reply not found.",
				"show_alert" => true,
			];
		}

		$handler = $this->handlers[$handlerIdentifier];
		$middlewareStack = $this->getMiddlewareStack($handlerIdentifier);

		// Buat pipeline
		$pipeline = $this->createPipeline($middlewareStack, function (
			$context
		) use ($handler, $replyText, $chatId, $replyToMessageId) {
			return $handler->handle($context, $replyText, $chatId, $replyToMessageId);
		});

		try {
			$context = $state["context"] ?? [];
			// Eksekusi pipeline
			$result = $pipeline($context);

			// Cek apakah middleware memblokir handler
			if ($this->isBlocked($result)) {
				// Hapus state karena request diblokir
				Log::warning("Message wes blocked", [
					"status" => $result["status"],
					"answer" => $result["answer"],
				]);
				CacheReplyStateManager::forgetReply($chatId, $replyToMessageId);
			}

			// Hapus state jika tidak ada flag keep_reply_state
			if (!($result["keep_reply_state"] ?? false)) {
				CacheReplyStateManage::forgetReply($chatId, $replyToMessageId);
			}

			return $result;
		} catch (\Exception $e) {
			Log::error("Reply handler execution failed", [
				"handler" => $handlerIdentifier,
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			CacheReplyStateManager::forgetReply($chatId, $replyToMessageId);
			return [
				"status" => "handler_failed",
				"answer" => "Reply handler execution failed",
				"chat_id" => $chatId,
				"show_alert" => true,
			];
		}
	}

	/**
	 * Cek apakah hasil dari middleware adalah block response
	 */
	protected function isBlocked($result): bool
	{
		return is_array($result) &&
			isset($result["block_handler"]) &&
			$result["block_handler"] === true;
	}

	/**
	 * Mendapatkan middleware stack untuk handler tertentu
	 */
	protected function getMiddlewareStack(string $handlerIdentifier): array
	{
		return $this->handlerMiddleware[$handlerIdentifier] ?? [];
	}

	/**
	 * Membuat middleware pipeline yang mendukung block handler
	 */
	protected function createPipeline(
		array $middlewareNames,
		callable $handler
	): callable {
		$pipeline = array_reduce(
			array_reverse($middlewareNames),
			function ($next, $middlewareName) {
				return function ($context) use ($middlewareName, $next) {
					if (!isset($this->middleware[$middlewareName])) {
						Log::warning("Reply middleware not found", [
							"name" => $middlewareName,
						]);
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
	 * Mendapatkan semua handler yang terdaftar
	 */
	public function getHandlers(): array
	{
		return $this->handlers;
	}
}
