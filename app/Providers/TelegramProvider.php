<?php
namespace Modules\Telegram\Providers;

use Modules\SocialAccount\Interfaces\SocialProvider;
use Modules\SocialAccount\Enums\Provider;
use Modules\Telegram\Models\TelegramUser;

class TelegramProvider implements SocialProvider
{
  public function getName(): string
  {
    return Provider::TELEGRAM->value;
  }

  public function getLabel(): string
  {
    return Provider::TELEGRAM->label();
  }

  public function getIcon(): string
  {
    return 'bi bi-telegram';
  }

  public function getLoginUrl(): string
  {
    // Untuk Telegram, login biasanya via Mini App, jadi kita arahkan ke route Mini App
    return route('telegram.login.index');
  }

  public function handleCallback($socialUser): array
  {}
}