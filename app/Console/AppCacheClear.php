<?php
namespace Modules\Telegram\Console;

use Illuminate\Console\Command;
use Modules\Telegram\Services\AppRegistryCollector;

class AppCacheClear extends Command
{
  protected $signature = 'app:app-clear';

  protected $description = "Clear apps telegram cache";

  public function handle() {
    AppRegistryCollector::clearCache();
    $this->info('Cache application telegram cleared.');
    return 0;
  }
}