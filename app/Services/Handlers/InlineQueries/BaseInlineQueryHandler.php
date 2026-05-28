<?php
namespace Modules\Telegram\Services\Handlers\InlineQueries;

use Telegram\Bot\Objects\InlineQuery;
use Modules\Telegram\Interfaces\TelegramInlineQueryHandlerInterface;
use Modules\Telegram\Services\Support\TelegramApi;

abstract class BaseInlineQueryHandler implements TelegramInlineQueryHandlerInterface
{
  protected TelegramApi $telegramApi;

  public function __construct(TelegramApi $telegramApi) {
    $this->telegramApi = $telegramApi;
  }

  /**
  * Nama handler (wajib dioverride).
  */
  abstract public function getName(): string;

  /**
  * Pattern default (override jika ingin filter spesifik).
  */
  public function getPattern(): string
  {
    return '*';
  }

  /**
  * Implementasi interface: mengekstrak data dari InlineQuery,
  * lalu memanggil process() yang akan dioverride child.
  */
  final public function handle(InlineQuery $inlineQuery, array $context): array
  {
    // Gabungkan data dari InlineQuery ke context
    $context = array_merge($context, [
      'query_text' => $inlineQuery->getQuery() ?? '',
      'offset' => $inlineQuery->getOffset() ?? '',
    ]);

    // Panggil method abstrak yang diimplementasi child
    return $this->process($context);
  }

  /**
  * Method utama yang harus dioverride oleh child class.
  * Semua data sudah tersedia di $context.
  */
  abstract protected function process(array $context): array;

  // ─── Helper methods ────────────────

  protected function answerInlineQuery(
    string $inlineQueryId,
    array $results,
    array $params = []
  ): bool {
    return $this->telegramApi->answerInlineQuery($inlineQueryId, $results, $params);
  }

  protected function makeArticleResult(
    string $id,
    string $title,
    string $messageText,
    string $description = '',
    array $extra = []
  ): array {
    return array_merge([
      'type' => 'article',
      'id' => $id,
      'title' => $title,
      'input_message_content' => [
        'message_text' => $messageText,
      ],
      'description' => $description,
    ], $extra);
  }

  protected function makePhotoResult(
    string $id,
    string $photoUrl,
    string $thumbUrl,
    ?string $title = null,
    ?string $description = null,
    ?string $caption = null,
    array $extra = []
  ): array {
    $result = [
      'type' => 'photo',
      'id' => $id,
      'photo_url' => $photoUrl,
      'thumb_url' => $thumbUrl,
    ];
    if ($title) $result['title'] = $title;
    if ($description) $result['description'] = $description;
    if ($caption) $result['caption'] = $caption;
    return array_merge($result, $extra);
  }

  protected function getQueryText(array $context): string
  {
    return $context['query_text'] ?? '';
  }

  protected function getOffset(array $context): string
  {
    return $context['offset'] ?? '';
  }

  protected function successResult(array $results, array $options = []): array
  {
    return array_merge([
      'results' => $results,
      'cache_time' => 300,
      'is_personal' => true,
      'next_offset' => '',
    ], $options);
  }

  protected function emptyResult(
    string $switchPmText = 'Bantuan',
    string $switchPmParameter = 'start'
  ): array {
    return [
      'results' => [],
      'cache_time' => 0,
      'switch_pm_text' => $switchPmText,
      'switch_pm_parameter' => $switchPmParameter,
    ];
  }
}