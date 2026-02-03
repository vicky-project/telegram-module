<?php

use Illuminate\Support\Facades\Route;
use Modules\Telegram\Http\Controllers\TelegramController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('telegrams', TelegramController::class)->names('telegram');
});
