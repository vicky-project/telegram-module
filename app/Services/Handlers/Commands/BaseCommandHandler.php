<?php
namespace Modules\Telegram\Services\Handlers;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Traits\MessageOperations;

abstract class BaseCommandHandler implements TelegramCommandInterface
{
	use MessageOperations;

	protected TelegramApi $telegramApi;

	public function __construct(TelegramApi $telegramApi)
	{
		$this->telegramApi = $telegramApi;
	}

	/**
	 * Get command name
	 */
	abstract public function getName(): string;

	/**
	 * Get command description
	 */
	abstract public function getDescription(): string;

	/**
	 * Handle command with operations support
	 */
	public function handle(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array {
		return $this->handleCommandWithOperations(
			$chatId,
			$text,
			$username,
			$params,
			function ($chatId, $text, $username, $params) {
				return $this->processCommand($chatId, $text, $username, $params);
			}
		);
	}

	/**
	 * Process command (to be implemented by child class)
	 */
	abstract protected function processCommand(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array;
}
