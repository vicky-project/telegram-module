<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;

Route::prefix("telegram")
	->name("telegram.")
	->group(function () {
		Route::middleware(["auth"])->group(function () {
			Route::get("", [TelegramController::class, "index"]);
		});

		Route::get("redirect", [TelegramController::class, "redirect"])->name(
			"redirect"
		);
	});
