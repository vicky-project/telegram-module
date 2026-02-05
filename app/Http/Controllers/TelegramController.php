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
	public function redirect(Request $request)
	{
		dd($request->previous());
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

			$telegram = $this->saveTelegramData($user, $auth_data);

			if ($telegram) {
				return $request->wantsJson()
					? response()->json([
						"success" => true,
						"message" => "Telegram connected",
						"data" => $telegram,
					])
					: back()->with("success", " Telegram was connected.");
			}

			return $request->wantsJson()
				? response()->json([
					"success" => false,
					"message" => "Failed to save account telegram",
				])
				: back()->withErrors(
					"Can not login using telegram. Please create user manual or login with another credential."
				);
		} catch (\Exception $e) {
			\Log::error("Failed to login using telegram", [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);

			return $request->wantsJson()
				? response()->json(["success" => false, "message" => $e->getMessage()])
				: back()->withErrors($e->getMessage());
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

	protected function saveTelegramData(User $user, array $data)
	{
		try {
			$telegram = $this->service->saveAndConnectToSocialAccount($user, $data);
			return $telegram;
		} catch (\Exception $e) {
			throw $e;
		}
	}
}
