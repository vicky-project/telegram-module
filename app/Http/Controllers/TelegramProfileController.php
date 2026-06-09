<?php

namespace Modules\Telegram\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Telegram\Models\TelegramUser;

class TelegramProfileController extends Controller
{
  public function show(Request $request) {
    // Ambil user Telegram yang terhubung dengan user login saat ini
    $user = $request->user();
    $telegramUser = TelegramUser::whereHas('provider', function ($query) use($user) {
      $query->where('user_id', $user->id);
    })->first();

    if (!$telegramUser) {
      abort(404, 'Akun Telegram belum terhubung.');
    }

    // Ambil aktivitas terakhir (opsional)
    $activities = $telegramUser->activities()->latest()->take(10)->get();

    return view('telegram::profile', compact('telegramUser', 'activities'));
  }
}