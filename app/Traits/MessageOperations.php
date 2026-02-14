<?php
namespace Modules\Telegram\Traits;

use Telegram\Bot\Objects\Message;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Support\TelegramMarkdownHelper;
use Modules\Telegram\Services\Support\CacheReplyStateManager;

trait MessageOperations
{
	protected const MAX_ALERT_TEXT_LENGTH = 200;
	protected const MAX_CALLBACK_TEXT_LENGTH = 200;
	protected const MAX_EDIT_MESSAGE_LENGTH = 4096;

	/**
	 * Handle command with message operations support
	 */
	protected function handleCommandWithOperations(
		int $chatId,
		string $text,
		?string $username = null,
		array $params = [],
		callable $handler
	): array {
		try {
			// Execute the handler
			$result = $handler($chatId, $text, $username, $params);

			// Handle message operations if present
			$this->handleMessageOperations($chatId, null, $result);

			return $result;
		} catch (\Exception $e) {
			Log::error("Command handling failed", [
				"chat_id" => $chatId,
				"error" => $e->getMessage(),
				"text" => $text,
			]);

			// Send error message
			$errorMessage = $this->getErrorAnswer($e->getMessage());
			$this->sendMessage($chatId, $errorMessage, "Markdown");

			return [
				"status" => "error",
				"message" => $e->getMessage(),
				"error" => $e->getMessage(),
			];
		}
	}

	/**
	 * Handle message operations (edit, delete, send new) with escape support
	 */
	protected function handleMessageOperations(
		?int $chatId,
		?int $messageId,
		array $result
	): void {
		if (!$chatId) {
			return; // Can't perform message operations without chat_id
		}

		$validParseModes = ["Markdown", "MarkdownV2", "HTML", null];

		// Edit existing message
		if (isset($result["edit_message"]) && $messageId) {
			$editData = $result["edit_message"];
			$text = $editData["text"] ?? "";
			$replyMarkup = $editData["reply_markup"] ?? null;
			$parseMode = $editData["parse_mode"] ?? "Markdown";

			if (!$this->isValidEditReplyMarkup($replyMarkup)) {
				throw new \InvalidArgumentException(
					"editMessageText only support for inline_keyboard"
				);
			}

			if (!in_array($parseMode, $validParseModes)) {
				Log::warning("Invalid parse_mode specified, using Markdown", [
					"parse_mode" => $parseMode,
					"valid_modes" => $validParseModes,
				]);
				$parseMode = "Markdown";
			}

			$editData["parse_mode"] = $parseMode;

			$this->editMessage($chatId, $messageId, $text, $replyMarkup, $editData);

			if (
				isset($replyMarkup["force_reply"]) &&
				$replyMarkup["force_reply"] === true
			) {
				$handlerIdentifier = $result["reply_handler"]["identifier"] ?? null;
				$context = $result["reply_handler"]["context"] ?? [];

				if ($handlerIdentifier) {
					// simpan state reply
					CacheReplyStateManager::expectReply(
						$chatId,
						$messageId,
						$handlerIdentifier,
						$context
					);
				} else {
					Log::warning(
						"Force reply in edit message needs a key for reply_handler.identifier",
						["reply_handler" => $result["reply_handler"]]
					);

					// Do something or skip
				}
			}
		}

		// Delete existing message
		if (isset($result["delete_message"]) && $messageId) {
			$this->deleteMessage($chatId, $messageId);
		}

		// Send new message
		if (isset($result["send_message"])) {
			$sendData = $result["send_message"];
			$text = $sendData["text"] ?? "No Text";
			$replyMarkup = $sendData["reply_markup"] ?? null;
			$parseMode = $sendData["parse_mode"] ?? "Markdown";

			if (!in_array($parseMode, $validParseModes)) {
				$parseMode = "Markdown";
			}

			if (
				isset($replyMarkup["force_reply"]) &&
				$replyMarkup["force_reply"] === true
			) {
				$handlerIdentifier = $result["reply_handler"]["identifier"] ?? null;
				$context = $result["reply_handler"]["context"] ?? [];

				if ($handlerIdentifier) {
					// simpan state reply
					$this->expectReply($chatId, $handlerIdentifier, $context, $text);
				} else {
					Log::warning(
						"Force reply in edit message needs a key for reply_handler.identifier",
						["reply_handler" => $result["reply_handler"]]
					);

					// Do something or skip
				}
			} else {
				$this->sendMessage($chatId, $text, $replyMarkup, $parseMode);
			}
		}

		// Send message with inline keyboard
		if (isset($result["send_message_with_keyboard"])) {
			$sendData = $result["send_message_with_keyboard"];
			$text = $sendData["text"] ?? "";
			$inlineKeyboard = $sendData["inline_keyboard"] ?? null;
			$parseMode = $sendData["parse_mode"] ?? "Markdown";

			if ($inlineKeyboard) {
				$replyMarkup = ["inline_keyboard" => $inlineKeyboard];
			} else {
				$replyMarkup = null;
			}

			$this->sendMessage($chatId, $text, $replyMarkup, $parseMode);
		}
	}

