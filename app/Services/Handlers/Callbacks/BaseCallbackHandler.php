<?php
namespace Modules\Telegram\Services\Handlers\Callbacks;

use Modules\Telegram\Interfaces\TelegramCallbackHandlerInterface;
use Modules\Telegram\Services\Support\GlobalCallbackBuilder;

abstract class BaseCallbackHandler implements TelegramCallbackHandlerInterface
{
	/**
	 * Get module name (harus diimplementasikan oleh child class)
	 */
	abstract public function getModuleName(): string;

	/**
	 * Get scope (default 'global')
	 */
	public function getScope(): string
	{
		return "global";
	}

	/**
	 * Get pattern untuk module ini
	 */
	public function getPattern(): string
	{
		return "{$this->getScope()}:{$this->getModuleName()}:*";
	}

	/**
	 * Parse data berdasarkan pattern module
	 */
	protected function parseModuleData(array $data, array $context): array
	{
		$parsed = [
			"module" => $this->getModuleName(),
			"entity" => $data["entity"] ?? null,
			"action" => $data["action"] ?? null,
			"id" => $data["id"] ?? null,
			"params" => $data["params"] ?? [],
			"context" => $context,
		];

		return $parsed;
	}

	/**
	 * Build callback data untuk module ini
	 */
	protected function buildModuleCallback(
		string $entity,
		string $action,
		$id = null,
		array $params = []
	): string {
		return GlobalCallbackBuilder::build(
			$this->getScope(),
			$this->getModuleName(),
			$entity,
			$action,
			$id,
			$params
		);
	}
}
