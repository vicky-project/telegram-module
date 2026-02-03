<?php
namespace Modules\Telegram\Services\Handlers;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\LinkService;
use Modules\Telegram\Services\CommandService;
use Modules\Telegram\Services\Support\TelegramApi;

class CommandHandler
{
	protected $appName;
	protected LinkService $linkService;
	protected CommandService $commandService;
	protected TelegramApi $telegramApi;

	public function __construct(
		LinkService $linkService,
		CommandService $commandService,
		TelegramApi $telegramApi
	) {
		$this->linkService = $linkService;
		$this->commandService = $commandService;
		$this->telegramApi = $telegramApi;
		$this->appName = config("app.name", "Financial");
	}

	/**
	 * Handle command
	 */
	public function handleCommand(
		int $chatId,
		string $text,
		?string $username
	): array {
		$parts = explode(" ", $text, 2);
		$command = strtolower(trim($parts[0], "/"));
		$argument = $parts[1] ?? null;

		Log::info("Processing command", [
			"chat_id" => $chatId,
			"command" => $command,
			"argument" => $argument,
		]);

		$methodName = "handle" . ucfirst($command);
		if (method_exists($this, $methodName)) {
			return $this->$methodName($chatId, $argument, $username);
		}

		return $this->handleUnknownCommand($chatId);
	}

	/**
	 * Handle /start command
	 */
	private function handleStart(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$user = $this->linkService->getUserByChatId($chatId);

		if ($user) {
			$message = $this->getWelcomeMessageForLinkedUser($user);
		} else {
			$message = $this->getWelcomeMessageForNewUser();
		}

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return [
			"status" => "start",
			"chat_id" => $chatId,
			"user_linked" => (bool) $user,
		];
	}

	/**
	 * Handle /link command
	 */
	private function handleLink(
		int $chatId,
		?string $code,
		?string $username
	): array {
		if (!$code) {
			Log::warning("Link Code not found.", [
				"chat_id" => $chatId,
				"code" => $code,
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

		$user = $this->linkService->validateLinkingCode($code);
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

	/**
	 * Handle /unlink command
	 */
	private function handleUnlink(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->telegramApi->sendMessage($chatId, "❌ Akun tidak terhubung.");

			return [
				"status" => "unlink_failed",
				"reason" => "not_linked",
			];
		}

		$user->unlinkTelegramAccount();

		$message =
			"✅ *Akun berhasil diputuskan.*\n\n" .
			"Anda bisa menghubungkan kembali melalui web app.\n" .
			"Terima kasih telah menggunakan bot kami! 👋";

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return [
			"status" => "unlink_success",
			"user_id" => $user->id,
		];
	}

	/**
	 * Handle /add command
	 */
	private function handleAdd(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$message =
				"❌ Anda belum terhubung.\n" .
				"Gunakan /start untuk instruksi linking.";

			$this->telegramApi->sendMessage($chatId, $message);

			return [
				"status" => "add_failed",
				"reason" => "not_linked",
			];
		}

		$fullText = "/add " . ($argument ?? "");
		$result = $this->commandService->processAddCommand($user, $fullText);

		$this->telegramApi->sendMessage(
			$chatId,
			$result["message"],
			$result["parse_mode"] ?? null
		);

		return [
			"status" => "add_processed",
			"success" => $result["success"] ?? false,
			"transaction_id" => $result["transaction_id"] ?? null,
		];
	}

	/**
	 * Handle /help command
	 */
	private function handleHelp(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$message = $this->getHelpMessage();
		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return ["status" => "help_sent"];
	}

	/**
	 * Handle /accounts command
	 */
	private function handleAccounts(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->telegramApi->sendMessage($chatId, "❌ Anda belum terhubung.");

			return [
				"status" => "accounts_failed",
				"reason" => "not_linked",
			];
		}

		$accounts = $user
			->accounts()
			->active()
			->get();

		if ($accounts->isEmpty()) {
			$this->telegramApi->sendMessage($chatId, "📭 Anda belum memiliki akun.");

			return ["status" => "no_accounts"];
		}

		$message = "🏦 *Daftar Akun Anda:*\n\n";
		foreach ($accounts as $account) {
			$balance = number_format($account->balance->getAmount()->toInt());
			$message .= "• @{$account->name} - Rp {$balance}\n";
		}
		$message .= "\nGunakan `@nama_akun` saat menambah transaksi.";

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return [
			"status" => "accounts_listed",
			"count" => $accounts->count(),
		];
	}

	/**
	 * Handle /categories command
	 */
	private function handleCategories(
		int $chatId,
		?string $argument,
		?string $username
	): array {
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->telegramApi->sendMessage($chatId, "❌ Anda belum terhubung.");

			return [
				"status" => "categories_failed",
				"reason" => "not_linked",
			];
		}

		$categories = $user
			->categories()
			->active()
			->get();

		if ($categories->isEmpty()) {
			$this->telegramApi->sendMessage(
				$chatId,
				"📭 Anda belum memiliki kategori."
			);

			return ["status" => "no_categories"];
		}

		$message = "📂 *Daftar Kategori Anda:*\n\n";
		foreach ($categories as $category) {
			$type = $category->type->value === "income" ? "📈" : "📉";
			$message .= "• #{$category->name} {$type}\n";
		}
		$message .= "\nGunakan `#nama_kategori` saat menambah transaksi.";

		$this->telegramApi->sendMessage($chatId, $message, "Markdown");

		return [
			"status" => "categories_listed",
			"count" => $categories->count(),
		];
	}

