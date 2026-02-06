<?php
namespace Modules\Telegram\Interfaces;

interface TelegramCallbackHandlerInterface
{
	/**
	 * Handle the callback
	 *
	 * @param array $data Parsed callback data
	 * @param array $context Additional context (chat_id, message_id, user, etc.)
	 * @return array Response data
	 */
	public function handle(array $data, array $context): array;

	/**
	 * Get callback pattern that this handler can process
	 * Can be exact match or pattern with wildcards
	 *
	 * @return string Pattern (e.g., "user:profile:*", "invoice:pay:*", "menu:*")
	 */
	public function getPattern(): string;

	/**
	 * Get handler name for logging and debugging
	 */
	public function getName(): string;
}
