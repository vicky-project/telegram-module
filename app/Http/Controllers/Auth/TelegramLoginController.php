<?php
namespace Modules\Telegram\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Users\Models\User;
use Modules\Telegram\Models\TelegramUser;
use Modules\SocialAccount\Enums\Provider;
use Modules\SocialAccount\Models\SocialAccount;

class TelegramLoginController extends Controller
{
  public function redirect() {
    return view();
  }

  public function authenticate(Request $request) {
    // Data dari middleware sudah ada di session
    $telegramData = session('telegram_user');
    if (!$telegramData) {
      return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Cari atau buat TelegramUser
    $telegramUser = TelegramUser::firstOrCreate(
      ['telegram_id' => $telegramData['id']],
      [
        'first_name' => $telegramData['first_name'] ?? null,
        'last_name' => $telegramData['last_name'] ?? null,
        'username' => $telegramData['username'] ?? null,
        'photo_url' => $telegramData['photo_url'] ?? null,
        'data' => $telegramData,
      ]
    );

    // Cari social account yang terkait
    $socialAccount = SocialAccount::where('provider', Provider::TELEGRAM)
    ->where('providerable_id', $telegramUser->id)
    ->where('providerable_type', TelegramUser::class)
    ->first();

    if ($socialAccount) {
      // User sudah ada, login
      Auth::login($socialAccount->user);
      return redirect()->route('telegram.mini-app.dashboard');
    }

    // Jika tidak ada, arahkan ke halaman penghubung atau buat user baru?
    // Bisa juga tampilkan form untuk menghubungkan ke akun yang sudah ada
    // Untuk sederhananya, kita buat user baru
    $user = User::create([
      'name' => $telegramUser->first_name . ' ' . $telegramUser->last_name,
      'email' => null, // Telegram mungkin tidak punya email
      'password' => bcrypt(str_random(16)),
    ]);

    SocialAccount::create([
      'user_id' => $user->id,
      'provider' => 'telegram',
      'providerable_id' => $telegramUser->id,
      'providerable_type' => TelegramUser::class,
      'provider_data' => [
        'telegram_id' => $telegramUser->telegram_id,
        'username' => $telegramUser->username,
        'avatar' => $telegramUser->photo_url,
      ],
    ]);

    Auth::login($user);
    return redirect()->route('telegram.mini-app.dashboard');
  }
}