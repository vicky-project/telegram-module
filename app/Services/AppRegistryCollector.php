<?php

namespace Modules\Telegram\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ReflectionClass;
use Modules\Telegram\Attributes\RegisterApp;
use Modules\Telegram\Contracts\AppRegistry;
use Modules\Telegram\Providers\TelegramServiceProvider;
use Modules\Telegram\Listeners\CollectAppListener;

class AppRegistryCollector
{
  private const SOURCE_PRIORITY = [
    'lazy_explicit' => 1,
    'config' => 2,
    'hook_service' => 3,
    'event' => 4,
    'attribute' => 5,
  ];

  public static function collect(): array
  {
    $cacheKey = config('telegram.app_cache_key', 'telegram.registered_apps_final');

    return Cache::remember($cacheKey, 3600, function () {
      $apps = [];
      $sources = [
        'lazy_explicit' => self::collectFromLazyExplicit(),
        'config' => self::collectFromConfigFiles(),
        'hook_service' => self::collectFromHookService(),
        'event' => self::collectFromEvents(),
        'attribute' => self::collectFromAttributes(),
      ];

      foreach ($sources as $sourceName => $appsFromSource) {
        foreach ($appsFromSource as $app) {
          if (!isset($app['id'])) {
            Log::warning("App from source '{$sourceName}' missing 'id' field, skipped.");
            continue;
          }
          $id = $app['id'];
          $priority = self::SOURCE_PRIORITY[$sourceName] ?? 999;

          if (!isset($apps[$id])) {
            $apps[$id] = [
              'data' => $app,
              'source' => $sourceName,
              'priority' => $priority,
            ];
          } else {
            if ($priority < $apps[$id]['priority']) {
              Log::warning("Duplicate app id '{$id}' from '{$sourceName}' (priority {$priority}) overwriting '{$apps[$id]['source']}' (priority {$apps[$id]['priority']})");
              $apps[$id] = [
                'data' => $app,
                'source' => $sourceName,
                'priority' => $priority,
              ];
            } else {
              Log::warning("Duplicate app id '{$id}' from '{$sourceName}' (priority {$priority}) ignored, keeping from '{$apps[$id]['source']}'");
            }
          }
        }
      }

      return array_values(array_column($apps, 'data'));
    });
  }

  protected static function collectFromLazyExplicit(): array
  {
    try {
      return TelegramServiceProvider::getExplicitApps();
    } catch (\Throwable $e) {
      return [];
    }
  }

  protected static function collectFromConfigFiles(): array
  {
    $apps = [];
    $modulesPath = base_path('Modules');
    if (!is_dir($modulesPath)) return $apps;

    foreach (File::directories($modulesPath) as $moduleDir) {
      $configFile = $moduleDir . '/config/apps.php';
      if (File::exists($configFile)) {
        $data = include $configFile;
        if (is_array($data)) {
          if (isset($data['id'])) {
            $apps[] = $data;
          } elseif (isset($data[0])) {
            $apps = array_merge($apps, $data);
          }
        }
      }
    }
    return $apps;
  }

  protected static function collectFromHookService(): array
  {
    $apps = [];
    try {
      foreach (app()->tagged('module.app') as $registry) {
        if ($registry instanceof AppRegistry) {
          $appData = $registry->registerApp();
          if (is_array($appData) && isset($appData['id'])) {
            $apps[] = $appData;
          }
        }
      }
    } catch (\Throwable $e) {}
    return $apps;
  }

  protected static function collectFromEvents(): array
  {
    try {
      return CollectAppListener::getApps();
    } catch (\Throwable $e) {
      return [];
    }
  }

  protected static function collectFromAttributes(): array
  {
    $apps = [];
    if (PHP_VERSION_ID < 80000) return $apps;

    $modulesPath = base_path('Modules');
    if (!is_dir($modulesPath)) return $apps;

    foreach (File::allFiles($modulesPath) as $file) {
      if ($file->getExtension() !== 'php') continue;
      $class = self::getClassFromFile($file);
      if (!$class) continue;

      try {
        $reflection = new ReflectionClass($class);
        foreach ($reflection->getAttributes(RegisterApp::class) as $attr) {
          $instance = $attr->newInstance();
          $apps[] = [
            'id' => $instance->id,
            'name' => $instance->name,
            'description' => $instance->description,
            'icon_url' => $instance->iconUrl,
            'icon_emoji' => $instance->iconEmoji ?? substr($instance->name, 0, 1),
            'launch_url' => $instance->launchUrl ?? '#',
          ];
        }
      } catch (\Throwable $e) {}
    }
    return $apps;
  }

  private static function getClassFromFile($file): ?string
  {
    $content = file_get_contents($file->getRealPath());
    $tokens = token_get_all($content);
    $namespace = '';
    $class = '';
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
      if ($tokens[$i][0] === T_NAMESPACE) {
        for ($j = $i+1; $j < $count; $j++) {
          if ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_NAME_QUALIFIED) {
            $namespace .= $tokens[$j][1];
          } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
            break;
          }
        }
      }
      if ($tokens[$i][0] === T_CLASS) {
        for ($j = $i+1; $j < $count; $j++) {
          if ($tokens[$j][0] === T_STRING) {
            $class = $tokens[$j][1];
            break 2;
          }
        }
      }
    }
    return $namespace ? $namespace . '\\' . $class : null;
  }

  public static function clearCache(): void
  {
    Cache::forget(config('telegram.app_cache_key', 'telegram.registered_apps_final'));
  }
}