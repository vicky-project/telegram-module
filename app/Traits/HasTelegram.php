<?php
namespace Modules\Telegram\Traits;

use Modules\Telegram\Models\Telegram;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait HasTelegram
{
	public function telegram()
	{
		return $this->hasOne(Telegram::class);
	}

	public function hasTelegram(): bool
	{
		if ($this->relationLoaded("telegram")) {
			return !is_null($this->telegram);
		}

		return $this->telegram()->exists();
	}

	/**
	 * Generate verification code for Telegram linking
	 */
	public function generateTelegramVerificationCode(): string
	{
		$code = strtoupper(Str::random(6));

		$telegram = $this->telegram()->firstOrCreate(
			[],
			[
				"user_id" => $this->id,
				"verification_code" => $code,
				"code_expires_at" => Carbon::now()->addMinutes(10),
			]
		);

		if ($telegram->wasRecentlyCreated === false) {
			$telegram->update([
				"verification_code" => $code,
				"code_expires_at" => Carbon::now()->addMinutes(10),
			]);
		}

		if ($this->relationLoaded("telegram")) {
			$this->load("telegram");
		}

		return $code;
	}

	/**
	 * Link Telegram account
	 */
	public function linkTelegramAccount(
		int $chatId,
		string $username = null
	): bool {
		if (!$this->hasTelegram()) {
			return false;
		}

		$telegram = $this->telegram()->first();

		return $telegram->update([
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
		if (!$this->hasTelegram()) {
			return false;
		}

		try {
			$telegram = $this->telegram()->first();

			if (
				!$telegram ||
				!$telegram->verification_code ||
				!$telegram->code_expires_at ||
				$telegram->verification_code !== $code
			) {
				return false;
			}

			if (Carbon::now()->gt(Carbon::parse($telegram->code_expires_at))) {
				return false;
			}

			return true;
		} catch (ModelNotFoundException $e) {
			return false;
		}
	}

	/**
	 * Unlink Telegram account
	 */
	public function unlinkTelegramAccount(): bool
	{
		if (!$this->hasTelegram()) {
			return true;
		}

		$success = $this->telegram()->update([
			"telegram_id" => null,
			"username" => null,
			"verification_code" => null,
			"code_expires_at" => null,
		]);

		if ($success && $this->relationLoaded("telegram")) {
			$this->load("telegram");
		}

		return $success;
	}

	/**
	 * Check if user has linked Telegram
	 */
	public function hasLinkedTelegram(): bool
	{
		if ($this->relationLoaded("telegram") && $this->telegram) {
			return !is_null($this->telegram->telegram_id);
		}

		return $this->telegram()
			->whereNotNull("telegram_id")
			->exists();
	}

	/**
	 * Get Telegram notification settings
	 */
	public function getTelegramSetting(string $key, $default = null)
	{
		$settings = $this->getAllTelegramSettings();

		return $settings[$key] ?? $default;
	}

	public function getAllTelegramSettings(): array
	{
		if (!$this->hasTelegram()) {
			return [];
		}

		if (!$this->relationLoaded("telegram")) {
			$this->load("telegram");
		}

		return $this->telegram->settings ?? [];
	}

	public function setTelegramNotification(bool $active): bool
	{
		if (!$this->hasTelegram()) {
			$this->telegram()->create([
				"user_id" => $this->id,
				"notifications" => $active,
			]);

			return true;
		}

		$success = $this->telegram()->update([
			"notifications" => $active,
		]);

		if ($success && $this->relationLoaded("telegram")) {
			$this->load("telegram");
		}

		return $success;
	}

	/**
	 * Update Telegram settings
	 */
	public function updateTelegramSettings(array $settings): bool
	{
		if (!$this->hasTelegram()) {
			return (bool) $this->telegram()->create([
				"user_id" => $this->id,
				"settings" => $settings,
			]);
		}

		$telegram = $this->telegram()->first();
		$current = $telegram->settings ?? [];
		$mergeSettings = array_merge($current, $settings);

		$success = $this->telegram()->update([
			"settings" => $mergeSettings,
		]);

		if ($success && $this->relationLoaded("telegram")) {
			$this->load("telegram");
		}

		return $success;
	}
}
