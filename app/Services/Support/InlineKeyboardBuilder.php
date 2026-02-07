<?php
namespace Modules\Telegram\Services\Support;

use Modules\Telegram\Services\Handlers\CallbackHandler;

class InlineKeyboardBuilder
{
	protected CallbackHandler $callbackHandler;

	public function __construct(CallbackHandler $callbackHandler)
	{
		$this->callbackHandler = $callbackHandler;
	}

	/**
	 * Build pagination keyboard
	 */
	public function pagination(
		string $action,
		int $currentPage,
		int $totalPages,
		array $extraParams = []
	): array {
		$keyboard = [];

		// Previous button
		if ($currentPage > 1) {
			$prevParams = array_merge($extraParams, ["page" => $currentPage - 1]);
			$keyboard[] = [
				"text" => "⬅️ Sebelumnya",
				"callback_data" => $this->callbackHandler->createCallbackData(
					$action,
					$prevParams
				),
			];
		}

		// Current page indicator
		$keyboard[] = [
			"text" => "{$currentPage}/{$totalPages}",
			"callback_data" => "noop", // No operation
		];

		// Next button
		if ($currentPage < $totalPages) {
			$nextParams = array_merge($extraParams, ["page" => $currentPage + 1]);
			$keyboard[] = [
				"text" => "Berikutnya ➡️",
				"callback_data" => $this->callbackHandler->createCallbackData(
					$action,
					$nextParams
				),
			];
		}

		return [$keyboard];
	}

	/**
	 * Build confirmation keyboard
	 */
	public function confirmation(
		string $action,
		string $itemId,
		string $confirmText = "✅ Ya",
		string $cancelText = "❌ Batal"
	): array {
		return [
			[
				[
					"text" => $confirmText,
					"callback_data" => $this->callbackHandler->createCallbackData(
						$action . ":confirm",
						[$itemId]
					),
				],
				[
					"text" => $cancelText,
					"callback_data" => $this->callbackHandler->createCallbackData(
						$action . ":cancel",
						[$itemId]
					),
				],
			],
		];
	}

	/**
	 * Build grid keyboard from items
	 * format:
	 * [
	 *   [
	 *       'text' => 'Ini adalah text',
	 *       'value' => Ini adalah value
	 *   ],
	 *   [
	 *       'text' => 'Ini adalah text 2',
	 *       'value' => 'Ini adalah value 2'
	 *   ]
	 * ]
	 */
	public function grid(
		array $items,
		int $columns = 2,
		string $action = "select"
	): array {
		$keyboard = [];
		$row = [];

		foreach ($items as $index => $item) {
			$row[] = [
				"text" => $item["text"],
				"callback_data" => $this->callbackHandler->createCallbackData($action, [
					$item["value"],
				]),
			];

			if (count($row) >= $columns) {
				$keyboard[] = $row;
				$row = [];
			}
		}

		if (!empty($row)) {
			$keyboard[] = $row;
		}

		return $keyboard;
	}

	public function getCallbackHandler()
	{
		return $this->callbackHandler;
	}
}
