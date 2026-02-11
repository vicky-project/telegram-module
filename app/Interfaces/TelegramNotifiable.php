<?php
namespace Modules\Telegram\Interfaces;

use App\Models\User;
interface TelegramNotifiable
{
	/**
	 * Get Telegram chat ID(s)
	 * Can be string, array, or collection
	 */
	public function getUser(): User;

	/**
	 * Get formatted message for Telegram
	 */
	public function getTelegramMessage(): string;

	/**
	 * Get Telegram send options
	 */
	public function getTelegramOptions(): array;
}
