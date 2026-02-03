<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;

Route::prefix("telegram")
	->name("telegram.")
	->group(function () {
		Route::get("redirect", [TelegramController::class, "redirect"])->name(
			"redirect"
		);
	});
