<?php
namespace Modules\Telegram\Services\Support;

class InlineKeyboardBuilder
{
	protected $scope = "global";

	protected $module;

	protected $entity;

	public function setScope(string $scope = "global")
	{
		$this->scope = $scope;
		return $this;
	}

	public function setModule(string $module)
	{
		$this->module = $module;
		return $this;
	}

	public function setEntity(string $entity)
	{
		$this->entity = $entity;
		return $this;
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
				"callback_data" => GlobalCallbackBuilder::build(
					"nav",
					$this->module ?? null,
					$this->entity ?? null,
					$action,
					$extraParams["id"] ?? null,
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
				"callback_data" => GlobalCallbackBuilder::build(
					"nav",
					$this->module ?? null,
					$this->entity ?? null,
					$action,
					$extraParams["id"] ?? null,
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
		$confirmButton = GlobalCallbackBuilder::build(
			$this->scope,
			$this->module ?? null,
			$this->entity ?? null,
			$action . "_confirm",
			$itemId
		);
		$cancelButton = GlobalCallbackBuilder::build(
			$this->scope,
			$this->module ?? null,
			$this->entity ?? null,
			$action . "_cancel",
			$itemId
		);

		return [
			[
				[
					"text" => $confirmText,
					"callback_data" => $confirmButton,
				],
				[
					"text" => $cancelText,
					"callback_data" => $cancelButton,
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
				"callback_data" => GlobalCallbackBuilder::build(
					$this->scope,
					$this->module ?? null,
					$this->entity ?? null,
					$action,
					$item["value"]
				),
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
}
