<?php
namespace Modules\Telegram\Services\Handlers\Callbacks;

use Illuminate\Support\Facades\Log;
use Modules\Telegram\Interfaces\TelegramCallbackHandlerInterface;
use Modules\Telegram\Services\Support\GlobalCallbackBuilder;
use Modules\Telegram\Services\Support\TelegramApi;

abstract class BaseCallbackHandler implements TelegramCallbackHandlerInterface
{
	protected const MAX_CALLBACK_TEXT_LENGTH = 200;

	protected const MAX_ALERT_TEXT_LENGTH = 200;

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
	): array {
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
						"Markdown",
						$options["message_options"]
					);

					// Answer callback query with fallback message
					$text = $options["fallback_message"];
					$showAlert = false;

					Log::info("Callback query text too long, sent as message instead", [
						"callback_id" => $callbackId,
						"message_chat_id" => $options["message_chat_id"],
						"text_length" => mb_strlen($text),
					]);
				} else {
					// Use fallback message
					$text = $options["fallback_message"];
					Log::warning("Callback query text too long, using fallback", [
						"callback_id" => $callbackId,
						"original_length" => mb_strlen($text),
						"fallback" => $text,
					]);
				}
			}

			// Answer the callback query
			$this->telegramApi->answerCallbackQuery(
				$callbackId,
				$text,
				$showAlert ? true : false
			);

			Log::debug("Callback query answered safely", [
				"callback_id" => $callbackId,
				"text_length" => mb_strlen($text),
				"show_alert" => $showAlert,
			]);

			return [
				"status" => "callback_handled",
				"callback_id" => $callbackId,
				"text_length" => mb_strlen($text),
				"show_alert" => $showAlert,
			];
		} catch (\Exception $e) {
			Log::error("Failed to answer callback query safely", [
				"callback_id" => $callbackId,
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			throw $e;
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
}
