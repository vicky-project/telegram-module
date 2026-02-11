<?php
namespace Modules\Telegram\Listeners;

use Modules\Telegram\Interfaces\TelegramNotifiable;
use Modules\Telegram\Traits\MessageOperations;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendTelegramNotification implements ShouldQueue
{
	use MessageOperations;

	public $queue = "telegram";

	public function handle($event)
	{
		// Check if event implements the contract
		if (!$event instanceof TelegramNotifiable) {
			Log::warning("Event does not implement TelegramNotifiable", [
				"event" => get_class($event),
			]);
			return;
		}

		try {
			$user = $event->getUser();
			$telegram = $user->socialAccounts->byProvider("telegram")->first()
				->providerable;
			if (!$telegram->notifications) {
				Log::warning("Skip notification disabled by user.", [
					"user" => $user->name,
					"telegram_id" => $telegram->telegram_id,
				]);
				return;
			}

			$chatIds = $this->normalizeChatIds($telegram->telegram_id);

			$message = $event->getTelegramMessage();
			$options = $event->getTelegramOptions();
			$replyMarkup = $options["reply_markup"] ?? null;
			$parseMode = $options["parse_mode"] ?? "Markdown";

			foreach ($chatIds as $chatId) {
				$this->sendMessage(
					$chatId,
					$message,
					$replyMarkup,
					$parseMode,
					$options
				);

				Log::info("Telegram notification sent", [
					"chat_id" => $chatId,
					"event" => get_class($event),
					"message_length" => strlen($message),
				]);
			}
		} catch (\Exception $e) {
			Log::error("Failed to send Telegram notification", [
				"event" => get_class($event),
				"error" => $e->getMessage(),
			]);

			// Optional: Retry logic
			if ($this->attempts() < 3) {
				throw $e;
			}
		}
	}

	/**
	 * Normalize chat IDs to array
	 */
	protected function normalizeChatIds($chatIds): array
	{
		if (is_string($chatIds)) {
			return [$chatIds];
		}

		if (is_array($chatIds)) {
			return $chatIds;
		}

		if (is_iterable($chatIds)) {
			return iterator_to_array($chatIds);
		}

		return [];
	}

	public function failed($event, \Throwable $exception)
	{
		Log::error("Telegram notification job failed", [
			"event" => get_class($event),
			"error" => $exception->getMessage(),
		]);
	}
}
