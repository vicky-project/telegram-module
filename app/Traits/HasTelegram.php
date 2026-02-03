<?php
namespace Modules\Telegram\Traits;

use Modules\Telegram\Models\Telegram;

trait HasTelegram
{
	public function telegram()
	{
		return $this->hasOne(Telegram::class);
	}

	/**
	 * Generate verification code for Telegram linking
	 */
	public function generateTelegramVerificationCode(): string
	{
		$code = strtoupper(Str::random(6));
		$this->update([
			"verification_code" => $code,
			"code_expires_at" => Carbon::now()->addMinutes(10),
		]);

		return $code;
	}

	/**
	 * Link Telegram account
	 */
	public function linkTelegramAccount(
		int $chatId,
		string $username = null
	): bool {
		return $this->update([
			"telegram_id" => $chatId,
			"username" => $username,
			"verification_code" => null,
			"code_expires_at" => null,
		]);
	}
}
