<?php
namespace Modules\Telegram\Services\Handlers\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Support\TelegramApi;

class UnlinkCommand extends BaseCallbackHandler
{
	protected $telegramService;

	public function __construct(
		TelegramApi $telegramApi,
		TelegramService $telegramService
	) {
		$this->telegramService = $telegramService;
		parent::__construct($telegramApi);
	}

	public function getModuleName(): string
	{
		return "telegram";
	}

	public function getName(): string
	{
		return "System Callback Handler";
	}

	public function getScope(): string
	{
		return "system";
	}

	public function handle(array $data, array $context): array
	{
		try {
			return $this->handleCallbackWithAutoAnswer(
				$context,
				$data,
				fn($data, $context) => $this->processCallback($data, $context)
			);
		} catch (\Exception $e) {
			Log::error("Failed to handle callback of system", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return ["status" => "callback_failed", "answer" => $e->getMessage()];
		}
	}

	private function processCallback(array $data, array $context): array
	{
		$entity = $data["entity"];
		$action = $data["action"];
		$id = $data["id"] ?? null;
		$chatId = $context["chat_id"] ?? null;
		$params = $data["params"] ?? [];
		$user = $context["user"] ?? null;

		if (!$user) {
			return [
				"status" => "unauthorized",
				"answer" => "Anda perlu login terlebih dahulu",
				"show_alert" => true,
			];
		}

		if (!$id) {
			return [
				"status" => "unknown_account",
				"answer" => "Kehilangan ID akun. Ketik perintah akun kembali.",
				"show_alert" => true,
			];
		}

		if (!$entity !== "telegram") {
			return [
				"status" => "unknown_entity",
				"answer" => "Akses tidak dikenali",
				"show_alert" => true,
			];
		}

		return $this->processUnlinkCallback($action, $chatId);
	}

	private function processUnlinkCallback(string $action, int $chatId): array
	{
		switch ($action) {
			case "unlink_confirm":
				return $this->processUnlinkConfirmation($chatId);

			case "unlink_cancel":
				return $this->processUnlinkCancel();

			default:
				return [
					"status" => "unknown_action",
					"answer" => "Aksi tidak dikenali",
					"show_alert" => true,
				];
		}
	}

	private function processUnlinkConfirmation(int $chatId): array
	{
		try {
			$user = $this->telegramService->getUserByChatId($chatId);

			if (!$user) {
				Log::error("User not found", ["chat_id" => $chatId, "data" => $user]);

				return [
					"status" => "unknown_user",
					"answer" => "User not found",
					"show_alert" => true,
				];
			}

			\Log::debug("Using user: " . $user->name);
			$this->telegramService->unlink($user, $chatId);

			$message =
				"✅ *Akun berhasil diputuskan.* {$this->appName}\n\n" .
				"Anda bisa menghubungkan kembali melalui web app.\n" .
				"Terima kasih telah menggunakan bot kami! 👋";

			return [
				"status" => "unlink_success",
				"answer" => "Success unlink account",
				"edit_message" => $this->createEditMessageData($message),
			];
		} catch (\RuntimeException $e) {
			Log::error("Failed to unlink account", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return [
				"status" => "failed_unlink",
				"answer" => "Failed to unlink account",
				"show_alert" => true,
			];
		}
	}

	private function processUnlinkCancel(): array
	{
	}
}
