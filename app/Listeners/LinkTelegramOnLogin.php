<?php

namespace Modules\Telegram\Listeners;

use Illuminate\Auth\Events\Login;
use Modules\Telegram\Services\TelegramAuthService;
use Illuminate\Support\Facades\Session;

class LinkTelegramOnLogin
{
	protected $telegramAuthService;

	public function __construct(TelegramAuthService $telegramAuthService)
	{
		$this->telegramAuthService = $telegramAuthService;
	}

	/**
	 * Handle the event.
	 */
	public function handle(Login $event)
	{
		// Cek apakah ada pending initData dari middleware
		if (Session::has("telegram_pending_init")) {
			$initData = Session::get("telegram_pending_init");

			// Hubungkan akun Telegram ke user yang baru login
			$this->telegramAuthService->linkTelegramToUser($initData, $event->user);

			// Hapus pending dari session
			Session::forget("telegram_pending_init");

			// Opsional: set flash message
			Session::flash("status", "Akun Telegram berhasil dihubungkan!");
		}
	}
}
