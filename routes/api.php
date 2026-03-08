<?php
use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramWebhookController;

Route::group(['prefix' => 'telegram', 'as' => 'telegram.'], function () {
  Route::post("webhook", [TelegramWebhookController::class, "handleWebhook"])
  ->withoutMiddleware(["auth:sanctum", "auth"])
  ->name("webhook");
});