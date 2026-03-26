<?php
namespace Modules\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Notifications\Notifiable;
use Modules\SocialAccount\Models\SocialAccount;
use Modules\SocialAccount\Interfaces\SocialAccountInterface;

class TelegramUser extends Model implements SocialAccountInterface
{
  use Notifiable;

  protected $fillable = [
    'telegram_id',
    'first_name',
    'last_name',
    'username',
    'language_code',
    'photo_url',
    'data'
  ];
  protected $casts = ['data' => 'array'];

  public function provider(): MorphOne
  {
    return $this->morphOne(SocialAccount::class, "providerable");
  }

  public function scopeByTelegramId($query, $telegramId) {
    return $query->where("telegram_id", $telegramId);
  }

  public function routeNotificationForTelegram() {
    return $this->telegram_id;
  }
}