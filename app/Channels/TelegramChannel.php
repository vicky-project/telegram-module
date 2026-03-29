<?php

namespace Modules\Telegram\Channels;

use Illuminate\Notifications\Notification;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Support\TelegramMarkdownHelper;

class TelegramChannel
{
  /**
  * Create a new channel instance.
  */
  public function __construct(protected TelegramApi $telegramApi) {}

  /**
  * Authenticate the user's access to the channel.
  */
  public function send(mixed $notifiable, Notification $notification) {
    if (!method_exists($notification, 'toTelegram')) {
      \Log::warning("Method toTelegram not exist in class: {class}", ["class" => get_class($notification)]);
      return;
    }

    $telegramId = $notifiable->routeNotificationFor("telegram");
    if (!$telegramId) {
      \Log::warning("Telegram ID not found.", [
        "telegram_id" => $telegramId,
        "notifiable" => $notifiable,
        "notification" => $notification
      ]);
      return;
    }

    $message = $notification->toTelegram($notifiable);
    if (is_string($message)) {
      $this->telegramApi->sendMessage($telegramId, $message);
    } elseif (is_array($message)) {
      $text = $message["text"];
      if (isset($message["parse_mode"])) {
        $text = TelegramMarkdownHelper::safeText($text, $message["parse_mode"]);
      }

      $this->telegramApi->sendMessage($telegramId, $text, $message["parse_mode"] ?? null, $message["reply_markup"] ?? null);
    }
  }
}