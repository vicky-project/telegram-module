<?php
namespace Modules\Telegram\Services\Support;

use Telegram\Bot\Api;
use Telegram\Bot\Objects\Message;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramApi
{
	protected Api $telegram;

	public function __construct()
	{
		$token = config("telegram.bot.token");
		$this->telegram = new Api($token);
	}

	public function setWebhook(array $config): bool|string
	{
		if ($this->telegram) {
			return $this->telegram->setWebhook($config);
		}

		return false;
	}

	public function removeWebhook(): bool|string
	{
		if ($this->telegram) {
			return $this->telegram->removeWebhook();
		}

		return false;
	}

	public function getWebhookInfo()
	{
		if ($this->telegram) {
			return $this->telegram->getWebhookInfo();
		}

		return false;
	}

	public function sendMessage(
		int $chatId,
		string $text,
		?string $parseMode = null,
		?array $replyMarkup = null,
		array $options = [],
		?bool $withResponse = false,
	): bool|Message {
		try {
			$params = array_merge(
				[
					"chat_id" => $chatId,
					"text" => $text,
					"parse_mode" => $parseMode,
					"disable_web_page_preview" => $options["disable_preview"] ?? true,
				],
				$options,
			);

			if ($replyMarkup) {
				// bisa berupa keyboard atau force reply
				$params["reply_markup"] = json_encode($replyMarkup);
			}
			if ($this->telegram) {
				$result = $this->telegram->sendMessage($params);
				Log::info("Resulting send message.", ["result" => $result]);

				return $withResponse ? $result : true;
			}

			return false;
		} catch (TelegramSDKException $e) {
			Log::error("Failed to send Telegram message", [
				"chat_id" => $chatId,
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return false;
		}
	}

	public function editMessageText(
		int $chatId,
		int $messageId,
		string $text,
		?array $replyMarkup = null,
		?string $parseMode = null,
	): bool {
		try {
			if ($replyMarkup !== null) {
				$keys = array_keys($replyMarkup);
				if (count($keys) !== 1 || $keys[0] !== "inline_keyboard") {
					throw new TelegramSDKException(
						"Invalid reply_markup for edit message text. Only inline_keyboard is allowed",
					);
				}
			}

			$params = [
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"text" => $text,
			];

			if ($parseMode) {
				$params["parse_mode"] = $parseMode;
			}

			if ($replyMarkup) {
				// bisa berupa keyboard atau force reply
				$params["reply_markup"] = json_encode($replyMarkup);
			}

			if ($this->telegram) {
				$this->telegram->editMessageText($params);
				return true;
			}

			return false;
		} catch (TelegramSDKException $e) {
			Log::error("Failed to edit Telegram message", [
				"chat_id" => $chatId,
				"message_id" => $messageId,
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return false;
		}
	}

	public function deleteMessage(int $chatId, int $messageId): bool
	{
		if ($this->telegram) {
			try {
				$this->telegram->deleteMessage([
					"chat_id" => $chatId,
					"message_id" => $messageId,
				]);
				return true;
			} catch (TelegramSDKException $e) {
				Log::error("Failed to delete Telegram message", [
					"chat_id" => $chatId,
					"message_id" => $messageId,
					"error" => $e->getMessage(),
				]);
				return false;
			}
		}

		return false;
	}

	public function answerCallbackQuery(
		string $callbackQueryId,
		string $text,
		bool $showAlert = false,
	): bool {
		if ($this->telegram) {
			try {
				$this->telegram->answerCallbackQuery([
					"callback_query_id" => $callbackQueryId,
					"text" => $text,
					"show_alert" => $showAlert,
				]);
				return true;
			} catch (TelegramSDKException $e) {
				Log::error("Failed to answer callback query", [
					"callback_query_id" => $callbackQueryId,
					"error" => $e->getMessage(),
				]);
				return false;
			}
		}

		return false;
	}
}
