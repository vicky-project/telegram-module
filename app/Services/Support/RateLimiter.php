<?php
namespace Modules\Telegram\Services\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RateLimiter
{
	protected int $maxAttempts = 10;
	protected int $decayMinutes = 1;

	public function isRateLimited(int $userId, string $type): bool
	{
		$key = $this->getKey($userId, $type);
		$attempts = Cache::get($key, 0);

		return $attempts >= $this->maxAttempts;
	}

	public function increment(int $userId, string $type): void
	{
		$key = $this->getKey($userId, $type);
		$attempts = Cache::get($key, 0);

		Cache::put($key, $attempts + 1, now()->addMinutes($this->decayMinutes));

		if ($attempts + 1 >= $this->maxAttempts) {
			Log::warning("Rate limit exceeded", [
				"user_id" => $userId,
				"type" => $type,
			]);
		}
	}

	public function reset(int $userId, string $type): void
	{
		$key = $this->getKey($userId, $type);
		Cache::forget($key);
	}

	protected function getKey(int $userId, string $type): string
	{
		return "telegram_rate_limit:{$userId}:{$type}";
	}
}
