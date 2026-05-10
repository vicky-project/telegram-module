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

    // Handle command
    if ($this->isCommand($text)) {
      return $this->commandDispatcher->handleCommand($chatId, $text, $username);
    }

    if ($location) {
      return $this->locationDispatcher->handleLocation($chatId, $location->getLatitude(), $location->getLongitude(), $username);
    }


    if ($replyToMessage) {
      // handle replyToMessage
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
    return $this->sendDefaultMessage($chatId);
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