<?php
namespace Modules\Telegram\Services\Support;

class GlobalCallbackBuilder
{
	/**
	 * Build callback data untuk modul apa pun
	 */
	public static function build(
		string $scope = "global",
		?string $module = null,
		?string $entity = null,
		string $action,
		$id = null,
		array $params = []
	): string {
		$parts = [$scope];

		if ($module) {
			$parts[] = $module;
		}

		if ($entity) {
			$parts[] = $entity;
		}

		$parts[] = $action;

		if ($id !== null) {
			$parts[] = (string) $id;
		}

		if (!empty($params)) {
			foreach ($params as $param) {
				$parts[] = (string) $param;
			}
		}

		$callbackData = implode(":", $parts);

		// Validate Telegram limits
		if (strlen($callbackData) > 64) {
			throw new \InvalidArgumentException(
				"Callback data exceeds 64 characters: " . $callbackData
			);
		}

		return $callbackData;
	}

	// Navigation
	public static function backTo(string $module, string $entity = null): string
	{
		return self::build("nav", $module, $entity, "back");
	}

	public static function home(): string
	{
		return "nav:home";
	}

	// System actions
	public static function systemRefresh(): string
	{
		return "system:refresh";
	}

	public static function systemError(string $code): string
	{
		return self::build("system", null, null, "error", $code);
	}
}
