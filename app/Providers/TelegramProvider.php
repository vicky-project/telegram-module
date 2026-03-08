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
    return route('social.login', Provider::TELEGRAM->value);
  }

  public function handleCallback($socialUser): array
  {
    logger()->debug("Telegram data: ", ['data' => $socialUser]);
    // $socialUser di sini bisa berupa array data dari Mini App atau dari OAuth
    // Kita asumsikan data dari Mini App sudah dalam format yang sesuai
    $telegramUser = TelegramUser::firstOrCreate(
      ['telegram_id' => $socialUser['id']],
      [
        'first_name' => $socialUser['first_name'] ?? null,
        'last_name' => $socialUser['last_name'] ?? null,
        'username' => $socialUser['username'] ?? null,
        'language_code' => $socialUser['language_code'] ?? null,
        'photo_url' => $socialUser['photo_url'] ?? null,
        'data' => $socialUser,
      ]
    );

    return [
      'providerable_id' => $telegramUser->id,
      'providerable_type' => TelegramUser::class,
      'provider_data' => [
        'telegram_id' => $telegramUser->telegram_id,
        'username' => $telegramUser->username,
        'first_name' => $telegramUser->first_name,
        'last_name' => $telegramUser->last_name,
        'avatar' => $telegramUser->photo_url,
      ],
    ];
  }
}