	/**
	 * Handle unknown command
	 */
	private function handleUnknownCommand(int $chatId): array
	{
		$message = "❌ Command tidak dikenali. Gunakan /help untuk bantuan.";
		$this->telegramApi->sendMessage($chatId, $message);

		return ["status" => "unknown_command"];
	}

	/**
	 * Get welcome message for linked user
	 */
	private function getWelcomeMessageForLinkedUser($user): string
	{
		return "👋 Halo {$user->name}!\n" .
			"Akun Anda sudah terhubung.\n\n" .
			"📋 *Command yang tersedia:*\n" .
			"• /add - Tambah transaksi baru\n" .
			"• /accounts - Lihat daftar akun\n" .
			"• /categories - Lihat kategori\n" .
			"• /unlink - Putuskan koneksi\n" .
			"• /help - Bantuan lengkap\n\n" .
			"Gunakan /add untuk menambah transaksi pertama Anda!";
	}

	/**
	 * Get welcome message for new user
	 */
	private function getWelcomeMessageForNewUser(): string
	{
		return "👋 Selamat datang di {$this->appName} Bot!\n\n" .
			"Untuk menghubungkan akun Anda:\n" .
			"1. Login ke aplikasi web\n" .
			"2. Buka Menu Settings → Telegram Integration\n" .
			"3. Generate kode verifikasi\n" .
			"4. Kirim ke bot ini: /link <kode>\n\n" .
			"Gunakan /help untuk informasi lebih lanjut.";
	}

	/**
	 * Get help message
	 */
	private function getHelpMessage(): string
	{
		return "📚 *Bantuan {$this->appName} Bot*\n\n" .
			"🔗 *Linking Account:*\n" .
			"• /start - Memulai bot\n" .
			"• /link <kode> - Hubungkan akun\n" .
			"• /unlink - Putuskan koneksi\n\n" .
			"💰 *Transaksi:*\n" .
			"• /add <tipe> <jumlah> <deskripsi> [#kategori] [@akun]\n" .
			"  Contoh: `/add expense 50000 Makan siang #Food @Cash`\n\n" .
			"📊 *Informasi:*\n" .
			"• /accounts - Lihat daftar akun\n" .
			"• /categories - Lihat kategori\n\n" .
			"⚙️ *Format:*\n" .
			"• Tipe: income, expense, transfer\n" .
			"• Jumlah: angka tanpa titik (50000)\n" .
			"• #kategori: opsional (default: Umum)\n" .
			"• @akun: opsional (default: akun utama)\n\n" .
			"💡 *Tips:* Gunakan tanpa spasi untuk nama multi-kata\n" .
			"Contoh: `#MakanSiang` atau `@BankBCA`";
	}
}
