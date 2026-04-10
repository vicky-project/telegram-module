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
    try {
      $initData = $this->getInitData($request);
      if (!$initData) {
        return response()->json(['error' => 'Missing initData', 'message' => 'Missing initData'], 400);
      }

      // Validasi initData
      if (!$this->authService->verifyTelegramData($initData, config("telegram.bot.token"), true)) {
        return response()->json(['error' => 'Invalid initData', 'message' => 'Invalid initData'], 403);
      }

      // Parse data user
      $telegramData = $this->authService->parseUserData($initData);
      $telegramId = $telegramData['id'] ?? null;
      if (!$telegramId) {
        // Telegram id tidak ditemukan
        \Log::error("No telegram user id in init data", $data);
        abort(403, "Invalid user data");
      }

      $telegramUser = TelegramUser::firstOrCreate(
        ['telegram_id' => $telegramId],
        [
          'first_name' => $telegramData['first_name'] ?? null,
          'last_name' => $telegramData['last_name'] ?? null,
          'username' => $telegramData['username'] ?? null,
          'language_code' => $telegramData['language_code'] ?? null,
          'photo_url' => $telegramData['photo_url'] ?? null,
          'data' => $telegramData,
        ]
      );

      $telegramUser->tokens()->delete();

      $token = $telegramUser->createToken('telegram-mini-app', ["access-app"], now()->plus(weeks: 1))->plainTextToken;

      return response()->json([
        'success' => true,
        'token' => $token,
        'user' => $telegramUser->only([
          'id',
          'telegram_id',
          'first_name',
          'username'
        ]),
      ]);
    } catch(\Exception $e) {
      \Log::error("Error authenticate telegram.", ["message" => $e->getMessage()]);
      return response()->json(["error" => true, "message" => $e->getMessage()], 500);
    }
  }

  private function getInitData(Request $request) {
    return $request->input('initData') ?? $request->header('X-Telegram-Init-Data') ?? $request->query('initData');
  }
}