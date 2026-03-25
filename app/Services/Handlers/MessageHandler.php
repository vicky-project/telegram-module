<?php
namespace Modules\Telegram\Services\Handlers;

use Telegram\Bot\Objects\Message;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\CommandDispatcher;
use Modules\Telegram\Services\Handlers\ReplyDispatcher;
use Modules\Telegram\Services\Handlers\LocationDispatcher;
use Modules\Telegram\Services\Support\TelegramApi;

class MessageHandler
{
  protected CommandDispatcher $commandDispatcher;
  protected ReplyDispatcher $replyDispatcher;
  protected LocationDispatcher $locationDispatcher;
  protected TelegramApi $telegramApi;

  public function __construct(
    CommandDispatcher $commandDispatcher,
    ReplyDispatcher $replyDispatcher,
    LocationDispatcher $locationDispatcher,
    TelegramApi $telegramApi,
  ) {
    $this->commandDispatcher = $commandDispatcher;
    $this->replyDispatcher = $replyDispatcher;
    $this->locationDispatcher = $locationDispatcher;
    $this->telegramApi = $telegramApi;
  }

  /**
  * Handle incoming message
  */
  public function handle(Message $message): array
  {
    $chatId = $message->getChat()->getId();
    $text = $message->getText() ?? "";
    $username = $message->getChat()->getUsername();
    $replyToMessage = $message->getReplyToMessage();
    $location = $message->getLocation();

    Log::info("Telegram message received");

    // Handle command
    if ($this->isCommand($text)) {
      Log::info("Handling command");
      return $this->commandDispatcher->handleCommand($chatId, $text, $username);
    }

    if ($location) {
      Log::info("Location handling");
      return $this->locationDispatcher->handleLocation($chatId, $location->getLatitude(), $location->getLongitude(), $username);
    }


    if ($replyToMessage) {
      // handle replyToMessage
      Log::info("Handling to reply message");
      return $this->replyDispatcher->handleReply(
        $chatId,
        $text,
        $replyToMessage->getMessageId(),
      );
    }


    // Handle regular text message
    return $this->handleTextMessage($chatId, $text);
  }

  /**
  * Handle edited message
  */
  public function handleEditedMessage(Message $message): array
  {
    Log::info("Telegram edited message", [
      "chat_id" => $message->getChat()->getId(),
      "message_id" => $message->getMessageId(),
    ]);

    return ["status" => "edited_message_ignored"];
  }

  /**
  * Check if text is a command
  */
  private function isCommand(string $text): bool
  {
    return strpos($text, "/") === 0;
  }

  /**
  * Handle regular text messages
  */
  private function handleTextMessage(int $chatId, string $text): array
  {
    $useDeepseek = config("telegram.use_deepseek_ai", false);

    if (!$useDeepseek) {
      Log::info("Default message text sent.");
      return $this->sendDefaultMessage($chatId);
    }

    // Pastikan kelas DeepSeek tersedia
    if (!class_exists(\DeepSeek\DeepSeekClient::class)) {
      Log::error("DeepSeek class not found. Check installation.");
      return $this->sendDefaultMessage($chatId);
    }

    try {
      $deepseek = app(\DeepSeek\DeepSeekClient::class);
      $response = $deepseek->query($text, 'user')
      ->withModel("deepseek-chat")
      ->setTemperature(1.5)
      ->run();
      Log::info("Message replied by deepseek.ai", ["response" => $response]);

      if (isset($response->error)) {
        throw new \Exception($response->error->message);
      }

      return [
        "status" => "deepseek_replied",
        "chat_id" => $chatId,
        "response" => $response->success->message
      ];
    } catch (\Exception $e) {
      Log::error("DeepSeek API error: " . $e->getMessage());
      return $this->sendDefaultMessage($chatId);
    }
  }

  private function sendDefaultMessage(int $chatId): array
  {
    $appName = config("app.name");
    $response = "Halo! Saya adalah bot untuk aplikasi {$appName}.\n\nGunakan /help untuk melihat command yang tersedia.";
    $this->telegramApi->sendMessage($chatId, $response);
    return [
      "status" => "text_message",
      "chat_id" => $chatId,
      "response" => $response,
    ];
  }

  /**
  * Get chat information
  */
  public function getChatInfo(int $chatId): ?array
  {
    try {
      $chat = $this->telegramApi->getChat($chatId);

      return [
        "id" => $chat->getId(),
        "type" => $chat->getType(),
        "title" => $chat->getTitle(),
        "username" => $chat->getUsername(),
        "first_name" => $chat->getFirstName(),
        "last_name" => $chat->getLastName(),
      ];
    } catch (\Exception $e) {
      Log::error("Failed to get chat info", [
        "chat_id" => $chatId,
        "error" => $e->getMessage(),
      ]);

      return null;
    }
  }
}