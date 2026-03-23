<?php
namespace Modules\Telegram\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Telegram\Models\TelegramUser;
use Modules\Telegram\Services\TelegramAuthService;
use Modules\Telegram\Services\TelegramService;

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

    if (!$authService->verifyTelegramData(http_build_query($data), config("telegram.bot.token"), false)) {
      return response()->json(["error" => "Invalid hash"], 403);
    }

    $telegramId = $data['id'] ?? null;
    if (!$telegramId) {
      return response()->json(["error" => "Missing user ID"], 400);
    }

    $user = Auth::check() ? Auth::user() : null;

    $socialAccount = $service->processTelegram($data, $user, $data);

    if ($socialAccount) {
      $user = $socialAccount->user;
      if (!Auth::check()) {
        Auth::login($user);
        session()->regenerate();
      }

      $socialAccount->update(['last_used_at' => now()]);
      return response()->json(["success" => true, "redirect" => route("profile")]);
    }

    return $request->wantsJson() ? response()->json(["error" => "Gagal menghubungkan akun telegram."], 400) : redirect()->route("login")->withErrors("Tidak ditemukan user dengan provider: telegram. Silakan login manual atau registrasi user baru.");
  }
}