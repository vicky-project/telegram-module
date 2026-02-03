<?php
namespace Modules\Telegram\Services\Handlers;

use Telegram\Bot\Objects\CallbackQuery;
use Illuminate\Support\Facades\Log;
use Modules\Wallet\Services\Telegram\Handlers\Callbacks\CallbackHandlerFactory;
use Modules\Wallet\Services\Telegram\LinkService;
use Modules\Wallet\Services\Telegram\Support\TelegramApi;

class CallbackHandler
{
	protected CallbackHandlerFactory $callbackHandlerFactory;
	protected LinkService $linkService;
	protected TelegramApi $telegramApi;

	public function __construct(
		CallbackHandlerFactory $callbackHandlerFactory,
		LinkService $linkService,
		TelegramApi $telegramApi
	) {
		$this->callbackHandlerFactory = $callbackHandlerFactory;
		$this->linkService = $linkService;
		$this->telegramApi = $telegramApi;
	}

	/**
	 * Handle callback query
	 */
	public function handle(CallbackQuery $callbackQuery): array
	{
		$data = json_decode($callbackQuery->getData(), true);
		$chatId = $callbackQuery
			->getMessage()
			->getChat()
			->getId();
		$callbackQueryId = $callbackQuery->getId();

		Log::info("Processing callback query", [
			"chat_id" => $chatId,
			"callback_query_id" => $callbackQueryId,
			"data" => $data,
		]);

		// Get user by chat_id
		$user = $this->linkService->getUserByChatId($chatId);

		if (!$user) {
			$this->telegramApi->answerCallbackQuery(
				$callbackQueryId,
				"❌ Anda belum terhubung. Gunakan /start untuk menghubungkan akun.",
				true
			);

			return [
				"status" => "callback_failed",
				"reason" => "user_not_linked",
				"chat_id" => $chatId,
			];
		}

		// Determine handler from data
		$type = $data["type"] ?? null;
		$action = $data["action"] ?? null;

		if (!$type || !$action) {
			Log::warning("Invalid callback data", ["data" => $data]);

			$this->telegramApi->answerCallbackQuery(
				$callbackQueryId,
				"❌ Invalid callback data",
				true
			);

			return [
				"status" => "callback_failed",
				"reason" => "invalid_data",
				"data" => $data,
			];
		}

		try {
			// Use factory to get handler
			$handler = $this->callbackHandlerFactory->getHandlerForCallback(
				$action,
				$type
			);

			if (!$handler) {
				Log::warning("No handler found for callback", [
					"action" => $action,
					"type" => $type,
				]);

				$this->telegramApi->answerCallbackQuery(
					$callbackQueryId,
					"❌ Handler not found",
					true
				);

				return [
					"status" => "callback_failed",
					"reason" => "handler_not_found",
					"action" => $action,
					"type" => $type,
				];
			}

			// Handle the callback
			$result = $handler->handle($user, $data, $callbackQuery);

			Log::info("Callback handler executed", [
				"handler" => get_class($handler),
				"result" => $result,
			]);

			return array_merge(
				[
					"status" => "callback_handled",
					"handler" => get_class($handler),
				],
				$result
			);
		} catch (\Exception $e) {
			Log::error("Callback handler error", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
				"data" => $data,
				"chat_id" => $chatId,
			]);

			$this->telegramApi->answerCallbackQuery(
				$callbackQueryId,
				"❌ Terjadi kesalahan sistem",
				true
			);

			return [
				"status" => "callback_error",
				"error" => $e->getMessage(),
				"chat_id" => $chatId,
			];
		}
	}

	/**
	 * Process callback data
	 */
	public function processCallbackData(string $callbackData): ?array
	{
		try {
			return json_decode($callbackData, true);
		} catch (\Exception $e) {
			Log::error("Failed to parse callback data", [
				"callback_data" => $callbackData,
				"error" => $e->getMessage(),
			]);

			return null;
		}
	}

	/**
	 * Get callback information
	 */
	public function getCallbackInfo(CallbackQuery $callbackQuery): array
	{
		return [
			"id" => $callbackQuery->getId(),
			"from" => [
				"id" => $callbackQuery->getFrom()->getId(),
				"username" => $callbackQuery->getFrom()->getUsername(),
				"first_name" => $callbackQuery->getFrom()->getFirstName(),
				"last_name" => $callbackQuery->getFrom()->getLastName(),
			],
			"message" => [
				"message_id" => $callbackQuery->getMessage()->getMessageId(),
				"chat_id" => $callbackQuery
					->getMessage()
					->getChat()
					->getId(),
				"date" => $callbackQuery->getMessage()->getDate(),
			],
			"chat_instance" => $callbackQuery->getChatInstance(),
			"data" => json_decode($callbackQuery->getData(), true),
		];
	}
}
