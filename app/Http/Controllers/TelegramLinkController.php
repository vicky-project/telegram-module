<?php
namespace Modules\Telegram\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Telegram\Services\LinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TelegramLinkController extends Controller
{
	protected $telegramLinkService;

	public function __construct(LinkService $telegramLinkService)
	{
		$this->middleware("auth:web");
		$this->telegramLinkService = $telegramLinkService;
	}

	/**
	 * Generate new linking code
	 */
	public function generateCode(Request $request)
	{
		$user = Auth::user();

		if ($user->telegram->hasLinkedTelegram()) {
			return response()->json(
				[
					"success" => false,
					"message" => "Akun Telegram sudah terhubung",
				],
				400
			);
		}

		$result = $this->telegramLinkService->generateLinkingCode($user);

		return response()->json([
			"success" => true,
			"code" => $result["code"],
			"expires_at" => $result["expires_at"]->format("H:i:s"),
			"bot_username" => $result["bot_username"],
			"instructions" => $this->getInstructions(
				$result["code"],
				$result["bot_username"]
			),
		]);
	}

	/**
	 * Unlink Telegram account
	 */
	public function unlink(Request $request)
	{
		$user = Auth::user();

		if (!$user->telegram->hasLinkedTelegram()) {
			return response()->json(
				[
					"success" => false,
					"message" => "Akun Telegram tidak terhubung",
				],
				400
			);
		}

		$user->telegram->unlinkTelegramAccount();

		return response()->json([
			"success" => true,
			"message" => "Akun Telegram berhasil diputuskan",
		]);
	}

	/**
	 * Get linking instructions
	 */
	private function getInstructions(string $code, string $botUsername): string
	{
		return "1. Buka Telegram dan cari @" .
			$botUsername .
			"
			2. Kirim perintah: /link {$code}" .
			"
			3. Tunggu konfirmasi dari bot" .
			"Kode berlaku 10 menit";
	}

	/**
	 * Update Telegram notification settings
	 */
	public function updateSettings(Request $request)
	{
		$validated = $request->validate([
			"notifications" => "boolean",
			"new_transaction" => "boolean",
			"daily_summary" => "boolean",
			"weekly_summary" => "boolean",
			"budget_warning" => "boolean",
			"budget_exceeded" => "boolean",
			"low_balance" => "boolean",
		]);

		$user = Auth::user();

		if (isset($validated["notifications"])) {
			$user->setTelegramNotification($validated["notifications"]);
		}

		$settingKeys = [
			"new_transaction",
			"daily_summary",
			"weekly_summary",
			"budget_warning",
			"budget_exceeded",
			"low_balance",
		];

		$settings = [];
		foreach ($settingKeys as $key) {
			if (isset($validated[$key])) {
				$settings[$key] = $validated[$key];
			}
		}

		$user->updateTelegramSettings($settings);

		return response()->json([
			"success" => true,
			"message" => "Pengaturan berhasil diperbarui",
		]);
	}

	/**
	 * Get linking status
	 */
	public function status()
	{
		$user = Auth::user();

		return response()->json([
			"linked" => $user->hasLinkedTelegram(),
			"chat_id" => $user->telegram_chat_id,
			"username" => $user->telegram_username,
			"notifications" => $user->telegram_notifications,
			"settings" => $user->telegram_settings,
		]);
	}
}
