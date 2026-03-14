<?php
namespace Modules\Telegram\Http\Controllers\MiniApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MiniAppController extends Controller
{
  public function index(Request $request) {
    dd($request->all());
    $token = $request->get("token");
    return view('telegram::index', compact("token"));
  }

  public function profile() {
    $telegramUser = session('telegram_user');
    return view('telegram::mini-app.profile', compact('telegramUser'));
  }
}