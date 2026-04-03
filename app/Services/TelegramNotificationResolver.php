<?php
namespace Modules\Telegram\Services;

use Modules\SocialAccounts\Enums\Provider;
use Exception;

class TelegramNotificationResolver
{
  protected $notifiable;

  public function __construct($notifiable = null) {
    $this->notifiable = $notifiable;
  }

  public function setNotifiable($notifiable) {
    $this->notifiable = $notifiable;
    return $this;
  }

  public function getTelegramId() {
    $notifiable = $this->notifiable;

    try {
      if (!$notifiable || empty($notifiable)) {
        throw new \Exception("You must set notifiable first.");
      }

      // Check method exist in model
      if (method_exists($notifiable, "routeNotificationFor")) {
        $id = $notifiable->routeNotificationFor("telegram");
        if (!empty($id)) {
          return $id;
        }
      }

      // Check model has property direct
      if (isset($notifiable->telegram_id) && !empty($notifiable->telegram_id)) {
        return $notifiable->telegram_id;
      }

      // Check model has relation to telegram
      if (method_exists($notifiable, "telegram")) {
        $telegram = $notifiable->telegram;
        if ($telegram && isset($telegram->telegram_id)) {
          return $telegram->telegram_id;
        }
      }

      // Check if model has relation to model SocialAccount
      if (method_exists($notifiable, "socialAccounts") || $notifiable->socialAccounts) {
        $telegram = $notifiable->socialAccounts()->byProvider(Provider::TELEGRAM)->first();
        if ($telegram && $telegram->telegram_id) {
          return $telegram->telegram_id;
        }
      }

      \Log::warning("Not found any telegram id.", [
        "notifiable" => $notifiable
      ]);
      return null;
    } catch(Exception $e) {
      throw $e;
    }
  }
}