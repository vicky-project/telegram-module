<?php
namespace Modules\Telegram\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\Telegram\Models\Telegram;
use Modules\Telegram\Repositories\TelegramRepository;
use Modules\UserManagement\Services\SocialAccountService;
use Rappasoft\LaravelAuthenticationLog\Helpers\DeviceFingerprint;
use Rappasoft\LaravelAuthenticationLog\Models\AuthenticationLog;

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
		try {
			// Need package rappasoft/laravel-authentication-log
			if (!class_exists(DeviceFingerprint::class)) {
				\Log::warning(
					"Package rappasoft/laravel-authentication-log not found."
				);
				return false;
			}

			$deviceId = DeviceFingerprint::generate($this->request);

			$cacheKey = "device_user_mapping_" . md5($deviceId);

			$userId = Cache::remember($cacheKey, now()->addHours(), function () use (
				$deviceId
			) {
				$autLog = AuthenticationLog::query()
					->fromDevice($deviceId)
					->successful()
					->recent()
					->whereNotNull("authenticatable_id")
					->latest("login_at")
					->first();

				return $autLog ? $autLog->authenticatable_id : null;
			});

			// Not found historical device login
			if (!$userId) {
				return false;
			}

			$socialAccounts = $this->service->getByUserId($userId);

			// Social Account not exists
			if (!$socialAccounts || $socialAccounts->isEmpty()) {
				return false;
			}

			$telegram = $socialAccounts->where("provider", "telegram")->first();

			// Social Account not have provider
			if (!$telegram || !$telegram->providerable) {
				return false;
			}

			return true;
		} catch (\Exception $e) {
			\Log::error("Error checking device known: " . $e->getMessage(), [
				"message" => $e->getMessage(),
				"trace" => $e->getTraceAsString(),
			]);
			return false;
		}
	}

	public function getUserByChatId(int $chatId)
	{
		return optional($this->telegram->getByChatId($chatId), function (
			Telegram $telegram
		) {
			return $telegram->provider?->user;
		});
	}

	public function unlink(User $user, int $telegramId): bool
	{
		try {
			$telegram = $this->telegram->getByChatId($telegramId);

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
			$telegram = $this->telegram->getByChatId($data["id"]);

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
