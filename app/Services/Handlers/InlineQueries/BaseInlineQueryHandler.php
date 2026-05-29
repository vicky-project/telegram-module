<?php
namespace Modules\Telegram\Services\Handlers\InlineQueries;

use Telegram\Bot\Objects\InlineQuery;
use Modules\Telegram\Interfaces\TelegramInlineQueryHandlerInterface;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Support\TelegramMarkdownHelper;

abstract class BaseInlineQueryHandler implements TelegramInlineQueryHandlerInterface
{
  protected TelegramApi $telegramApi;

  /**
  * Parse mode default untuk hasil inline.
  * Override di child class jika ingin semua hasil pakai parse mode tertentu.
  * null = tidak ada parse mode.
  */
  protected ?string $defaultParseMode = null;

  public function __construct(TelegramApi $telegramApi) {
    $this->telegramApi = $telegramApi;
  }

  abstract public function getName(): string;

  public function getPattern(): string
  {
    return '*';
  }

  final public function handle(InlineQuery $inlineQuery, array $context): array
  {
    $context = array_merge($context, [
      'query_text' => $inlineQuery->getQuery() ?? '',
      'offset' => $inlineQuery->getOffset() ?? '',
    ]);
    return $this->process($context);
  }

  abstract protected function process(array $context): array;

  // ─── Helper Methods ──────────────────────────────

  protected function answerInlineQuery(
    string $inlineQueryId,
    array $results,
    array $params = []
  ): bool {
    return $this->telegramApi->answerInlineQuery($inlineQueryId, $results, $params);
  }

  /**
  * Escape teks sesuai parse mode.
  */
  protected function escapeText(string $text, ?string $parseMode = 'Markdown'): string
  {
    return TelegramMarkdownHelper::safeText($text, $parseMode);
  }

  /**
  * Buat satu artikel hasil inline.
  *
  * @param string $id
  * @param string $title
  * @param string $messageText (harus sudah di-escape jika pakai parse_mode)
  * @param string $description
  * @param array $extra
  * @param string|null $parseMode null = tidak pakai parse mode
  * @return array
  */
  protected function makeArticleResult(
    string $id,
    string $title,
    string $messageText,
    string $description = '',
    array $extra = [],
    ?string $parseMode = null
  ): array {
    $content = ['message_text' => $messageText];
    if ($parseMode !== null) {
      $content['message_text'] = $this->escapeText($messageText, $parseMode);
      $content['parse_mode'] = $parseMode;
    }

    return array_merge([
      'type' => 'article',
      'id' => $id,
      'title' => $title,
      'input_message_content' => $content,
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
    array $extra = [],
    ?string $parseMode = null
  ): array {
    $result = [
      'type' => 'photo',
      'id' => $id,
      'photo_url' => $photoUrl,
      'thumb_url' => $thumbUrl,
    ];
    if ($title) $result['title'] = $title;
    if ($description) $result['description'] = $description;
    if ($caption) {
      $result['caption'] = $caption;
      if ($parseMode) $result['parse_mode'] = $parseMode;
    }
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