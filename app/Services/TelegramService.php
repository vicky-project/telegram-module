<?php
namespace Modules\Telegram\Services;

use App\Models\User;
use Modules\Telegram\Models\Telegram;
use Modules\Telegram\Repositories\TelegramRepository;
use Modules\UserManagement\Services\SocialAccountService;

class TelegramService
{
	protected $telegram;
	protected $service;

	public function __construct(
		TelegramRepository $telegram,
		SocialAccountService $service
	) {
		$this->telegram = $telegram;
		$this->service = $service;
	}

	public function processTelegram(array $data, ?User $user = null)
	{
		if ($user) {
			return $this->saveAndConnectToSocialAccount($user, $data);
		}
	}

	protected function saveAndConnectToSocialAccount(User $user, array $data)
	{
		try {
			$telegram = $this->telegram->firstOrCreate($data);

			$this->service->saveUserSocialAccountByProvider(
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
