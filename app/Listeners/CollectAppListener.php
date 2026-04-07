<?php

namespace Modules\Telegram\Listeners;

use Modules\Telegram\Events\RegisterAppEvent;

class CollectAppListener
{
  private static array $apps = [];

  public function handle(RegisterAppEvent $event): void
  {
    self::$apps[] = $event->app;
  }

  public static function getApps(): array
  {
    return self::$apps;
  }

  public static function clearApps(): void
  {
    self::$apps = [];
  }
}