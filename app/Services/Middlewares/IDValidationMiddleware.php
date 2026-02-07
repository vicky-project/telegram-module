<?php
namespace Modules\Telegram\Services\Middlewares;

use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Support\TelegramIdentifier;
use Modules\Telegram\Services\Support\TelegramApi;

class IDValidationMiddleware implements TelegramMiddlewareInterface
{
	protected TelegramIdentifier $identifiers;
	protected TelegramApi $telegram;

	public function __construct(
		TelegramIdentifier $identifiers,
		TelegramApi $telegram
	) {
		$this->identifiers = $identifiers;
		$this->telegram = $telegram;
	}

	public function handle(array $context, callable $next)
	{
		$userId = $context["user_id"] ?? null;
		$chatId = $context["chat_id"] ?? null;

		\Log::debug("Using identifier", [
			"user_id" => $userId,
			"chat_id" => $chatId,
		]);

		if (!$userId) {
			if (!$chatId) {
				\Log::error("Missing Telegram identifiers", [
					"user_id" => $userId,
					"chat_id" => $chatId,
				]);

				return [
					"status" => "error",
					"message" => "Missing Telegram identifiers",
					"answer" => "Missing telegram identifiers",
					"block_handler" => true,
				];
			}
			$userId = $chatId;
		}

		if (!$this->identifiers->validateIds($userId, $chatId)) {
			\Log::error("Invalid Telegram identifiers", [
				"user_id" => $userId,
				"chat_id" => $chatId,
			]);

			return [
				"status" => "error",
				"message" => "Invalid Telegram identifiers",
				"answer" => "Invalid Telegram identifiers",
				"block_handler" => true,
			];
		}

		// For this now only support private chat
		if (!$this->identifiers->isPrivateChat($chatId)) {
			if (!isset($context["callback_id"])) {
				$this->telegram->sendMessage($chatId, "Only support for private chat");
			}

			\Log::error("Only support for private chat only", ["chat_id" => $chatId]);

			return [
				"status" => "error",
				"message" => "Only support for private chat",
				"answer" => "Only support for private chat",
				"block_handler" => true,
			];
		}

		return $next($context);
	}
}
