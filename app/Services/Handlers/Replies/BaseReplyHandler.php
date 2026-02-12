<?php
namespace Modules\Telegram\Services\Handlers\Replies;

use Modules\Telegram\Interfaces\TelegramReplyHandlerInterface;
use Modules\Telegram\Services\Support\TelegramApi;
use Modules\Telegram\Traits\MessageOperations;

abstract class BaseReplyHandler implements ReplyHandlerInterface
{
	use MessageOperations;

	protected TelegramApi $telegramApi;

	public function __construct(TelegramApi $telegramApi)
	{
		$this->telegramApi = $telegramApi;
	}

	/**
	 * Nama module (contoh: 'wallet', 'user', 'finance')
	 */
	abstract public function getModuleName(): string;

	/**
	 * Nama entitas (contoh: 'account', 'transaction', 'budget')
	 */
	abstract public function getEntity(): string;

	/**
	 * Nama aksi (contoh: 'create', 'edit', 'delete')
	 */
	abstract public function getAction(): string;

	/**
	 * Identifier unik: module:entity:action
	 */
	public function getIdentifier(): string
	{
		return implode(":", [
			$this->getModuleName(),
			$this->getEntity(),
			$this->getAction(),
		]);
	}

	/**
	 * Proses balasan dari user – WAJIB diimplementasikan oleh child class.
	 */
	abstract public function handle(
		array $context,
		string $replyText,
		int $chatId,
		int $replyToMessageId
	): array;
}
