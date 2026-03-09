<?php
namespace Modules\Telegram\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Telegram\Models\TelegramUser;
use Modules\Telegram\Services\TelegramAuthService;
use Modules\Telegram\Services\TelegramService;
use Modules\SocialAccount\Models\SocialAccount;

class TelegramLoginController extends Controller
{
  public function index(Request $request) {
    return view("telegram::login");
  }

  public function process(Request $request, TelegramAuthService $authService, TelegramService $service) {
    $data = $request->input('data') ?? $request->query("data");

    if (!$data) {
      return response()->json(["error" => "No data"], 400);
    }

    if (!$authService->verifyTelegramData(urldecode(http_build_query($data)), config("telegram.bot.token"))) {
      return response()->json(["error" => "Invalid hash"], 403);
    }

    $telegramId = $data['id'] ?? null;
    if (!$telegramId) {
      return response()->json(["error" => "Missing user ID"], 400);
    }

    if (Auth::check) {
      $user = Auth::user();
      $telegram = $service->processTelegram($data, $user, $data);
      return response()->json(["success" => true, "redirect" => route("profile")]);
    }
  }
}