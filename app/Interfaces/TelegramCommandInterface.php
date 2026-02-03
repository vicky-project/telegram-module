<?php
namespace Modules\Telegram\Interfaces;

interface TelegramCommandInterface
{
	public function getCommandName(): string;
	public function getDescription(): string; // Untuk /help
	public function requiresLinkedUser(): bool; // Tentukan apakah perlu user terhubung
	public function handle(
		int $chatId,
		?string $argument,
		?string $username,
		$user = null
	): array;
}
