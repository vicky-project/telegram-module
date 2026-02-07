<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;
use Modules\Telegram\Http\Controllers\TelegramWebhookController;

Route::prefix("telegram")->group(function () {
	Route::middleware(["auth:web"])
		->withoutMiddleware(["auth:sanctum"])
		->group(function () {});

	Route::post(config("telegram.bot.token") . "/webhook", [
		TelegramWebhookController::class,
		"handleWebhook",
	])
		->withoutMiddleware(["auth:sanctum", "auth"])
		->name("telegram.webhook");
});
