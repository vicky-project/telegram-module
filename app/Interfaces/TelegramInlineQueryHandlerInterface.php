<?php
namespace Modules\Telegram\Interfaces;

use Telegram\Bot\Objects\InlineQuery;

interface TelegramInlineQueryHandlerInterface
{
  /**
  * Pattern yang akan dicocokkan dengan query teks dari pengguna.
  */
  public function getPattern(): string;

  /**
  * Nama handler (untuk logging).
  */
  public function getName(): string;

  /**
  * Tangani inline query.
  *
  * @param InlineQuery $inlineQuery
  * @param array $context Data tambahan (user_id, dll.)
  * @return array Harus mengandung 'results' (array hasil) dan opsional 'next_offset', 'cache_time', dll.
  */
  public function handle(InlineQuery $inlineQuery, array $context): array;
}