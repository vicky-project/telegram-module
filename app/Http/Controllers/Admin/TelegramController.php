<?php
namespace Modules\Telegram\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Telegram\Models\TelegramUser;

class TelegramController extends Controller
{
  public function index(Request $request) {
    $tgUsers = TelegramUser::all();

    return view("telegram::admin.index", compact('tgUsers'));
  }
}