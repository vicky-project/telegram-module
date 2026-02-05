<?php
namespace Modules\Telegram\Services\Handlers;

use Telegram\Bot\Objects\Message;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\CommandDispatcher;
use Modules\Telegram\Services\Support\TelegramApi;

class MessageHandler
{
	protected CommandDispatcher $commandDispatcher;
	protected TelegramApi $telegramApi;

	public function __construct(
		CommandDispatcher $commandDispatcher,
		TelegramApi $telegramApi
	) {
		$this->commandDispatcher = $commandDispatcher;
		$this->telegramApi = $telegramApi;
	}

	/**
	 * Handle incoming message
	 */
	public function handle(Message $message): array
	{
		$chatId = $message->getChat()->getId();
		$text = $message->getText() ?? "";
		$username = $message->getChat()->getUsername();

		Log::info("Telegram message received", [
			"chat_id" => $chatId,
			"username" => $username,
			"text" => $text,
		]);

		// Handle command
		if ($this->isCommand($text)) {
			//return $this->commandDispatcher->handleCommand($chatId, $text, $username);
		}

		// Handle regular text message
		return $this->handleTextMessage($chatId, $text);
	}

	/**
	 * Handle edited message
	 */
	public function handleEditedMessage(Message $message): array
	{
		Log::info("Telegram edited message", [
			"chat_id" => $message->getChat()->getId(),
			"message_id" => $message->getMessageId(),
		]);

		return ["status" => "edited_message_ignored"];
	}

	/**
	 * Check if text is a command
	 */
	private function isCommand(string $text): bool
	{
		return strpos($text, "/") === 0;
	}

	/**
	 * Handle regular text messages
	 */
	private function handleTextMessage(int $chatId, string $text): array
	{
		$response =
			"Halo! Saya adalah bot untuk manajemen keuangan.\n" .
			"Gunakan /help untuk melihat command yang tersedia.";

		$this->telegramApi->sendMessage($chatId, $response);

		return [
			"status" => "text_message",
			"chat_id" => $chatId,
			"response" => $response,
		];
	}

	/**
	 * Get chat information
	 */
	public function getChatInfo(int $chatId): ?array
	{
		try {
			$chat = $this->telegramApi->getChat($chatId);

			return [
				"id" => $chat->getId(),
				"type" => $chat->getType(),
				"title" => $chat->getTitle(),
				"username" => $chat->getUsername(),
				"first_name" => $chat->getFirstName(),
				"last_name" => $chat->getLastName(),
			];
		} catch (\Exception $e) {
			Log::error("Failed to get chat info", [
				"chat_id" => $chatId,
				"error" => $e->getMessage(),
			]);

			return null;
		}
	}
}
