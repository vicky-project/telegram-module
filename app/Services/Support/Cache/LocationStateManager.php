<?php
namespace Modules\Telegram\Services\Support\Cache;

use Illuminate\Support\Facades\Cache;

class LocationStateManager
{
  protected const PREFIX = 'telegram_location_';
  protected const TTL = 300; // 5 menit

  /**
  * Set state bahwa user sedang diharapkan mengirim lokasi
  *
  * @param int $chatId
  * @param string $handlerIdentifier
  * @param array $context
  */
  public static function expectLocation(int $chatId, string $handlerIdentifier, array $context = []): void
  {
    $key = self::PREFIX . $chatId;
    Cache::put($key, [
      'handler' => $handlerIdentifier,
      'context' => $context,
      'expires_at' => now()->addMinutes("telegram.bot.cache.duration", self::TTL)->timestamp,
    ], self::TTL);
  }

  /**
  * Ambil state yang diharapkan, jika ada
  */
  public static function getExpectedLocation(int $chatId): ?array
  {
    $key = self::PREFIX . $chatId;
    return Cache::get($key);
  }

  /**
  * Hapus state
  */
  public static function clearExpectedLocation(int $chatId): void
  {
    $key = self::PREFIX . $chatId;
    Cache::forget($key);
  }
}