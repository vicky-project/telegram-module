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

	public function isHasTelegram()
	{
		return $this->telegram;
	}

	/**
	 * Generate verification code for Telegram linking
	 */
	public function generateTelegramVerificationCode($userId): string
	{
		$code = strtoupper(Str::random(6));
		$this->telegram()->update([
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
		return $this->telegram()->update([
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
		if (!$this->isHasTelegram()) {
			return false;
		}

		if (
			!$this->telegram->verification_code ||
			!$this->telegram->code_expires_at ||
			$this->telegram->verification_code !== $code ||
			Carbon::now()->gt($this->telegram->code_expires_at)
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
		return $this->telegram()->update([
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
		return $this->isHasTelegram() && !is_null($this->telegram->telegram_id);
	}

	/**
	 * Get Telegram notification settings
	 */
	public function getTelegramSetting(string $key, $default = null)
	{
		$settings = $this->getAllTelegramSettings() ?? [];

		if (!isset($settings[$key])) {
			return $default;
		}

		return $settings[$key] ?? $default;
	}

	public function getAllTelegramSettings()
	{
		return $this->telegram->settings ?? [];
	}

	public function setTelegramNotification(bool $active)
	{
		$this->telegram()->update([
			"notifications" => $active,
		]);
	}

	/**
	 * Update Telegram settings
	 */
	public function updateTelegramSettings(array $settings): bool
	{
		$current = $this->getAllTelegramSettings();

		return $this->telegram()->update([
			"settings" => array_merge($current, $settings),
		]);
	}
}
