<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\LinkService;
use Modules\Telegram\Services\Support\TelegramApi;

class LinkCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected LinkService $linkService;
	protected string $appName;

	public function __construct(
		TelegramApi $telegramApi,
		LinkService $linkService
	) {
		$this->telegramApi = $telegramApi;
		$this->linkService = $linkService;
		$this->appName = config("app.name", "Financial");
	}

	public function getCommandName(): string
	{
		return "link";
	}

	public function getDescription(): string
	{
		return "Menghubungkan ke telegram.";
	}

	public function requiresLinkedUser(): bool
	{
		return false; // Tidak perlu user terhubung
	}

	public function handle(
		int $chatId,
		?string $argument,
		?string $username,
		$user = null
	): array {
		if (!$argument) {
			Log::warning("Link Code not found.", [
				"chat_id" => $chatId,
				"code" => $argument,
				"username" => $username,
			]);

			$message =
				"❌ Format salah.\n" .
				"Gunakan: /link <kode_verifikasi>\n\n" .
				"Dapatkan kode dari web app di halaman Settings → Telegram Integration.";

			$this->telegramApi->sendMessage($chatId, $message);

			return [
				"status" => "link_failed",
				"reason" => "missing_code",
			];
		}

		$user = $this->linkService->validateLinkingCode($argument);
		if (!$user) {
			$message =
				"❌ Kode tidak valid atau sudah kadaluarsa.\n" .
				"Silakan generate kode baru di web app.";

			$this->telegramApi->sendMessage($chatId, $message);

			return [
				"status" => "link_failed",
				"reason" => "invalid_code",
			];
		}

		// Check if Telegram already linked to another account
		$existingUser = $this->linkService->getUserByChatId($chatId);
		if ($existingUser && $existingUser->id !== $user->id) {
			$message =
				"❌ Akun Telegram ini sudah terhubung dengan akun lain.\n" .
				"Gunakan /unlink di akun tersebut terlebih dahulu.";

			$this->telegramApi->sendMessage($chatId, $message);

			return [
				"status" => "link_failed",
				"reason" => "already_linked_to_other",
			];
		}

		// Complete linking
		$success = $this->linkService->completeLinking($user, $chatId, $username);

		if ($success) {
			$message =
				"✅ *Akun berhasil dihubungkan!*\n\n" .
				"Halo {$user->name}!\n" .
				"Sekarang Anda bisa menambah transaksi via Telegram.\n\n" .
				"Contoh: `/add expense 50000 Makan siang #Food @Cash`\n" .
				"Gunakan /help untuk command lengkap.";

			$this->telegramApi->sendMessage($chatId, $message, "Markdown");

			return [
				"status" => "link_success",
				"user_id" => $user->id,
				"username" => $username,
			];
		}

		$this->telegramApi->sendMessage(
			$chatId,
			"❌ Gagal menghubungkan akun. Coba lagi."
		);

		return [
			"status" => "link_failed",
			"reason" => "system_error",
		];
	}
}
