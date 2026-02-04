<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;
use Modules\Telegram\Http\Controllers\TelegramLinkController;
use Modules\Telegram\Http\Controllers\TelegramWebhookController;

Route::prefix("telegram")->group(function () {
	Route::middleware(["auth:web"])
		->withoutMiddleware(["auth:sanctum"])
		->group(function () {
			Route::post("generate-code", [
				TelegramLinkController::class,
				"generateCode",
			])->name("generate-code");
			Route::post("unlink", [TelegramLinkController::class, "unlink"])->name(
				"unlink"
			);
			Route::post("update-settings", [
				TelegramLinkController::class,
				"updateSettings",
			])->name("update-settings");
		});

	Route::post("webhook", [TelegramWebhookController::class, "handleWebhook"])
		->withoutMiddleware(["auth:sanctum", "auth"])
		->name("telegram.webhook");
});
