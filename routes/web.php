<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;

Route::prefix("telegram")
	->name("telegram.")
	->group(function () {
		Route::middleware(["auth"])->group(function () {
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
