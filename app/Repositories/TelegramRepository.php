<?php
namespace Modules\Telegram\Repositories;

use Carbon\Carbon;
use Modules\Telegram\Models\Telegram;

class TelegramRepository
{
	protected $model;

	public function __construct(Telegram $model)
	{
		$this->model = $model;
	}

	public function firstOrCreate(array $data)
	{
		return $this->model->firstOrCreate(
			[
				"telegram_id" => $data["id"],
			],
			[
				"telegram_id" => $data["id"],
				"username" => $data["username"] ?? null,
				"first_name" => $data["first_name"] ?? null,
				"last_name" => $data["last_name"] ?? null,
				"auth_date" => Carbon::createFromTimestamp($data["auth_date"])->format(
					"Y-m-d H:i:s"
				),
			]
		);
	}
}
