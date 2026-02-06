<?php
namespace Modules\Telegram\Interfaces;

interface TelegramCommandInterface
{
	/**
	 * Handle the command
	 */
	public function handle(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array;

	/**
	 * Get command name
	 */
	public function getName(): string;

	/**
	 * Get command description for help
	 */
	public function getDescription(): string;
}
