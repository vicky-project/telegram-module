<?php
use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\MiniApp\MiniAppController;
use Modules\Telegram\Http\Controllers\Auth\TelegramAuthController;

Route::group([
  "prefix" => "telegram", "as" => "telegram."], function() {
  Route::get("/", function() {
    return view("telegram::entry");
  })->name("entry");

  Route::get("/not-connected", function() {
    return view("telegram::not-connected");
  })->name("not-connected");

  Route::post("/auth", [TelegramAuthController::class, "authenticate"])->middleware(["web", "telegram.webapp"])->name("auth");

  // Halaman Mini App (dilindungi middleware validasi)
  Route::group(['middleware' => ['web', 'telegram.auth', 'auth']], function () {
    Route::get("/dashboard", [MiniAppController::class, "index"])->name("dashboard");
  });
});