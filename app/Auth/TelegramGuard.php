<?php

namespace Modules\Telegram\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Telegram\Services\TelegramService;

class TelegramGuard implements Guard
{
	use GuardHelpers;

	protected $request;
	protected $provider;
	protected $botToken;
	protected $service;

	public function __construct(
		UserProvider $provider,
		Request $request,
		TelegramService $service
	) {
		$this->provider = $provider;
		$this->request = $request;
		$this->service = $service;
		$this->botToken = config("telegram.bot.token");
	}

	/**
	 * Mendapatkan user yang terautentikasi berdasarkan data Telegram.
	 */
	public function user()
	{
		if ($this->user !== null) {
			return $this->user;
		}

		// Jika tidak ada parameter 'hash', tolak
		if (!$this->request->has("hash")) {
			return null;
		}

		$params = $this->request->all();
		$hash = $params["hash"];
		unset($params["hash"]);

		// Verifikasi tanda tangan Telegram
		if (!$this->validateTelegramData($params, $hash)) {
			return null;
		}

		// Cari user berdasarkan telegram_id (dari parameter 'id')
		if (!isset($params["id"])) {
			return null;
		}

		try {
			$user = $this->service->processTelegram($params);
			if ($user) {
				$this->user = $user;
				return $user;
			}
		} catch (\Exception $e) {
			\Log::error("TelegramGuard: gagal memproses user.", [
				"error" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
		}

		return null;
	}

	/**
	 * Validasi data dari Telegram.
	 *
	 * @param array $data
	 * @param string $hash
	 * @return bool
	 */
	protected function validateTelegramData(array $data, string $hash): bool
	{
		// 1. Bentuk data_check_string (urutkan key, format key=value, dipisah newline)
		ksort($data);
		$pairs = [];
		foreach ($data as $key => $value) {
			$pairs[] = "$key=$value";
		}
		$dataCheckString = implode("\n", $pairs);

		// 2. Secret key = HMAC-SHA256(bot_token, "WebAppData")
		$secretKey = hash_hmac("sha256", $this->botToken, "WebAppData", true);

		// 3. Hitung HMAC dari data_check_string
		$computedHash = hash_hmac("sha256", $dataCheckString, $secretKey);

		// 4. Bandingkan dengan hash yang dikirim
		return hash_equals($computedHash, $hash);
	}

	/**
	 * Tidak digunakan untuk login manual.
	 */
	public function validate(array $credentials = [])
	{
		return false;
	}
}
