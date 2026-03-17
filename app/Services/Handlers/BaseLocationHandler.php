<?php
namespace Modules\Telegram\Services\Handlers;

use Modules\Telegram\Interfaces\TelegramLocationInterface;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Traits\MessageOperations;

abstract class BaseLocationHandler implements TelegramLocationInterface
{
  use MessageOperations;

  public function __construct(TelegramApi $telegramApi) {
    $this->telegramApi = $telegramApi;
  }

  /**
  * Get location handler name
  */
  abstract public function getName(): string;

  /**
  * Menangani pesan lokasi
  *
  * @param array $parsedData Data lokasi (latitude, longitude, dll)
  * @param array $context Konteks dari middleware
  * @return array Response
  */
  public function handle(array $parsedData, array $context): array
  {
    \Log::debug("Base location handler", ["parsed_data" =>$parsedData,"context" =>$context]);
    $chatId = $parsedData["chat_id"];
    $latitude = $parsedData["latitude"];
    $longitude = $parsedData["longitude"];

    return $this->processLocation($chatId, $latitude, $longitude, null, $context);
  }

  /**
  * Process command (to be implemented by child class)
  */
  abstract protected function processLocation(
    int $chatId,
    float $latitude,
    float $longitude,
    ?string $username = null,
    array $context = []
  ): array;
}