<?php
namespace Modules\Telegram\Services;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Models\TelegramUser;
use Modules\Telegram\Services\Handlers\MessageHandler;
use Modules\Telegram\Services\Handlers\CallbackHandler;

class UpdateHandler
{
  protected Api $telegram;
  protected MessageHandler $messageHandler;
  protected CallbackHandler $callbackHandler;

  public function __construct(
    Api $telegram,
    MessageHandler $messageHandler,
    CallbackHandler $callbackHandler
  ) {
    $this->telegram = $telegram;
    $this->messageHandler = $messageHandler;
    $this->callbackHandler = $callbackHandler;
  }

  /**
  * Handle incoming webhook update
  */
  public function handle(Request $request): array
  {
    try {
      $update = $this->telegram->getWebhookUpdate();

      activity()->withProperties([
        "telegram_id" => $update->getMessage()->getChat()->getId(),
        "text" => $update->getMessage()->getText()
      ])->log("Mengirim pesan ke bot.");

      if ($update->has("message")) {
        $this->recordUser($update->getMessage());
        return $this->messageHandler->handle($update->getMessage());
      }

      if ($update->has("edited_message")) {
        return $this->messageHandler->handleEditedMessage(
          $update->getEditedMessage()
        );
      }

      if ($update->has("callback_query")) {
        return $this->callbackHandler->handle($update->getCallbackQuery());
      }

      Log::warning("Unhandled update type", [
        "update_id" => $update->getUpdateId(),
        "types" => array_keys($update->toArray()),
      ]);

      return ["status" => "unhandled",
        "update_id" => $update->getUpdateId()];
    } catch (TelegramSDKException $e) {
      Log::error("Telegram SDK error", [
        "error" => $e->getMessage(),
        "trace" => $e->getTraceAsString(),
      ]);
      throw $e;
    }
  }

  private function recordUser(Message $message): TelegramUser
  {
    $chat = $message->getChat();
    $chatId = $chat->getId();

    $data = [
      'first_name' => $chat->getFirstName() ?? null,
      'last_name' => $chat->getLastName() ?? null,
      'username' => $chat->getUsername() ?? null,
      'photo_url' => $chat->getPhoto() ?? null,
      "auth_date" => now()->format("d-m-Y H:i:s")
    ];

    $telegramUser = TelegramUser::firstOrCreate(
      ["telegram_id" => $chatId],
      [
        "first_name" => $data["first_name"],
        "last_name" => $data["last_name"],
        "username" => $data["username"],
        "photo_url" => $data["photo_url"],
        'data' => $data,
      ]
    );

    if ($telegramUser->wasRecentlyCreated) {
      Log::info("New Telegram user recorded", [
        "telegram_id" => $chatId,
        "username" => $data["username"]
      ]);
    } else {
      $changed = false;
      if ($telegramUser->first_name != $data["first_name"]) {
        $telegramUser->first_name = $data["first_name"];
        $changed = true;
      }
      if ($telegramUser->last_name != $data["last_name"]) {
        $telegramUser->last_name = $data["last_name"];
        $changed = true;
      }
      if ($telegramUser->username != $data["username"]) {
        $telegramUser->username = $data["username"];
        $changed = true;
      }
      if ($telegramUser->photo_url != $data["photo_url"]) {
        $telegramUser->photo_url = $data["photo_url"];
        $changed = true;
      }

      $oldData = $telegramUser->data ?? [];
      $oldData["auth_date"] = $data["auth_date"];
      $telegramUser->data = $oldData;
      $changed = true;

      if ($changed) {
        $telegramUser->save();
        Log::info("Telegram user data updated", [
          "telegram_id" => $chatId,
          "username" => $data["username"]
        ]);
      }
    }

    return $telegramUser;
  }

  /**
  * Get webhook info
  */
  public function getWebhookInfo(): array
  {
    $info = $this->telegram->getWebhookInfo();

    return [
      "url" => $info->getUrl(),
      "has_custom_certificate" => $info->getHasCustomCertificate(),
      "pending_update_count" => $info->getPendingUpdateCount(),
      "last_error_date" => $info->getLastErrorDate(),
      "last_error_message" => $info->getLastErrorMessage(),
      "max_connections" => $info->getMaxConnections(),
      "allowed_updates" => $info->getAllowedUpdates(),
    ];
  }

  /**
  * Set webhook URL
  */
  public function setWebhook(string $url, ?string $secretToken = null): bool
  {
    $params = [
      "url" => $url,
      "max_connections" => 40,
      "allowed_updates" => config("telegram.bot.allowed_updates", ["message"]),
    ];

    if ($secretToken) {
      $params["secret_token"] = $secretToken;
    }

    try {
      $result = $this->telegram->setWebhook($params);
      if ($result) {
        Log::info("Webhook set successfuly", [
          "url" => $url,
          "has_secret" => !empty($secretToken)
        ]);
        return true;
      } else {
        Log::warning("Failed to set webhook", ["url" => $url]);
        return false;
      }
    } catch(\Exception $e) {
      Log::error("Exception while setting webhook.", [
        "url" => $url,
        "error" => $e->getMessage()
      ]);

      return false;
    }
  }

  /**
  * Remove webhook
  */
  public function removeWebhook(): bool
  {
    try {
      $response = $this->telegram->removeWebhook();
      if ($response) {
        Log::info("Webhook removed successfuly.");
        return true;
      } else {
        Log::warning("Failed to remove webhook");
        return false;
      }
    } catch(\Exception $e) {
      Log::error("Exception while removing webhook", [
        "error" => $e->getMessage()
      ]);
      return false;
    }
  }
}