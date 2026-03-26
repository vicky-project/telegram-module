<?php

namespace Modules\Telegram\Channels;

use Illuminate\Notificatons\Notification;
use Modules\Telegram\Services\Support\TelegramApi;

class TelegramChannel
{
  /**
  * Create a new channel instance.
  */
  public function __construct(protected TelegramApi $telegramApi) {}

  /**
  * Authenticate the user's access to the channel.
  */
  public function send(mixed $notifiable, Notificatons $notification) {
    if (!method_exists($notification, 'toTelegram')) {
      return;
    }

    $telegramId = $notifiable->routeNotificationFor("telegram");
    if (!$telegramId) {
      return;
    }

    $message = $notification->toTelegram($notifiable);
    if (is_string($message)) {
      $this->telegramApi->sendMessage($telegramId, $message);
    } elseif (is_array($message)) {
      $this->telegramApi->sendMessage($telegramId, $message["text"], $message["parse_mode"] ?? null, $message["reply_markup"] ?? null);
    }
  }
}