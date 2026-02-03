<?php
namespace Modules\Telegram\Traits;

use Modules\Telegram\Models\Telegram;

trait HasTelegram
{
	public function telegram()
	{
		return $this->hasOne(Telegram::class);
	}
}
