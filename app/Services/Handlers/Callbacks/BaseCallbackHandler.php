<?php
namespace Modules\Telegram\Services\Handlers\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramCallbackHandlerInterface;
use Modules\Telegram\Services\Support\GlobalCallbackBuilder;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Services\Support\TelegramMarkdownHelper;

abstract class BaseCallbackHandler implements TelegramCallbackHandlerInterface
{
	protected const MAX_CALLBACK_TEXT_LENGTH = 200;
	protected const MAX_ALERT_TEXT_LENGTH = 200;
	protected const MAX_EDIT_MESSAGE_LENGTH = 4096;

	protected TelegramApi $telegramApi;

	public function __construct(TelegramApi $telegramApi)
	{
		$this->telegramApi = $telegramApi;
	}

	/**
	 * Get module name (harus diimplementasikan oleh child class)
	 */
	abstract public function getModuleName(): string;

	/**
	 * Get scope (default 'global')
	 */
	public function getScope(): string
	{
		return "global";
	}

	/**
	 * Get pattern untuk module ini
	 */
	public function getPattern(): string
	{
		return "{$this->getScope()}:{$this->getModuleName()}:*";
	}

	/**
	 * Parse data berdasarkan pattern module
	 */
	protected function parseModuleData(array $data, array $context): array
	{
		$parsed = [
			"module" => $this->getModuleName(),
			"entity" => $data["entity"] ?? null,
			"action" => $data["action"] ?? null,
			"id" => $data["id"] ?? null,
			"params" => $data["params"] ?? [],
			"context" => $context,
		];

		return $parsed;
	}

	/**
	 * Build callback data untuk module ini
	 */
	protected function buildModuleCallback(
		string $entity,
		string $action,
		$id = null,
		array $params = []
	): string {
		return GlobalCallbackBuilder::build(
			$this->getScope(),
			$this->getModuleName(),
			$entity,
			$action,
			$id,
			$params
		);
	}

