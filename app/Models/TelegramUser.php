<?php
namespace Modules\Telegram\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\SocialAccount\Models\SocialAccount;
use Modules\UserManagement\Interfaces\SocialAccountInterface;


class TelegramUser extends Model implements SocialAccountInterface
{
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
}