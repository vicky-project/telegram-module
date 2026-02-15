<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;
use Modules\Telegram\Http\Controllers\TelegramMiniAppController;

Route::prefix("telegram")
	->name("telegram.")
	->group(function () {
		Route::middleware(["auth"])->group(function () {
			Route::get("redirect-auth", [
				TelegramController::class,
				"redirectAuth",
			])->name("redirect-auth");
		});
		Route::post("unlink", [TelegramController::class, "unlink"])->name(
			"unlink"
		);

		Route::get("redirect-login", [
			TelegramController::class,
			"redirectLogin",
		])->name("redirect-login");

		Route::get("mini-app", [TelegramMiniAppController::class, "index"])->name(
			"mini-app"
		);
	});
