<?php
namespace Modules\Telegram\Console;

use Illuminate\Console\Command;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;
use Modules\Telegram\Services\UpdateHandler;

class TelegramSetup extends Command
{
	protected $signature = 'telegram:setup 
                            {action : set|remove|info|test} 
                            {--url= : Webhook URL}';

	protected $description = "Setup Telegram webhook";

	public function handle()
	{
		$token = config("telegram.bot.token");

		if (!$token) {
			$this->error("TELEGRAM_BOT_TOKEN not set in .env");
			return 1;
		}

		$telegram = new Api($token);
		$action = $this->argument("action");

		switch ($action) {
			case "set":
				return $this->setWebhook();
			case "remove":
				return $this->removeWebhook();
			case "info":
				return $this->getWebhookInfo();
			case "test":
				return $this->testConnection($telegram);
			default:
				$this->error("Invalid action. Use: set, remove, info, test");
				return 1;
		}
	}

	private function setWebhook(): int
	{
		try {
			$url =
				$this->option("url") ??
				url(config("telegram.bot.webhook_url", "/api/telegram/webhook"));

			$this->info("Setting webhook to: {$url}");

			$secret = config("telegram.bot.webhook_secret");
			if ($secret) {
				$this->info("Using secret token: {$secret}");
			}

			$handler = app(UpdateHandler::class);
			$response = $handler->setWebhook($url, $secret ?? null);

			if ($response) {
				$this->info("✅ Webhook set successfully!");
				$this->showWebhookInfo();
				return 0;
			} else {
				$this->error("❌ Failed to set webhook");
				return 1;
			}
		} catch (\Exception $e) {
			$this->error("❌ Error: " . $e->getMessage());
			Log::error("Failed to set Telegram webhook", [
				"error" => $e->getMessage(),
			]);
			return 1;
		}
	}

	private function removeWebhook(): int
	{
		$this->info("Removing webhook...");

		try {
			$handler = app(UpdateHandler::class);
			$response = $handler->removeWebhook();

			if ($response) {
				$this->info("✅ Webhook removed successfully!");
				return 0;
			} else {
				$this->error("❌ Failed to remove webhook");
				return 1;
			}
		} catch (\Exception $e) {
			$this->error("❌ Error: " . $e->getMessage());
			return 1;
		}
	}

	private function getWebhookInfo(): int
	{
		$this->showWebhookInfo();
		return 0;
	}

	private function showWebhookInfo(): void
	{
		try {
			$handler = app(UpdateHandler::class);
			$info = $handler->getWebhookInfo();

			$this->info("\n📊 Webhook Information:");
			$this->line("URL: " . ($info["url"] ?: "Not set"));
			$this->line("Pending Updates: " . $info["pending_update_count"]);
			$this->line("Last Error: " . ($info["last_error_message"] ?: "None"));
			$this->line("Max Connections: " . $info["max_connections"]);

			if ($info["last_error_date"]) {
				$this->line(
					"Last Error Date: " . date("Y-m-d H:i:s", $info["last_error_date"])
				);
			}

			$this->line("Allowed Updates: " . $info["allowed_updates"]);
		} catch (\Exception $e) {
			$this->error("Failed to get webhook info: " . $e->getMessage());
		}
	}

	private function testConnection(Api $telegram): int
	{
		$this->info("Testing Telegram connection...");

		try {
			$me = $telegram->getMe();

			$this->info("✅ Connection successful!");
			$this->line("Bot ID: " . $me->getId());
			$this->line("Bot Name: " . $me->getFirstName());
			$this->line("Bot Username: @" . $me->getUsername());

			return 0;
		} catch (\Exception $e) {
			$this->error("❌ Connection failed: " . $e->getMessage());
			return 1;
		}
	}
}
