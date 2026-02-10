<?php
namespace Modules\Telegram\Services\Handlers\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramCallbackHandlerInterface;
use Modules\Telegram\Services\Support\GlobalCallbackBuilder;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Traits\MessageOperations;

abstract class BaseCallbackHandler implements TelegramCallbackHandlerInterface
{
	use MessageOperations;

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
	 * Get handler name for logging and debugging
	 */
	abstract public function getName(): string;

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
					$parseMode = $options["parse_mode"] ?? "Markdown";
					$text = $this->safeText($text, $parseMode);

					// Send as regular message instead
					$this->telegramApi->sendMessage(
						$options["message_chat_id"],
						$text,
						$parseMode,
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
				return [
					"status" => "error",
					"message" => "Missing callback ID",
					"answer" => "Missing callback",
					"show_alert" => true,
				];
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
					"message" => $this->getUnknownCallbackAnswer(),
					"answer" => "Invalid callback",
					"show_alert" => false,
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
}
