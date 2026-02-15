<?php
namespace Modules\Telegram\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class TelegramMiniAppController extends Controller
{
	public function index(Request $request)
	{
		return view("telegram::mini-apps.index");
	}

	public function handleData(Request $request)
	{
		$initData = $request->input("initData");
		return response()->json($initData);
	}
}
