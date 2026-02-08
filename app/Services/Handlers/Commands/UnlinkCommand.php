<?php
namespace Modules\Telegram\Services\Handlers\Commands;

use Modules\Telegram\Interfaces\TelegramCommandInterface;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\InlineKeyboardBuilder;
use Modules\Telegram\Services\Support\TelegramApi;

class UnlinkCommand implements TelegramCommandInterface
{
	protected TelegramApi $telegramApi;
	protected TelegramService $telegramService;
	protected string $appName;

	public function __construct(
		TelegramApi $telegramApi,
		TelegramService $telegramService
	) {
		$this->telegramApi = $telegramApi;
		$this->telegramService = $telegramService;
		$this->appName = config("app.name", "Financial");
	}

	public function getName(): string
	{
		return "unlink";
	}

	public function getDescription(): string
	{
		return "Memutuskan akun dari telegram.";
	}

	public function handle(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = []
	): array {
		try {
			$user = $params["user"] ?? null;

			if (!$user) {
				$user = $this->telegramService->getUserByChatId($chatId);
			}

			if (!$user) {
				$message =
					"❌ *Akun Tidak Ditemukan*\n\n" .
					"Anda belum menghubungkan akun dengan Telegram.\n" .
					"Gunakan /start untuk memulai.";

				$this->telegramApi->sendMessage($chatId, $message, "MarkdownV2");

				return ["status" => "not_linked", "chat_id" => $chatId];
			}

			return $this->sendConfirmationMessage($chatId, $user);
		} catch (\RuntimeException $e) {
			\Log::error("Failed to disconnecting to telegram.", [
				"chat_id" => $chatId,
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			$message =
				"*Error* {$this->appName}" .
				"Terjadi kesalahan saat memutuskan koneksi ke bot telegram.\nSilakan coba lagi atau hubungi administrator.";

			$this->telegramApi->sendMessage($chatId, $message, "Markdown");

			return [
				"status" => "unlink_failed",
				"chat_id" => $chatId,
				"message" => $e->getMessage(),
			];
		}
	}

	private function sendConfirmationMessage(int $chatId, $user)
	{
		$username = $user->username ?? ($user->name ?? ($user->email ?? "User"));

		$message =
			"⚠️ *Konfirmasi Pemutusan Akun*\n\n" .
			"Anda akan memutuskan akun:\n" .
			"👤 *{$username}* dari bot {$this->appName}\n\n" .
			"Setelah diputuskan, Anda tidak akan:\n" .
			"• Menerima notifikasi melalui Telegram\n" .
			"• Dapat menggunakan bot untuk transaksi\n" .
			"• Dapat mengakses data keuangan via bot\n\n" .
			"Apakah Anda yakin ingin melanjutkan?";

		$keyboard = app(InlineKeyboardBuilder::class);
		$keyboard->setScope("system");
		$keyboard->setModule("telegram");
		$keyboard->setEntity("telegram");
		$keyboard->confirmation("unlink", $chatId);

		$this->telegramApi->sendMessage($chatId, $message, "MarkdownV2", [
			"inline_keyboard" => $keyboard,
		]);

		return [
			"status" => "unlink_success",
			"user_id" => $user->id,
			"chat_id" => $chatId,
		];
	}
}
