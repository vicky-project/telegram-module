<?php
namespace Modules\Telegram\Interfaces;

interface TelegramMiddlewareInterface
{
	public function handle(
		int $chatId,
		string $command,
		?string $argument,
		?string $username,
		callable $next
	): array;
}