	protected function isValidEditReplyMarkup(?array $replyMarkup): bool
	{
		if ($replyMarkup === null) {
			return true;
		}

		$keys = array_keys($replyMarkup);
		return count($keys) === 1 && $keys[0] === "inline_keyboard";
	}

	/**
	 * Edit message with safety checks and auto-escaping
	 */
	protected function editMessage(
		int $chatId,
		int $messageId,
		string $text,
		?array $replyMarkup = null,
		array $options = []
	): bool {
		try {
			$options = array_merge(
				[
					"parse_mode" => "Markdown",
					"disable_web_page_preview" => true,
					"auto_truncate" => true,
					"auto_escape" => true,
				],
				$options
			);

			// Check message length
			if (mb_strlen($text) > self::MAX_EDIT_MESSAGE_LENGTH) {
				if ($options["auto_truncate"]) {
					$text = $this->truncateText($text, self::MAX_EDIT_MESSAGE_LENGTH);
					Log::warning("Edit message text truncated", [
						"chat_id" => $chatId,
						"message_id" => $messageId,
						"max_length" => self::MAX_EDIT_MESSAGE_LENGTH,
					]);
				} else {
					throw new \Exception(
						"Message text exceeds maximum length of " .
							self::MAX_EDIT_MESSAGE_LENGTH .
							" characters"
					);
				}
			}

			// Auto-escape text if needed
			if ($options["auto_escape"]) {
				$text = $this->escapeText($text, $options["parse_mode"]);
			}

			return $this->telegramApi->editMessageText(
				$chatId,
				$messageId,
				$text,
				$replyMarkup,
				$options["parse_mode"]
			);
		} catch (\Exception $e) {
			Log::error("Failed to edit message", [
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	/**
	 * Delete message
	 */
	protected function deleteMessage(int $chatId, int $messageId): bool
	{
		try {
			return $this->telegramApi->deleteMessage($chatId, $messageId);
		} catch (\Exception $e) {
			Log::error("Failed to delete message", [
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"error" => $e->getMessage(),
			]);
			return false;
		}
	}

	/**
	 * Send message with safe parsing
	 */
	protected function sendMessage(
		int $chatId,
		string $text,
		?array $replyMarkup = null,
		string $parseMode = "Markdown",
		array $options = [],
		?bool $withResponse = false
	): bool|Message {
		try {
			// Auto-escape text if needed
			if ($options["auto_escape"] ?? true) {
				$text = $this->escapeText($text, $parseMode);
			}

			return $this->telegramApi->sendMessage(
				$chatId,
				$text,
				$parseMode,
				$replyMarkup,
				$options,
				$withResponse
			);
		} catch (\Exception $e) {
			Log::error("Failed to send message", [
				"chat_id" => $chatId,
				"error" => $e->getMessage(),
				"parse_mode" => $parseMode,
			]);
			return false;
		}
	}

	protected function answerCallbackQuery(
		int $callbackQueryId,
		string $text,
		?bool $showAlert = null
	): bool {
		return $this->telegramApi->answerCallbackQuery(
			$callbackQueryId,
			$text,
			$showAlert ?? strlen($text) > 100
		);
	}

	/**
	 * Truncate text to specified length with ellipsis
	 */
	protected function truncateText(string $text, int $maxLength): string
	{
		if (mb_strlen($text) <= $maxLength) {
			return $text;
		}

		$truncated = mb_substr($text, 0, $maxLength - 3);
		$lastSpace = mb_strrpos($truncated, " ");

		if ($lastSpace !== false && $lastSpace > $maxLength - 10) {
			$truncated = mb_substr($truncated, 0, $lastSpace);
		}

		return $truncated . "...";
	}

	/**
	 * Get error answer for failed operations
	 */
	protected function getErrorAnswer(string $error = ""): string
	{
		$base = "Terjadi kesalahan saat memproses permintaan.";

		if (!empty($error)) {
			if (mb_strlen($error) > 50) {
				$error = mb_substr($error, 0, 47) . "...";
			}
			return $base . "\n" . $error;
		}

		return $base;
	}

	/**
	 * Create edit message data structure with safe parsing
	 */
	protected function createEditMessageData(
		string $text,
		?array $replyMarkup = null,
		string $parseMode = "Markdown",
		bool $autoEscape = true,
		?array $options = []
	): array {
		return [
			"text" => $text,
			"reply_markup" => $replyMarkup,
			"parse_mode" => $parseMode,
			"auto_escape" => $autoEscape,
		];
	}

	/**
	 * Create send message data structure with safe parsing
	 */
	protected function createSendMessageData(
		string $text,
		?array $replyMarkup = null,
		string $parseMode = "Markdown",
		bool $autoEscape = true
	): array {
		if ($autoEscape) {
			$text = $this->escapeText($text, $parseMode);
		}

		return [
			"text" => $text,
			"reply_markup" => $replyMarkup,
			"parse_mode" => $parseMode,
			"auto_escape" => $autoEscape,
		];
	}

	/**
	 * Create send message with keyboard data structure
	 */
	protected function createSendMessageWithKeyboardData(
		string $text,
		?array $inlineKeyboard = null,
		string $parseMode = "Markdown",
		bool $autoEscape = true
	): array {
		if ($autoEscape) {
			$text = $this->escapeText($text, $parseMode);
		}

		return [
			"text" => $text,
			"inline_keyboard" => $inlineKeyboard,
			"parse_mode" => $parseMode,
			"auto_escape" => $autoEscape,
		];
	}

	/**
	 * Helper method to escape text based on parse mode
	 */
	protected function escapeText(
		string $text,
		string $parseMode = "Markdown"
	): string {
		return TelegramMarkdownHelper::safeText($text, $parseMode);
	}

	/**
	 * Helper method to send a simple text response
	 */
	protected function sendSimpleResponse(
		int $chatId,
		string $text,
		string $parseMode = "Markdown",
		array $options = []
	): bool {
		return $this->sendMessage($chatId, $text, null, $parseMode, $options);
	}

	/**
	 * Helper method to send response with inline keyboard
	 */
	protected function sendResponseWithKeyboard(
		int $chatId,
		string $text,
		array $inlineKeyboard,
		string $parseMode = "Markdown",
		array $options = []
	): bool {
		$replyMarkup = ["inline_keyboard" => $inlineKeyboard];
		return $this->sendMessage(
			$chatId,
			$text,
			$replyMarkup,
			$parseMode,
			$options
		);
	}

	public function sendMessageWithResponse(
		int $chatId,
		string $text,
		?string $parseMode = "Markdown",
		?array $replyMarkup = null,
		array $options = []
	): ?Message {
		try {
			return $this->sendMessage(
				$chatId,
				$text,
				$replyMarkup,
				$parseMode,
				$options,
				true
			);
		} catch (\Exception $e) {
			Log::error("Failed to get raw body response", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return null;
		}
	}

	/**
	 * Helper untuk mengirim pesan dan menyimpan state reply (opsional, bisa dipanggil dari command/callback)
	 */
	protected function expectReply(
		int $chatId,
		string $handlerIdentifier,
		array $context = [],
		string $text = "Silakan masukkan input:",
		?int $messageId = null,
		?array $replyMarkup = null
	): array {
		$replyMarkup = $replyMarkup ?? ["force_reply" => true];
		if (!$messageId) {
			$response = $this->sendMessageWithResponse(
				$chatId,
				$text,
				"Markdown",
				$replyMarkup
			);
			$messageId = $response->getMessageId() ?? null;
			Log::info("Getting message_id with: " . $messageId);
		}

		if ($messageId) {
			CacheReplyStateManager::expectReply(
				$chatId,
				$messageId,
				$handlerIdentifier,
				$context
			);
		}

		Log::info("Saving state", [
			"chat_id" => $chatId,
			"message_id" => $messageId,
			"handling_identifier" => $handlerIdentifier,
		]);

		return [
			"status" => "awaiting_reply",
			"chat_id" => $chatId,
			"message_id" => $messageId,
			"handler" => $handlerIdentifier,
		];
	}
}
