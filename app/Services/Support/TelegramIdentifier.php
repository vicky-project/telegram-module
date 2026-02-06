<?php
namespace Modules\Telegram\Services\Support;
use Telegram\Bot\Objects\Update;

class TelegramIdentifier
{
	/**
	 * Cek apakah chat_id adalah percakapan pribadi
	 */
	public function isPrivateChat(int $chatId): bool
	{
		return $chatId > 0;
	}

	/**
	 * Cek apakah chat_id adalah grup
	 */
	public function isGroupChat(int $chatId): bool
	{
		return $chatId < 0 && !str_starts_with((string) $chatId, "-100");
	}

	/**
	 * Cek apakah chat_id adalah supergroup/channel
	 */
	public function isSupergroupOrChannel(int $chatId): bool
	{
		return str_starts_with((string) $chatId, "-100");
	}

	/**
	 * Normalize chat_id untuk penyimpanan
	 */
	public function normalizeChatId(int $chatId): int
	{
		// Pastikan chat_id negatif untuk grup tetap negatif
		return $chatId;
	}

	/**
	 * Extract informasi dari update
	 */
	public function extractIds(Update $update): array
	{
		$data = [
			"user_id" => null,
			"chat_id" => null,
			"is_private" => false,
			"is_group" => false,
			"is_channel" => false,
		];

		if ($update->has("message")) {
			$message = $update->getMessage();
			$data["user_id"] = $message->getFrom()->getId();
			$data["chat_id"] = $message->getChat()->getId();
		} elseif ($update->has("callback_query")) {
			$callback = $update->getCallbackQuery();
			$data["user_id"] = $callback->getFrom()->getId();
			$data["chat_id"] = $callback
				->getMessage()
				->getChat()
				->getId();
		}

		// Tentukan tipe chat
		if ($data["chat_id"]) {
			$data["is_private"] = $this->isPrivateChat($data["chat_id"]);
			$data["is_group"] = $this->isGroupChat($data["chat_id"]);
			$data["is_channel"] = $this->isSupergroupOrChannel($data["chat_id"]);
		}

		return $data;
	}

	/**
	 * Generate cache key berdasarkan ID
	 */
	public function cacheKey(
		string $type,
		int $userId,
		?int $chatId = null
	): string {
		if ($chatId) {
			return "telegram:{$type}:user:{$userId}:chat:{$chatId}";
		}
		return "telegram:{$type}:user:{$userId}";
	}

	/**
	 * Validasi apakah user_id dan chat_id valid
	 */
	public function validateIds(int $userId, int $chatId): bool
	{
		// User ID harus positif
		if ($userId <= 0) {
			return false;
		}

		// Chat ID bisa positif (pribadi) atau negatif (grup/channel)
		// Tapi tidak boleh 0
		if ($chatId == 0) {
			return false;
		}

		return true;
	}
}
