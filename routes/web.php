<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;
use Modules\Telegram\Http\Controllers\TelegramLinkController;

Route::prefix("telegram")
	->name("telegram.")
	->group(function () {
		Route::middleware(["auth"])->group(function () {
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

			Route::get("redirect-auth", [
				TelegramController::class,
				"redirectAuth",
			])->name("redirect-auth");
		});
	});

Route::get("redirect-login", [
	TelegramLinkController::class,
	"redirectLogin",
])->name("redirect-login");
