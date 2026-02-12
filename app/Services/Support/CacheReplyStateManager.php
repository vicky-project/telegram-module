<?php
namespace Modules\Telegram\Services\Support;

use Illuminate\Support\Facades\Cache;

class CacheReplyStateManager
{
	protected const CACHE_PREFIX = "telegram_reply:";
	protected const CACHE_TTL = 60; // menit

	public static function expectReply(
		int $chatId,
		int $messageId,
		string $handlerIdentifier,
		array $context = []
	): void {
		$key = static::getCacheKey($chatId, $messageId);

		$data = [
			"handler" => $handlerIdentifier,
			"context" => $context,
			"created_at" => now()->toDateTimeString(),
		];

		Cache::put(
			$key,
			$data,
			now()->addMinutes(config("telegram.bot.cache.duration", self::CACHE_TTL))
		);
	}

	public static function getReplyState(
		int $chatId,
		int $replyToMessageId
	): ?array {
		$key = static::getCacheKey($chatId, $replyToMessageId);
		return Cache::get($key);
	}

	public static function forgetReply(int $chatId, int $messageId): void
	{
		$key = static::getCacheKey($chatId, $messageId);
		Cache::forget($key);
	}

	public static function getCacheKey(int $chatId, int $messageId): string
	{
		return config("telegram.bot.cache.prefix", self::CACHE_PREFIX) .
			$chatId .
			":" .
			$messageId;
	}
}
