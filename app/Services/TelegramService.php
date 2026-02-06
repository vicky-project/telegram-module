<?php
namespace Modules\Telegram\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Telegram\Models\Telegram;
use Modules\Telegram\Repositories\TelegramRepository;
use Modules\UserManagement\Services\SocialAccountService;

class TelegramService
{
	protected $request;
	protected $telegram;
	protected $service;

	public function __construct(
		Request $request,
		TelegramRepository $telegram,
		SocialAccountService $service
	) {
		$this->request = $request;
		$this->telegram = $telegram;
		$this->service = $service;
	}

	public function processTelegram(array $data, ?User $user = null)
	{
		if ($user) {
			return $this->saveAndConnectToSocialAccount($user, $data);
		}

		$telegram = Telegram::query()
			->byTelegramId($data["id"])
			->first();

		if (!$telegram) {
			return null;
		}

		$user = $telegram
			->provider()
			->byProvider("telegram")
			->first()?->user;

		if (!$user) {
			return null;
		}

		Auth::login($user);
		return $user;
	}

	public function checkDeviceKnown(): bool
	{
		// Need package rappasoft/laravel-authentication-log
		if (
			!class_exists(
				\Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::class
			)
		) {
			return false;
		}

		$deviceId = \Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint::generate(
			$this->request
		);

		$authFound = \Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog::query()
			->fromDevice($deviceId)
			->successful()
			->recent()
			->first();

		// Not found historical device login
		if (!$authFound) {
			return false;
		}

		$socialAccount = $this->service->getByAuthlogId($authFound->id);
		dd($socialAccount, $authFound);

		// Social Account not exists
		if (!$socialAccount || $socialAccount->isEmpty()) {
			return false;
		}

		$telegram = $socialAccount->where("provider", "telegram")->first();

		// Social Account not have provider
		if (!$telegram->providerable) {
			return false;
		}

		return true;
	}

	public function unlink(User $user, int $telegramId): bool
	{
		try {
			$telegram = Telegram::byTelegramId($telegramId)->first();

			return $user
				->socialAccounts()
				->byProvider("telegram")
				->where("providerable_id", $telegram->id)
				->delete();
		} catch (\Exception $e) {
			throw $e;
		}
	}

	protected function saveAndConnectToSocialAccount(User $user, array $data)
	{
		try {
			$telegram = $this->telegram->firstOrCreate($data);

			$socialAccount = $this->service->saveUserSocialAccountByProvider(
				$user,
				$telegram,
				"telegram"
			);

			return $telegram;
		} catch (\Exception $e) {
			throw $e;
		}
	}

	protected function tryLoginUsingTelegam(array $data)
	{
		try {
			$telegram = Telegram::where("telegram_id", $data["id"])->firstOrFail();

			if ($telegram) {
				$user = $telegram->provider->user;

				\Auth::login($user);
				return $user;
			}

			return false;
		} catch (\Exception $e) {
			throw $e;
		}
	}
}
