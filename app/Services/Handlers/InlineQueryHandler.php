<?php
namespace Modules\Telegram\Services\Handlers;

use Telegram\Bot\Objects\InlineQuery;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramInlineQueryHandlerInterface;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Support\TelegramApi;

class InlineQueryHandler
{
  private array $handlers = [];
  private array $middleware = [];
  private array $globalMiddlewares = [];
  private array $handlerMiddleware = [];

  protected TelegramApi $telegramApi;

  public function __construct(TelegramApi $telegramApi) {
    $this->telegramApi = $telegramApi;
  }

  public function registerHandler(
    TelegramInlineQueryHandlerInterface $handler,
    array $middleware = []
  ): void {
    $pattern = $handler->getPattern();
    $this->handlers[$pattern] = $handler;
    if (!empty($middleware)) {
      $this->handlerMiddleware[$pattern] = $middleware;
    }
  }

  public function registerMiddleware(string $name, TelegramMiddlewareInterface $middleware): void
  {
    $this->middleware[$name] = $middleware;
  }

  /**
  * Dipanggil dari UpdateHandler.
  */
  public function handle(InlineQuery $inlineQuery): array
  {
    $queryId = $inlineQuery->getId();
    $queryText = $inlineQuery->getQuery() ?? '';
    $from = $inlineQuery->getFrom();
    $userId = $from->getId();
    $username = $from->getUsername();
    $firstName = $from->getFirstName();
    $lastName = $from->getLastName();
    $offset = $inlineQuery->getOffset() ?? '';

    // Cari handler berdasarkan query text
    $handler = $this->findMatchingHandler($queryText);

    if (!$handler) {
      // Kirim hasil kosong dengan tombol menuju PM
      $this->telegramApi->answerInlineQuery($queryId, [], [
        'cache_time' => 0,
        'switch_pm_text' => 'Bantuan',
        'switch_pm_parameter' => 'start',
      ]);
      return ['status' => 'no_handler'];
    }

    // Siapkan context
    $context = [
      'query_id' => $queryId,
      'query_text' => $queryText,
      'user_id' => $userId,
      'username' => $username,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'offset' => $offset,
      'inline_query' => $inlineQuery,
      'timestamp' => now(),
    ];

    // Jalankan middleware pipeline
    $middlewareStack = $this->getMiddlewareStack($handler->getPattern());
    $pipeline = $this->createPipeline($middlewareStack, function($context) use ($handler, $inlineQuery) {
      return $handler->handle($inlineQuery, $context);
    });

    $result = $pipeline($context);

    // Ambil nilai dari hasil handler
    $results = $result['results'] ?? $result;
    $nextOffset = $result['next_offset'] ?? '';
    $cacheTime = $result['cache_time'] ?? 300;
    $isPersonal = $result['is_personal'] ?? true;

    $this->telegramApi->answerInlineQuery($queryId, $results, [
      'cache_time' => $cacheTime,
      'next_offset' => $nextOffset,
      'is_personal' => $isPersonal,
    ]);

    return [
      'status' => 'success',
      'handler' => $handler->getName(),
    ];
  }

  private function findMatchingHandler(string $query): ?TelegramInlineQueryHandlerInterface
  {
    // Exact match
    if (isset($this->handlers[$query])) {
      return $this->handlers[$query];
    }
    // Pattern matching
    foreach ($this->handlers as $pattern => $handler) {
      if ($this->patternMatches($pattern, $query)) {
        return $handler;
      }
    }
    return null;
  }

  private function patternMatches(string $pattern, string $query): bool
  {
    if ($pattern === $query) {
      return true;
    }
    $regex = $this->patternToRegex($pattern);
    return (bool)preg_match($regex, $query);
  }

  private function patternToRegex(string $pattern): string
  {
    $escaped = preg_quote($pattern, '#');
    // * -> .*
    $escaped = str_replace('\*', '.*', $escaped);
    // {var} -> ([^ ]+)
    $escaped = preg_replace('/\\\\\{[^}]+\}/', '([^ ]+)', $escaped);
    return '#^' . $escaped . '$#iu';
  }

  private function getMiddlewareStack(string $pattern): array
  {
    return array_merge(
      $this->globalMiddlewares,
      $this->handlerMiddleware[$pattern] ?? []
    );
  }

  private function createPipeline(array $middleware, callable $handler): callable
  {
    return array_reduce(
      array_reverse($middleware),
      function ($next, $name) {
        return function ($context) use ($name, $next) {
          if (!isset($this->middleware[$name])) {
            Log::warning("Middleware not found", ['name' => $name]);
            return $next($context);
          }
          return $this->middleware[$name]->handle($context, $next);
        };
      },
      $handler
    );
  }
}