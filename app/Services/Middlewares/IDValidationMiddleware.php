<?php
namespace Modules\Telegram\Services\Middlewares;

use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Support\TelegramIdentifier;
use Modules\Telegram\Services\Support\TelegramApi;

class IDValidationMiddleware implements TelegramMiddlewareInterface
{
	protected TelegramIdentifier $identifier;
	protected TelegramApi $telegram;

	public function __construct(
		TelegramIdentifier $identifier,
		TelegramApi $telegram
	) {
		$this->identifier = $identifier;
		$this->telegram = $telegram;
	}

	public function handle(array $context, callable $next)
	{
		$userId = $context["user_id"] ?? null;
		$chatId = $context["chat_id"] ?? null;

		\Log::debug("Using identifier", ["user_id" => $userId, "chat_id" => $cba]);

		if (!$userId || !$chatId) {
			return [
				"status" => "error",
				"message" => "Missing Telegram identifiers",
				"block_handler" => true,
			];
		}

		if (!$this->identifier->validateIds($userId, $chatId)) {
			return [
				"status" => "error",
				"message" => "Invalid Telegram identifiers",
				"block_handler" => true,
			];
		}

		// For this now only support private chat
		if (!$this->identifiers->isPrivateChat($chatId)) {
			if (!isset($context["callback_id"])) {
				$this->telegram->sendMessage($chatId, "Only support for private chat");
			}

			return [
				"status" => "error",
				"message" => "Only support for private chat",
				"block_handler" => true,
			];
		}

		return $next($context);
	}
}
