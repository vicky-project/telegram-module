<?php
use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramProfileController;
use Modules\Telegram\Http\Controllers\MiniApp\MiniAppController;
use Modules\Telegram\Http\Controllers\Admin\TelegramController;
use Modules\Telegram\Http\Controllers\Auth\TelegramLoginController;

Route::prefix("admin")
->name("admin.")
->middleware(["web", "auth"])
->group(function() {
  Route::prefix("telegram")
  ->name("telegram.")
  ->group(function() {
    Route::get("/", [TelegramController::class, "index"])->name('index');
    Route::get("/{id}/show", [TelegramController::class, "show"])->name("show");
  });
});

Route::group([
  "prefix" => "telegram", "as" => "telegram.", "middleware" => "web"], function() {
  Route::get('profile', [TelegramProfileController::class, 'show'])->name('profile')->middleware('auth');
  Route::view("/", "telegram::entry")->name("entry");

  Route::view("/not-connected", "telegram::not-connected")->name("not-connected");

  Route::group(["prefix" => "login", "as" => "login."], function() {
    Route::get("/", [TelegramLoginController::class, "index"])->name("index");
    Route::post("/process", [TelegramLoginController::class, "process"])->name("process");
  });
});