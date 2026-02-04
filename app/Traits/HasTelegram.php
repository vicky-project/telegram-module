<?php
namespace Modules\Telegram\Traits;

use Modules\Telegram\Models\Telegram;
use Carbon\Carbon;
use Illuminate\Support\Str;

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

	/**
	 * Verify Telegram linking code
	 */
	public function verifyTelegramCode(string $code): bool
	{
		if (
			!$this->verification_code ||
			!$this->code_expires_at ||
			$this->verification_code !== $code ||
			Carbon::now()->gt($this->code_expires_at)
		) {
			return false;
		}

		return true;
	}

	/**
	 * Unlink Telegram account
	 */
	public function unlinkTelegramAccount(): bool
	{
		return $this->update([
			"telegram_id" => null,
			"username" => null,
			"verification_code" => null,
			"code_expires_at" => null,
		]);
	}

	/**
	 * Check if user has linked Telegram
	 */
	public function hasLinkedTelegram(): bool
	{
		return !is_null($this->telegram_id);
	}

	/**
	 * Get Telegram notification settings
	 */
	public function getTelegramSetting(string $key, $default = null)
	{
		$settings = $this->telegram_settings ?? [];

		if (!isset($settings[$key])) {
			return $default;
		}

		return $settings[$key] ?? $default;
	}

	public function getAllTelegramSettings()
	{
		return $this->telegram_settings ?? [];
	}

	public function setTelegramNotification(bool $active)
	{
		$this->update([
			"telegram_notifications" => $active,
		]);
	}

	/**
	 * Update Telegram settings
	 */
	public function updateTelegramSettings(array $settings): bool
	{
		$current = $this->getAllTelegramSettings();

		return $this->update([
			"telegram_settings" => array_merge($current, $settings),
		]);
	}
}
