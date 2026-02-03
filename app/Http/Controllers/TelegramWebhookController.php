<?php
namespace Modules\Telegram\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Telegram\Services\LinkService;
use Modules\Telegram\Services\UpdateHandler;
use Modules\Telegram\Services\Support\TelegramApi;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
	protected TelegramApi $telegram;
	protected $linkService;
	protected $updateHandler;

	public function __construct(
		LinkService $linkService,
		UpdateHandler $updateHandler,
		TelegramApi $telegram
	) {
		$this->linkService = $linkService;

		$this->updateHandler = $updateHandler;
		$this->telegram = $telegram;
	}

	/**
	 * Handle incoming webhook
	 */
	public function handleWebhook(Request $request)
	{
		Log::info("Telegram webhook received", [
			"ip" => $request->ip(),
			"user_agent" => $request->userAgent(),
		]);

		// Verify secret token if set
		if (config("telegram.webhook_secret")) {
			$secret = $request->header("X-Telegram-Bot-Api-Secret-Token");
			if ($secret !== config("telegram.webhook_secret")) {
				Log::warning("Invalid webhook secret", ["provided" => $secret]);
				abort(403, "Invalid secret token");
			}
		}

		try {
			$result = $this->updateHandler->handle($request);

			return response()->json(["status" => "ok", "result" => $result]);
		} catch (TelegramSDKException $e) {
			Log::error("Telegram SDK error", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return response()->json(["error" => "Internal error"], 500);
		} catch (\Exception $e) {
			Log::error("Webhook processing error", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return response()->json(["error" => "Processing error"], 500);
		}
	}

	/**
	 * Set webhook URL (public endpoint)
	 */
	public function setWebhook()
	{
		$this->validateAdmin();

		$url = config("telegram.webhook_url", url("/api/telegram/webhook"));

		try {
			$response = $this->telegram->setWebhook([
				"url" => $url,
				"secret_token" => config("telegram.webhook_secret"),
				"max_connections" => 40,
				"allowed_updates" => ["message", "callback_query"],
			]);
			return response()->json([
				"success" => true,
				"url" => $url,
				"response" => $response,
			]);
		} catch (TelegramSDKException $e) {
			return response()->json(
				[
					"success" => false,
					"error" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Remove webhook
	 */
	public function removeWebhook()
	{
		$this->validateAdmin();

		try {
			$response = $this->telegram->removeWebhook();

			return response()->json([
				"success" => true,
				"response" => $response,
			]);
		} catch (TelegramSDKException $e) {
			return response()->json(
				[
					"success" => false,
					"error" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Get webhook info
	 */
	public function getWebhookInfo()
	{
		$this->validateAdmin();

		try {
			$info = $this->telegram->getWebhookInfo();

			if ($info === false) {
				return response()->json([
					"success" => false,
					"error" => "failed to get webhook info.",
				]);
			}

			return response()->json([
				"success" => true,
				"info" => [
					"url" => $info->getUrl(),
					"has_custom_certificate" => $info->getHasCustomCertificate(),
					"pending_update_count" => $info->getPendingUpdateCount(),
					"last_error_date" => $info->getLastErrorDate(),
					"last_error_message" => $info->getLastErrorMessage(),
					"max_connections" => $info->getMaxConnections(),
					"allowed_updates" => $info->getAllowedUpdates(),
				],
			]);
		} catch (TelegramSDKException $e) {
			return response()->json(
				[
					"success" => false,
					"error" => $e->getMessage(),
				],
				500
			);
		}
	}

	/**
	 * Validate admin access
	 */
	private function validateAdmin()
	{
		$admins = explode(",", config("telegram.admin", ""));

		if (!in_array(auth()->id(), $admins)) {
			abort(403, "Unauthorized");
		}
	}

	/**
	 * Test endpoint
	 */
	public function test()
	{
		return response()->json([
			"status" => "ok",
			"timestamp" => now(),
			"bot_username" => config("telegram.username"),
			"webhook_url" => config("telegram.webhook_url", "/api/telegram/webhook"),
		]);
	}
}
