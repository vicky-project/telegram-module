<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;
use Modules\Telegram\Http\Controllers\TelegramMiniAppController;
use Modules\Telegram\Http\Controllers\TelegramWebhookController;

Route::prefix("telegram")->group(function () {
	Route::middleware(["auth:web", "auth:telegram"])
		->withoutMiddleware(["auth:sanctum"])
		->group(function () {
			Route::post("mini-app/data", [
				TelegramMiniAppController::class,
				"handleData",
			]);
		});

	Route::post("webhook", [TelegramWebhookController::class, "handleWebhook"])
		->withoutMiddleware(["auth:sanctum", "auth"])
		->name("telegram.webhook");
});
