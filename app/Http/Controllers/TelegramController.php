<?php

namespace Modules\Telegram\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Telegram\Models\Telegram;

class TelegramController extends Controller
{
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
						"User not found or user not connected to telegram yet. Please login using another credential or register an account to connect with telegram."
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
					"Can not login or register using telegram. Please create user manual or login with another credential."
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

	/**
	 * Show the form for creating a new resource.
	 */
	public function create()
	{
		return view("telegram::create");
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(Request $request)
	{
	}

	/**
	 * Show the specified resource.
	 */
	public function show($id)
	{
		return view("telegram::show");
	}

	/**
	 * Show the form for editing the specified resource.
	 */
	public function edit($id)
	{
		return view("telegram::edit");
	}

	/**
	 * Update the specified resource in storage.
	 */
	public function update(Request $request, $id)
	{
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy($id)
	{
	}
}
