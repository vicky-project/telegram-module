<?php
use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\MiniApp\MiniAppController;
use Modules\Telegram\Http\Controllers\Auth\TelegramLoginController;

Route::group([
  "prefix" => "telegram", "as" => "telegram.", "middleware" => "web"], function() {
  Route::get("/", function() {
    return view("telegram::entry");
  })->name("entry");

  Route::get("/not-connected", function() {
    return view("telegram::not-connected");
  })->name("not-connected");

  Route::group(["prefix" => "login", "as" => "login."], function() {
    Route::get("/", [TelegramLoginController::class, "index"])->name("index");
    Route::post("/process", [TelegramLoginController::class, "process"])->name("process");
  });

  // Halaman Mini App (dilindungi middleware validasi)
  Route::group(['middleware' => ['web', 'telegram.miniapp']], function () {
    Route::get("/home", [MiniAppController::class, "index"])->name("home");
  });
});