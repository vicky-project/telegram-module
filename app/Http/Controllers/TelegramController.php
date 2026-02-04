<?php

namespace Modules\Telegram\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Telegram\Models\Telegram;

class TelegramController extends Controller
{
	public function index(Request $request)
	{
		$user = Auth::user();
		$botUsername = config("telegram.username");
		$settings = $user->getAllTelegramSettings();

		return view("telegram::index", compact("user", "botUsername", "settings"));
	}

	/**
	 * Display a listing of the resource.
	 */
	public function redirect(Request $request)
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

			$telegram = Telegram::where("telegram_id", $auth_data["id"])->first();

			if (!$telegram || $telegram->isEmpty()) {
				return redirect()
					->route("login")
					->withErrors(
						"No user found with connection to telegram. Please login with another credentials."
					);
			}

			$user = $telegram->user;

			if ($user) {
				\Auth::loginUsingId($user->id);

				return redirect()->route(config("telegram.auth_redirect_to_route"));
			}

			return redirect()
				->route("register")
				->withErrors(
					"Can not login using telegram. Please create user manual or login with another credential."
				);
		} catch (\Exception $e) {
			\Log::error("Failed to login using telegram", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return redirect()
				->route("login")
				->withErrors($e->getMessage());
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
