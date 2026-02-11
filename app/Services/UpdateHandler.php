<?php
namespace Modules\Telegram\Services;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\Handlers\MessageHandler;
use Modules\Telegram\Services\Handlers\CallbackHandler;

class UpdateHandler
{
	protected Api $telegram;
	protected MessageHandler $messageHandler;
	protected CallbackHandler $callbackHandler;

	public function __construct(
		Api $telegram,
		MessageHandler $messageHandler,
		CallbackHandler $callbackHandler
	) {
		$this->telegram = $telegram;
		$this->messageHandler = $messageHandler;
		$this->callbackHandler = $callbackHandler;
	}

	/**
	 * Handle incoming webhook update
	 */
	public function handle(Request $request): array
	{
		try {
			$update = $this->telegram->getWebhookUpdate();

			if ($update->has("message")) {
				return $this->messageHandler->handle($update->getMessage());
			} elseif ($update->has("edited_message")) {
				return $this->messageHandler->handleEditedMessage(
					$update->getEditedMessage()
				);
			} elseif ($update->has("callback_query")) {
				return $this->callbackHandler->handle($update->getCallbackQuery());
			} elseif ($update->has("edited_message")) {
				return $this->messageHandler->handleEditedMessage(
					$update->getEditedMessage()
				);
			}

			Log::warning("Unhandled update type", [
				"update_id" => $update->getUpdateId(),
				"types" => array_keys($update->toArray()),
			]);

			return ["status" => "unhandled", "update_id" => $update->getUpdateId()];
		} catch (TelegramSDKException $e) {
			Log::error("Telegram SDK error", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			throw $e;
		}
	}

	/**
	 * Get webhook info
	 */
	public function getWebhookInfo(): array
	{
		$info = $this->telegram->getWebhookInfo();

		return [
			"url" => $info->getUrl(),
			"has_custom_certificate" => $info->getHasCustomCertificate(),
			"pending_update_count" => $info->getPendingUpdateCount(),
			"last_error_date" => $info->getLastErrorDate(),
			"last_error_message" => $info->getLastErrorMessage(),
			"max_connections" => $info->getMaxConnections(),
			"allowed_updates" => $info->getAllowedUpdates(),
		];
	}

	/**
	 * Set webhook URL
	 */
	public function setWebhook(string $url, ?string $secretToken = null): bool
	{
		$params = [
			"url" => $url,
			"max_connections" => 40,
			"allowed_updates" => config("telegram.bot.allowed_updates", ["message"]),
		];

		if ($secretToken) {
			$params["secret_token"] = $secretToken;
		}

		return $this->telegram->setWebhook($params);
	}

	/**
	 * Remove webhook
	 */
	public function removeWebhook(): bool
	{
		$response = $this->telegram->removeWebhook();
		return $response;
	}
}
