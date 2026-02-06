<?php
namespace Modules\Telegram\Installations;

use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Services\Generators\TraitInserter;

class PostInstallation
{
	public function handle(string $moduleName)
	{
		try {
			$module = Module::find($moduleName);
			$module->enable();

			$result = $this->insertTraitToUserModel();
			logger()->info($result["message"]);

			Artisan::call("migrate", ["--force" => true]);
		} catch (\Exception $e) {
			logger()->error(
				"Failed to run post installation of financial module: " .
					$e->getMessage()
			);

			throw $e;
		}
	}

	private function insertTraitToUserModel()
	{
		return TraitInserter::insertTrait("Modules\Telegram\Traits\HasTelegram");
	}
}
