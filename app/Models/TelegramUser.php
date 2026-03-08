<?php
namespace Modules\Telegram\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
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
}