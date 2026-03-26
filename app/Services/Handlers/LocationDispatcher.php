<?php
namespace Modules\Telegram\Services\Handlers;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramLocationInterface;
use Modules\Telegram\Interfaces\TelegramMiddlewareInterface;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Support\Cache\LocationStateManager;

class LocationDispatcher
{
  /**
  * Registered location handlers
  * @var array<string, TelegramLocationHandlerInterface>
  */
  private array $handlers = [];

  /**
  * Registered middleware
  * @var array<string, TelegramMiddlewareInterface>
  */
  private array $middleware = [];

  /**
  * Global middleware yang selalu dijalankan
  */
  private array $globalMiddlewares = []; // opsional

  /**
  * Middleware khusus per handler
  * @var array<string, array>
  */
  private array $handlerMiddleware = [];

  protected TelegramApi $telegramApi;

  public function __construct(TelegramApi $telegramApi) {
    $this->telegramApi = $telegramApi;
  }

  /**
  * Daftarkan handler lokasi
  */
  public function registerHandler(
    TelegramLocationInterface $handler,
    array $middleware = []
  ): void {
    $name = $handler->getName();
    $this->handlers[$name] = $handler;

    if (!empty($middleware)) {
      $this->handlerMiddleware[$name] = $middleware;
    }

    Log::info("Location handler registered: " . $name);
  }

  /**
  * Daftarkan middleware
  */
  public function registerMiddleware(string $name, TelegramMiddlewareInterface $middleware): void
  {
    $this->middleware[$name] = $middleware;
    Log::info("Location middleware registered: " . $name);
  }

  /**
  * Proses pesan lokasi masuk
  */
  public function handleLocation(
    int $chatId,
    float $latitude,
    float $longitude,
    ?string $username
  ): array {
    try {
      // Cek apakah ada handler yang diharapkan
      $expected = LocationStateManager::getExpectedLocation($chatId);

      if (!$expected || !isset($this->handlers[$expected['handler']])) {
        // Tidak ada yang menunggu lokasi atau handler tidak ditemukan
        return $this->handleNoExpectedHandler($chatId);
      }

      $handlerName = $expected['handler'];
      $handler = $this->handlers[$handlerName];
      $contextData = $expected['context'] ?? [];

      // Bersihkan state setelah diambil
      LocationStateManager::clearExpectedLocation($chatId);

      // Siapkan data lokasi terparsing
      $parsedData = [
        'latitude' => $latitude,
        'longitude' => $longitude,
        'chat_id' => $chatId,
        'username' => $username,
        'context' => $contextData,
        // konteks dari pemanggil
      ];

      // Dapatkan middleware stack untuk handler ini
      $middlewareStack = $this->getMiddlewareStack($handlerName);

      // Buat pipeline
      $pipeline = $this->createPipeline($middlewareStack, function ($context) use ($handler, $parsedData) {
        return $handler->handle($parsedData, $context);
      });

      // Siapkan context awal untuk middleware
      $context = [
        'chat_id' => $chatId,
        'username' => $username,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'parsed_data' => $parsedData,
        'timestamp' => now(),
      ];

      // Eksekusi pipeline
      $result = $pipeline($context);

      return array_merge($result, [
        'status' => 'success',
        'handler' => $handlerName,
      ]);

    } catch (\Exception $e) {
      Log::error("Failed to handle location", [
        'chat_id' => $chatId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      // Kirim pesan error ke user
      $this->telegramApi->sendMessage(
        $chatId,
        "❌ Terjadi kesalahan saat memproses lokasi Anda."
      );

      return [
        'status' => 'error',
        'message' => 'Gagal memproses lokasi',
      ];
    }
  }

  /**
  * Tangani ketika tidak ada handler yang diharapkan
  */
  private function handleNoExpectedHandler(int $chatId): array
  {
    Log::warning("No expected location handler for chat", ['chat_id' => $chatId]);

    $this->telegramApi->sendMessage(
      $chatId,
      "📍 Lokasi Anda diterima, tapi tidak ada proses yang membutuhkannya saat ini."
    );

    return [
      'status' => 'no_handler',
      'chat_id' => $chatId,
    ];
  }

  /**
  * Buat pipeline middleware
  */
  private function createPipeline(array $middleware, callable $handler): callable
  {
    $pipeline = array_reduce(
      array_reverse($middleware),
      function ($next, $middlewareName) {
        return function ($context) use ($middlewareName, $next) {
          if (!isset($this->middleware[$middlewareName])) {
            Log::warning("Middleware not found", ['name' => $middlewareName]);
            return $next($context);
          }

          $middleware = $this->middleware[$middlewareName];
          return $middleware->handle($context, $next);
        };
      },
      $handler
    );

    return $pipeline;
  }

  /**
  * Dapatkan stack middleware untuk handler tertentu
  */
  private function getMiddlewareStack(string $handlerName): array
  {
    $stack = [];

    // Global middleware
    $stack = array_merge($stack,
      $this->globalMiddlewares);

    // Handler-specific middleware
    if (isset($this->handlerMiddleware[$handlerName])) {
      $stack = array_merge($stack, $this->handlerMiddleware[$handlerName]);
    }

    return $stack;
  }

  /**
  * Dapatkan semua handler terdaftar
  */
  public function getHandlers(): array
  {
    return $this->handlers;
  }

  /**
  * Dapatkan middleware untuk handler tertentu
  */
  public function getHandlerMiddleware(string $handlerName): array
  {
    return $this->handlerMiddleware[$handlerName] ?? [];
  }
}