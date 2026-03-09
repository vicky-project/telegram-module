<?php
namespace Modules\Telegram\Repositories;

use Carbon\Carbon;
use Modules\Telegram\Models\TelegramUser;

class TelegramRepository
{
  protected $model;

  public function __construct(TelegramUser $model) {
    $this->model = $model;
  }

  public function firstOrCreate(array $data) {
    return $this->model->firstOrCreate(
      [
        "telegram_id" => $data["id"],
      ],
      [
        "telegram_id" => $data["id"],
        "username" => $data["username"] ?? null,
        "first_name" => $data["first_name"] ?? null,
        "last_name" => $data["last_name"] ?? null,
        "photo_url" => $data["photo_url"] ?? null,
        "data" => $data
      ]
    );
  }

  public function getByChatId(int $chatId) {
    return $this->model->byTelegramId($chatId)->first();
  }
}