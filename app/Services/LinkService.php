<?php
namespace Modules\Telegram\Services;

use App\Models\User;
use Carbon\Carbon;
use Modules\Telegram\Models\Telegram;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LinkService
{
	/**
	 * Generate and store linking code
	 */
	public function generateLinkingCode(User $user): array
	{
		$code = $user->telegram()->generateTelegramVerificationCode();

		// Store in cache for quick validation
		Cache::put(
			"telegram_link:{$code}",
			[
				"user_id" => $user->id,
				"email" => $user->email,
				"name" => $user->name,
				"expires_at" => Carbon::now()->addMinutes(10),
			],
			600
		); // 10 minutes

		return [
			"code" => $code,
			"expires_at" => Carbon::parse(
				$user->telegram()->fresh()->telegram_code_expires_at
			),
			"bot_username" => config("telegram.username", "your_bot_username"),
		];
	}

	/**
	 * Validate linking code
	 */
	public function validateLinkingCode(string $code): ?User
	{
		$cached = Cache::get("telegram_link:{$code}");

		if (!$cached) {
			return null;
		}

		$user = User::find($cached["user_id"]);

		if (!$user || !$user->verifyTelegramCode($code)) {
			Cache::forget("telegram_link:{$code}");
			return null;
		}

		return $user;
	}

	/**
	 * Complete linking process
	 */
	public function completeLinking(
		User $user,
		int $chatId,
		string $username = null
	): bool {
		try {
			$linked = $user->telegram()->linkTelegramAccount($chatId, $username);

			if ($linked) {
				$code = $user->telegram->verification_code;
				// Clear cache
				Cache::forget("telegram_link:{$code}");

				// Log the linking
				Log::info("Telegram account linked", [
					"user_id" => $user->id,
					"chat_id" => $chatId,
					"username" => $username,
				]);
			}

			return $linked;
		} catch (\Exception $e) {
			Log::error("Failed to link Telegram account", [
				"user_id" => $user->id,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	/**
	 * Get user by Telegram chat ID
	 */
	public function getUserByChatId(int $chatId): ?User
	{
		$telegram = Telegram::where("telegram_id", $chatId)->first();

		return User::find($telegram->user_id);
	}
}
