<?php

namespace Modules\Telegram\Services;

use Modules\Telegram\Models\Telegram;
use Modules\Telegram\Services\TelegramService;
use Modules\UserManagement\Models\SocialAccount;
use Modules\UserManagement\Enums\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class TelegramAuthService
{
	public function __construct(protected TelegramService $telegramService)
	{
	}

	protected function verifyTelegramData(
		string $initData,
		string $botToken,
	): bool {
		$params = [];
		parse_str($initData, $params);

		$hash = $params["hash"] ?? "";
		unset($params["hash"]);

		ksort($params);

		$dataCheckString = urldecode(http_build_query($params, "", "\n"));
		$secretKey = hash_hmac("sha256", "WebAppData", $botToken, true);
		$computedHash = hash_hmac("sha256", $dataCheckString, $secretKey);

		return hash_equals($computedHash, $hash);
	}

	/**
	 * Autentikasi berdasarkan initData.
	 * Jika $createUser = false, tidak akan membuat user baru jika tidak ada relasi.
	 */
	public function authenticate(
		string $initData,
		string $botToken,
		bool $createUser = false,
	): ?User {
		if (!$this->verifyTelegramData($initData, $botToken)) {
			Log::warning("Telegram init data verification failed");
			return null;
		}

		parse_str($initData, $params);
		$telegramUser = json_decode($params["user"], true);

		// Cek auth_date (24 jam)
		$authDate = $params["auth_date"] ?? 0;
		if (time() - $authDate > 86400) {
			Log::warning("Telegram init data expired");
			return null;
		}

		$user = $this->telegramService->getUserByChatId($telegramUser["id"]);

		if ($user) {
			return $user;
		}

		// Tidak ada relasi
		if (!$createUser) {
			return null; // Jangan buat user baru
		}

		// Buat user baru (hanya jika diizinkan)
		$user = User::create([
			"name" => trim(
				($telegramUser["first_name"] ?? "") .
					" " .
					($telegramUser["last_name"] ?? ""),
			),
			"email" =>
				strtolower($telegramUser["username"] ?? $telegramUser["first_name"]) .
				"@telegram.com",
			"password" => null,
		]);

		$socialAccount = new SocialAccount([
			"user_id" => $user->id,
			"provider" => Provider::TELEGRAM,
			"last_used_at" => now(),
			"provider_data" => $params,
		]);
		$telegram->provider()->save($socialAccount);

		return $user;
	}

	/**
	 * Menghubungkan akun Telegram yang sudah diverifikasi ke user yang sudah ada.
	 */
	public function linkTelegramToUser(string $initData, User $user): bool
	{
		if (!$this->verifyTelegramData($initData, config("telegram.bot.token"))) {
			return false;
		}

		parse_str($initData, $params);
		$telegramUser = json_decode($params["user"], true);

		$telegram = Telegram::firstOrCreate(
			["telegram_id" => $telegramUser["id"]],
			[
				"username" => $telegramUser["username"] ?? null,
				"first_name" => $telegramUser["first_name"] ?? "",
				"last_name" => $telegramUser["last_name"] ?? null,
				"auth_date" => $params["auth_date"] ?? time(),
			],
		);

		// Cek apakah sudah terhubung ke user lain
		if ($telegram->provider) {
			if ($telegram->provider->user_id !== $user->id) {
				Log::warning("Telegram account already linked to another user", [
					"telegram_id" => $telegramUser["id"],
				]);
				return false;
			}
			return true;
		}

		// Buat social account
		$socialAccount = new SocialAccount([
			"user_id" => $user->id,
			"provider" => Provider::TELEGRAM,
			"last_used_at" => now(),
			"provider_data" => $params,
		]);
		$telegram->provider()->save($socialAccount);

		return true;
	}
}
