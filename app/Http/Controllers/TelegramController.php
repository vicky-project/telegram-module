<?php

namespace Modules\Telegram\Http\Controllers;

use App\Models\User;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Modules\Telegram\Models\Telegram;
use Modules\Telegram\Services\TelegramService;

class TelegramController extends Controller
{
	protected $service;

	public function __construct(TelegramService $service)
	{
		$this->service = $service;
	}

	public function index(Request $request)
	{
	}

	/**
	 * Display a listing of the resource.
	 */
	public function redirectAuth(Request $request)
	{
		try {
			$auth_data = $this->checkTelegramAuthorization(
				$request->only([
					"id",
					"first_name",
					"last_name",
					"username",
					"auth_date",
					"hash",
				])
			);

			$user = Auth::user();

			$telegram = $this->service->processTelegram($auth_data, $user);

			if ($telegram) {
				return response()->json([
					"success" => true,
					"message" => "Telegram connected",
					"data" => $telegram,
				]);
			} else {
				return response()->json([
					"success" => false,
					"message" => "Failed connecting application to telegram",
				]);
			}
		} catch (\Exception $e) {
			\Log::error("Failed to login using telegram", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return response()->json([
				"success" => false,
				"message" => $e->getMessage(),
			]);
		}
	}

	public function redirectLogin(Request $request)
	{
		try {
			$auth_data = $this->checkTelegramAuthorization(
				$request->only([
					"id",
					"first_name",
					"last_name",
					"username",
					"auth_date",
					"hash",
				])
			);

			$user = $this->service->processTelegram($auth_data);

			return redirect()
				->route("dashboard")
				->with("success", "Welcome Back: " . $user->name);
		} catch (\Exception $e) {
			return back()->withErrors($e->getMessage());
		}
	}

	private function checkTelegramAuthorization($auth_data)
	{
		\Log::info("Get data from telegram.", ["data" => $auth_data]);

		$bot_token = config("telegram.token");
		$check_hash = $auth_data["hash"];
		unset($auth_data["hash"]);
		$data_check_arr = [];
		foreach ($auth_data as $key => $value) {
			$data_check_arr[] = $key . "=" . $value;
		}
		sort($data_check_arr);
		$data_check_string = implode("\n", $data_check_arr);
		$secret_key = hash("sha256", $bot_token, true);
		$hash = hash_hmac("sha256", $data_check_string, $secret_key);

		if (strcmp($hash, $check_hash) !== 0) {
			throw new \Exception("Data is NOT from Telegram");
		}
		if (time() - $auth_data["auth_date"] > 86400) {
			throw new \Exception("Data is outdated");
		}
		return $auth_data;
	}
}