	/**
	 * Handle callback query answer with length validation
	 *
	 * @param string $callbackId
	 * @param string $text
	 * @param bool $showAlert
	 * @param array $options Additional options
	 * @return bool Success status
	 */
	protected function answerCallbackQuery(
		string $callbackId,
		string $text,
		bool $showAlert = false,
		array $options = []
	): bool {
		try {
			// Default options
			$options = array_merge(
				[
					"auto_truncate" => true,
					"fallback_message" => "OK",
					"send_as_message" => false,
					"message_chat_id" => null,
					"message_options" => [],
				],
				$options
			);

			// Determine max length based on alert type
			$maxLength = $showAlert
				? self::MAX_ALERT_TEXT_LENGTH
				: self::MAX_CALLBACK_TEXT_LENGTH;

			// Check if text is too long
			if (mb_strlen($text) > $maxLength) {
				if ($options["auto_truncate"]) {
					// Truncate text with ellipsis
					$text = $this->truncateText($text, $maxLength);
					Log::warning("Callback query text truncated", [
						"callback_id" => $callbackId,
						"original_length" => mb_strlen($text),
						"truncated_length" => mb_strlen($text),
						"max_length" => $maxLength,
						"show_alert" => $showAlert,
					]);
				} elseif ($options["send_as_message"] && $options["message_chat_id"]) {
					// Send as regular message instead
					$this->telegramApi->sendMessage(
						$options["message_chat_id"],
						$text,
						"MarkdownV2",
						$options["message_options"]
					);

					// Answer callback query with fallback message
					$text = $options["fallback_message"];
					$showAlert = false;
				} else {
					// Use fallback message
					$text = $options["fallback_message"];
				}
			}

			// Answer the callback query
			$this->telegramApi->answerCallbackQuery($callbackId, $text, $showAlert);

			return true;
		} catch (\Exception $e) {
			Log::error("Failed to answer callback query safely", [
				"callback_id" => $callbackId,
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return false;
		}
	}

	/**
	 * Edit message with safety checks
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

			if ($options["auto_escape"]) {
				$text = TelegramMarkdownHelper::safeText($text, $options["parse_mode"]);
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
	 * Truncate text to specified length with ellipsis
	 */
	protected function truncateText(string $text, int $maxLength): string
	{
		if (mb_strlen($text) <= $maxLength) {
			return $text;
		}

		// Subtract 3 for ellipsis
		$truncated = mb_substr($text, 0, $maxLength - 3);

		// Ensure we don't cut in the middle of a word (optional)
		$lastSpace = mb_strrpos($truncated, " ");
		if ($lastSpace !== false && $lastSpace > $maxLength - 10) {
			$truncated = mb_substr($truncated, 0, $lastSpace);
		}

		return $truncated . "...";
	}

	/**
	 * Validate callback data structure
	 */
	protected function validateCallbackData(array $data): bool
	{
		$required = ["entity", "action"];

		foreach ($required as $field) {
			if (empty($data[$field])) {
				Log::warning("Missing required callback data field", [
					"field" => $field,
					"data" => $data,
				]);
				return false;
			}
		}

		return true;
	}

	/**
	 * Get default answer for unknown callback
	 */
	protected function getUnknownCallbackAnswer(): string
	{
		return "Aksi tidak dikenali atau telah kadaluarsa.";
	}

	/**
	 * Get error answer for failed operations
	 */
	protected function getErrorAnswer(string $error = ""): string
	{
		$base = "Terjadi kesalahan saat memproses permintaan.";

		if (!empty($error)) {
			// Shorten error message if too long
			if (mb_strlen($error) > 50) {
				$error = mb_substr($error, 0, 47) . "...";
			}
			return $base . "\n" . $error;
		}

		return $base;
	}

	/**
	 * Handle callback with automatic answer
	 */
	protected function handleCallbackWithAutoAnswer(
		array $context,
		array $data,
		callable $handler
	): array {
		try {
			$callbackId = $context["callback_id"] ?? null;
			$chatId = $context["chat_id"] ?? null;
			$messageId = $context["message_id"] ?? null;

			if (!$callbackId) {
				Log::error("Missing callback ID in context", ["context" => $context]);
				return ["status" => "error", "message" => "Missing callback ID"];
			}

			// Validate callback data
			if (!$this->validateCallbackData($data)) {
				$this->answerCallbackQuery(
					$callbackId,
					$this->getUnknownCallbackAnswer(),
					false
				);
				return [
					"status" => "invalid_data",
					"message" => "Invalid callback data",
				];
			}

			// Execute the handler
			$result = $handler($data, $context);

			// Handle answer from result
			$this->handleCallbackResult($callbackId, $chatId, $messageId, $result);

			return $result;
		} catch (\Exception $e) {
			Log::error("Callback handling failed", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
				"data" => $data,
				"context" => $context,
			]);

			// Answer with error
			if (isset($context["callback_id"])) {
				$this->answerCallbackQuery(
					$context["callback_id"],
					$this->getErrorAnswer($e->getMessage()),
					true
				);
			}

			return [
				"status" => "error",
				"message" => $e->getMessage(),
				"error" => $e->getMessage(),
			];
		}
	}

	/**
	 * Handle callback answer based on result
	 */
	private function handleCallbackResult(
		string $callbackId,
		?int $chatId,
		?int $messageId,
		array $result
	): void {
		$this->handleCallbackAnswer($callbackId, $result);

		$this->handleMessageOperations($chatId, $messageId, $result);
	}

	/**
	 * Handle callback answer based on result
	 */
	private function handleCallbackAnswer(string $callbackId, array $result): void
	{
		if (isset($result["answer"])) {
			$answer = $result["answer"];
			$showAlert = $result["show_alert"] ?? false;

			$this->answerCallbackQuery($callbackId, $answer, $showAlert, [
				"send_as_message" => $result["send_as_message"] ?? false,
				"message_chat_id" => $result["message_chat_id"] ?? null,
				"message_options" => $result["message_options"] ?? [],
			]);
		} else {
			// Acknowledge callback without showing anything
			$this->answerCallbackQuery($callbackId, "", false);
		}
	}

	/**
	 * Handle message operations (edit, delete, send new)
	 */
	private function handleMessageOperations(
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

			if (!in_array($parseMode, $validParseModes)) {
				Log::warning("Invalid parse_mode specified, using Markdown", [
					"parse_mode" => $parseMode,
					"valid_modes" => $validParseModes,
				]);
				$parseMode = "Markdown";
			}

			if ($parseMode === "MarkdownV2") {
				$text = TelegramMarkdownHelper::safeText($text, $parseMode);
			}

			$this->editMessage($chatId, $messageId, $text, $replyMarkup, [
				"parse_mode" => $parseMode,
			]);
		}

		// Delete existing message
		if (isset($result["delete_message"]) && $messageId) {
			$this->deleteMessage($chatId, $messageId);
		}

		// Send new message
		if (isset($result["send_message"])) {
			$sendData = $result["send_message"];
			$text = $sendData["text"] ?? "No text";
			$replyMarkup = $sendData["reply_markup"] ?? null;
			$parseMode = $sendData["parse_mode"] ?? "Markdown";

			if (!in_array($parseMode, $validParseModes)) {
				$parseMode = "Markdown";
			}

			$this->telegramApi->sendMessage($chatId, $text, $parseMode, $replyMarkup);
		}
	}

	/**
	 * Create edit message data structure
	 */
	protected function createEditMessageData(
		string $text,
		?array $replyMarkup = null,
		string $parseMode = "Markdown",
		bool $autoEscape = true
	): array {
		if ($autoEscape) {
			$text = TelegramMarkdownHelper::safeText($text, $parseMode);
		}

		return [
			"text" => $text,
			"reply_markup" => $replyMarkup,
			"parse_mode" => $parseMode,
			"auto_escape" => $autoEscape,
		];
	}

	/**
	 * Create send message data structure
	 */
	protected function createSendMessageData(
		string $text,
		?array $replyMarkup = null,
		string $parseMode = "Markdown",
		bool $autoEscape = true
	): array {
		if ($autoEscape) {
			$text = TelegramMarkdownHelper::safeText($text, $parseMode);
		}

		return [
			"text" => $text,
			"reply_markup" => $replyMarkup,
			"parse_mode" => $parseMode,
			"auto_escape" => $autoEscape,
		];
	}
}
