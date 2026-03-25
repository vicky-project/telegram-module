<?php
namespace Modules\Telegram\Constants;

class Permission
{
  const VIEW_TELEGRAM_USERS = "view.telegram.users";

  public static function all():array {
    return [
      self::VIEW_TELEGRAM_USERS => 'View telegram users'
    ];
  }
}