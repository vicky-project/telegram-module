<?php
use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\MiniApp\MiniAppController;

Route::group(['prefix' => 'telegram', 'middleware' => ['web'], 'as' => 'telegram.'], function () {
  // Halaman Mini App (dilindungi middleware validasi)
  Route::group(['middleware' => ['telegram']], function () {
    Route::get('/', [MiniAppController::class, 'index'])->name('mini-app.index');
    Route::get('/profile', [MiniAppController::class, 'profile'])->name('mini-app.profile');
  });
});