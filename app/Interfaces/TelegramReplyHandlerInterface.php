<?php
namespace Modules\Telegram\Interfaces;

interface TelegramReplyHandlerInterface
{
	/**
	 * Mendapatkan identifier unik untuk handler ini
	 */
	public function getIdentifier(): string;

	/**
	 * Memproses balasan dari user
	 *
	 * @param array $context Data konteks yang disimpan saat mengirim pesan yang mengharapkan balasan
	 * @param string $replyText Teks balasan dari user
	 * @param int $chatId ID chat
	 * @param int $replyToMessageId ID pesan yang dibalas
	 * @return array Response (bisa berisi send_message, edit_message, dll)
	 */
	public function handle(
		array $context,
		string $replyText,
		int $chatId,
		int $replyToMessageId
	): array;
}
