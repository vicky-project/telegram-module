<?php
<<<<<<< HEAD

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
		Route::post("unlink", [TelegramController::class, "unlink"])->name(
			"unlink"
		);

		Route::get("redirect-login", [
			TelegramController::class,
			"redirectLogin",
		])->name("redirect-login");
	});
=======
use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\MiniApp\MiniAppController;
use Modules\Telegram\Http\Controllers\Auth\TelegramLoginController;

Route::group(['prefix' => 'telegram', 'middleware' => ['web'], 'as' => 'telegram.'], function () {
  // Halaman Mini App (dilindungi middleware validasi)
  Route::group(['middleware' => ['telegram']], function () {
    Route::get('/dashboard', [MiniAppController::class, 'dashboard'])->name('mini-app.dashboard');
    Route::get('/profile', [MiniAppController::class, 'profile'])->name('mini-app.profile');
  });

  // Endpoint autentikasi (menggunakan initData)
  Route::post('/auth', [TelegramLoginController::class, 'authenticate'])->name('auth');

  Route::get('/login/telegram', [TelegramLoginController::class, 'redirect'])->name('login');
});
>>>>>>> 984b245 (updates)
