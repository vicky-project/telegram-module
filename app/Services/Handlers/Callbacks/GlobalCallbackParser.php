<?php
namespace Modules\Telegram\Services\Handlers\Callbacks;

class GlobalCallbackParser
{
	/**
	 * Parse callback data dengan support cross-module
	 */
	public function parse(string $callbackData): array
	{
		$parts = explode(":", $callbackData);

		if (empty($parts)) {
			return [
				"scope" => "global",
				"module" => null,
				"entity" => null,
				"action" => $callbackData,
				"params" => [],
				"full" => $callbackData,
				"is_global" => true,
			];
		}

		// Definisikan scopes yang valid
		$validScopes = ["global", "system", "nav", "app", "user", "admin"];

		// Cek jika bagian pertama adalah scope
		$scope = in_array($parts[0], $validScopes) ? array_shift($parts) : "global";

		// Parse sisanya berdasarkan scope
		switch ($scope) {
			case "nav":
				return $this->parseNavigationData($scope, $parts);
			case "system":
				return $this->parseSystemData($scope, $parts);
			case "app":
				return $this->parseAppData($scope, $parts);
			default:
				return $this->parseGlobalData($scope, $parts);
		}
	}

	/**
	 * Parse data untuk navigasi
	 */
	private function parseNavigationData(string $scope, array $parts): array
	{
		if (count($parts) >= 3) {
			return [
				"scope" => $scope,
				"module" => $parts[0],
				"entity" => $parts[1],
				"action" => $parts[2],
				"id" => $parts[3] ?? null,
				"params" => array_slice($parts, 4),
				"full" => $scope . ":" . implode(":", $parts),
				"is_navigation" => true,
			];
		}

		return [
			"scope" => $scope,
			"module" => null,
			"entity" => null,
			"action" => implode(":", $parts),
			"params" => [],
			"full" => $scope . ":" . implode(":", $parts),
			"is_navigation" => true,
		];
	}

	/**
	 * Parse data global (modul specific)
	 */
	private function parseGlobalData(string $scope, array $parts): array
	{
		$module = $parts[0] ?? null;
		$entity = $parts[1] ?? null;
		$action = $parts[2] ?? null;

		// Jika hanya ada 2 bagian, asumsikan module:action
		if ($module && $entity && !$action) {
			$action = $entity;
			$entity = null;
		}

		return [
			"scope" => $scope,
			"module" => $module,
			"entity" => $entity,
			"action" => $action,
			"id" => $parts[3] ?? null,
			"params" => array_slice($parts, 4),
			"full" => $scope . ":" . implode(":", $parts),
			"is_module_specific" => (bool) $module,
		];
	}

	/**
	 * Parse system data
	 */
	private function parseSystemData(string $scope, array $parts): array
	{
		return [
			"scope" => $scope,
			"module" => $parts[0] ?? null,
			"entity" => $parts[1] ?? null,
			"action" => $parts[2] ?? "system",
			"id" => $parts[3] ?? null,
			"params" => array_slice($parts, 4),
			"full" => $scope . ":" . implode(":", $parts),
			"is_system" => true,
		];
	}

	private function parseAppData(string $scope, array $parts): array
	{
		return [];
	}
}
