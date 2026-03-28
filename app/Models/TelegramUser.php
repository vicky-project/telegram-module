<?php
namespace Modules\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Notifications\Notifiable;
use Modules\SocialAccount\Models\SocialAccount;
use Modules\SocialAccount\Interfaces\SocialAccountInterface;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Activitylog\LogOptions;

class TelegramUser extends Model implements SocialAccountInterface
{
  use Notifiable,
  LogsActivity,
  CausesActivity;

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

  /**
  * Activity Log Options
  */
  public function getActivitylogOptions(): LogOptions
  {
    return LogOptions::defaults()
    ->logOnly(['telegram_id', 'first_name', 'last_name', 'username', 'language_code'])
    ->logOnlyDirty()
    ->dontSubmitEmptyLogs()
    ->setDescriptionForEvent(fn(string $eventName) => "Telegram user {$eventName}")
    ->useLogName('telegram_users');
  }
}