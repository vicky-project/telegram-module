<?php
namespace Modules\Telegram\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramChat extends Model
{
  protected $fillable = ['chat_id',
    'type',
    'title',
    'data'];
  protected $casts = ['data' => 'array'];
}