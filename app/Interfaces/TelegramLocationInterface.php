<?php
namespace Modules\Telegram\Interfaces;

interface TelegramLocationInterface
{
  /**
  * Nama unik handler (identifier)
  */
  public function getName(): string;

  /**
  * Menangani pesan lokasi
  *
  * @param array $parsedData Data lokasi (latitude, longitude, dll)
  * @param array $context Konteks dari middleware
  * @return array Response
  */
  public function handle(array $parsedData, array $context): array;
}