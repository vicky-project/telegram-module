<?php
namespace Modules\Telegram\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SocialAccount\Enums\Provider;
use Modules\SocialAccount\Models\SocialAccount;
use Modules\Telegram\Models\TelegramUser;
use Modules\Telegram\Services\TelegramAuthService;

class TelegramAuthController extends Controller
{
  protected $authService;

  public function __construct(TelegramAuthService $authService) {
    $this->authService = $authService;
  }

  public function authenticate(Request $request) {
    $initData = $request->input('initData');
    if (!$initData) {
      return response()->json(['error' => 'Missing initData'], 400);
    }

    // Validasi initData
    if (!$this->authService->verifyTelegramData($initData, config("telegram.bot.token"), true)) {
      return response()->json(['error' => 'Invalid initData'], 403);
    }

    // Parse data user
    $telegramData = $this->authService->parseUserData($initData);
    $telegramUser = TelegramUser::firstOrCreate(
      ['telegram_id' => $telegramData['id']],
      [
        'first_name' => $telegramData['first_name'] ?? '',
        'last_name' => $telegramData['last_name'] ?? '',
        'username' => $telegramData['username'] ?? '',
        'photo_url' => $telegramData['photo_url'] ?? '',
        'data' => $telegramData,
      ]
    );

    // Cari social account yang terhubung
    $socialAccount = SocialAccount::where('provider', Provider::TELEGRAM)
    ->where('providerable_id', $telegramUser->id)
    ->where('providerable_type', TelegramUser::class)
    ->first();

    if (!$socialAccount) {
      return response()->json(['error' => 'Akun Telegram belum terhubung'], 403);
    }

    $user = $socialAccount->user;
    $token = $user->createToken('telegram-token', ["*"], now()->plus(weeks: 1))->plainTextToken;

    return response()->json([
      'success' => true,
      'token' => $token,
      'user' => $user,
    ]);
  }
}