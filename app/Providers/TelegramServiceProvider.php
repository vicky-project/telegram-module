<?php
namespace Modules\Telegram\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Nwidart\Modules\Facades\Module;
use Modules\Telegram\Services;
use Modules\Telegram\Channels\TelegramChannel;
use Modules\SocialAccount\Enums\Provider;

class TelegramServiceProvider extends ServiceProvider
{
  use PathNamespace;

  protected string $name = 'Telegram';

  protected string $nameLower = 'telegram';

  private static array $explicitApps = [];

  /**
  * Boot the application events.
  */
  public function boot(): void
  {
    $this->registerCommands();
    $this->registerCommandSchedules();
    $this->registerTranslations();
    $this->registerConfig();
    $this->registerViews();
    $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));

    // Register middleware
    $this->app["router"]->aliasMiddleware(
      "telegram.miniapp",
      \Modules\Telegram\Http\Middleware\TelegramMiniApp::class
    );
    $this->app["router"]->aliasMiddleware(
      "telegram.telegram.or.webauth",
      \Modules\Telegram\Http\Middleware\TelegramOrWebAuth::class
    );

    $hasSocialAccount = Module::has("SocialAccount") && Module::isEnabled("SocialAccount");

    if ($hasSocialAccount && class_exists($managerService = \Modules\SocialAccount\Services\SocialProviderManager::class)) {
      $manager = app($managerService);
      $manager->register(new TelegramProvider());
      Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
        $event->extendSocialite('telegram', \SocialiteProviders\Telegram\Provider::class);
      });
    }

    if (
      config($this->nameLower . ".hooks.enabled", false) &&
      class_exists($class = config($this->nameLower . ".hooks.service"))
    ) {
      $this->registerHooks($class);
    }


    $this->mergeConfigFrom(module_path($this->name, 'config/telegram.php'), 'services');
  }

  /**
  * Register the service provider.
  */
  public function register(): void
  {
    $this->app->register(EventServiceProvider::class);
    $this->app->register(RouteServiceProvider::class);

    $this->app
    ->make("config")
    ->set("app.timezone",
      config("telegram.timezone", 'Asia/Jakarta'));
    $this->app
    ->make('config')
    ->set('auth.guards.sanctum', [
      'driver' => 'sanctum',
      'provider' => 'telegram_users'
    ]);
    $this->app
    ->make('config')
    ->set('auth.providers.telegram_users', [
      'driver' => 'eloquent',
      'model' => \Modules\Telegram\Models\TelegramUser::class
    ]);
    $this->app
    ->make('config')
    ->set('sanctum.expiration', now()->addDays(7));

    Notification::resolved(function(ChannelManager $service): void {
      $service->extend("telegram", fn(Application $app) => $app->make(TelegramChannel::class));
    });

    $this->app->singleton(Services\Handlers\CommandDispatcher::class, function (
      $app,
    ) {
      $dispatcher = new Services\Handlers\CommandDispatcher();
      $this->registerCommandHandlers($dispatcher);
      $this->registerMiddlewares($dispatcher);
      return $dispatcher;
    });

    $this->app->singleton(Services\Handlers\CallbackHandler::class, function (
      $app
    ) {
      $callback = new Services\Handlers\CallbackHandler(
        $app->make(Services\Support\TelegramApi::class),
      );
      $this->registerCallbackHandlers($callback);
      $this->registerCallbackMiddlewares($callback);
      return $callback;
    });

    $this->app->singleton(Services\Handlers\ReplyDispatcher::class, function (
      $app,
    ) {
      $replyDispatcher = new Services\Handlers\ReplyDispatcher();
      $this->registerReplyHandlers($replyDispatcher);
      $this->registerReplyMiddlewares($replyDispatcher);
      return $replyDispatcher;
    });

    $this->app->singleton(Services\Handlers\LocationDispatcher::class, function ($app) {
      $locationDispatcher = new Services\Handlers\LocationDispatcher($app->make(Services\Support\TelegramApi::class));
      return $locationDispatcher;
    });

    $this->app->bind(Services\Handlers\MessageHandler::class, function ($app) {
      return new Services\Handlers\MessageHandler(
        $this->app->make(Services\Handlers\CommandDispatcher::class),
        $this->app->make(Services\Handlers\ReplyDispatcher::class),
        $this->app->make(Services\Handlers\LocationDispatcher::class),
        $this->app->make(Services\Support\TelegramApi::class),
      );
    });

    $this->app->singleton(Services\TelegramAuthService::class);
  }

  protected function registerHooks($hookService): void
  {}

  public static function registerAppExplicit(array $app): void
  {
    self::$explicitApps[] = $app;
    // Opsional: clear cache agar data terbaru segera tampil
    Services\AppRegistryCollector::clearCache();
  }

  public static function getExplicitApps(): array
  {
    return self::$explicitApps;
  }

  // Register middleware
  protected function registerMiddlewares(
    Services\Handlers\CommandDispatcher $dispatcher,
  ): void {
    $dispatcher->registerMiddleware(
      "auth",
      new Services\Middlewares\AuthMiddleware(
        $this->app->make(Services\TelegramService::class),
        $this->app->make(Services\Support\TelegramApi::class),
      ),
    );
    $dispatcher->registerMiddleware(
      "ids",
      new Services\Middlewares\IDValidationMiddleware(
        $this->app->make(Services\Support\TelegramIdentifier::class),
        $this->app->make(Services\Support\TelegramApi::class),
      ),
    );
  }

  // Register command
  protected function registerCommandHandlers(
    Services\Handlers\CommandDispatcher $dispatcher,
  ): void {
    $dispatcher->registerCommand(
      new Services\Handlers\Commands\StartCommand(
        $this->app->make(Services\Support\TelegramApi::class),
      ),
    );
    $dispatcher->registerCommand(
      new Services\Handlers\Commands\HelpCommand(
        $this->app->make(Services\Support\TelegramApi::class),
      ),
    );
    $dispatcher->registerCommand(
      new Services\Handlers\Commands\UnlinkCommand(
        $this->app->make(Services\Support\TelegramApi::class),
        $this->app->make(Services\TelegramService::class),
      ),
      ["auth"],
    );
  }

  protected function registerCallbackMiddlewares(
    Services\Handlers\CallbackHandler $callback,
  ): void {
    $callback->registerMiddleware(
      "auth",
      new Services\Middlewares\AuthMiddleware(
        $this->app->make(Services\TelegramService::class),
        $this->app->make(Services\Support\TelegramApi::class),
      ),
    );
    $callback->registerMiddleware(
      "ids",
      new Services\Middlewares\IDValidationMiddleware(
        $this->app->make(Services\Support\TelegramIdentifier::class),
        $this->app->make(Services\Support\TelegramApi::class),
      ),
    );
    $callback->registerMiddleware(
      "callback-throttle",
      new Services\Middlewares\CallbackThrottleMiddleware(),
    );
  }

  protected function registerCallbackHandlers(
    Services\Handlers\CallbackHandler $callback,
  ): void {
    // Add callback handler
    $callback->registerHandler(
      new Services\Handlers\Callbacks\UnlinkCallback(
        $this->app->make(Services\Support\TelegramApi::class),
        $this->app->make(Services\TelegramService::class),
      ),
      ["auth"],
    );
  }

  protected function registerReplyHandlers(
    Services\Handlers\ReplyDispatcher $replyDispatcher,
  ): void {
    // $replyDispatcher->registerHandler();
  }

  protected function registerReplyMiddlewares(
    Services\Handlers\ReplyDispatcher $replyDispatcher,
  ): void {
    $replyDispatcher->registerMiddleware(
      "auth",
      new Services\Middlewares\AuthMiddleware(
        $this->app->make(Services\TelegramService::class),
        $this->app->make(Services\Support\TelegramApi::class),
      ),
    );
  }

  /**
  * Register commands in the format of Command::class
  */
  protected function registerCommands(): void
  {
    $this->commands([\Modules\Telegram\Console\TelegramSetup::class]);
  }

  /**
  * Register command Schedules.
  */
  protected function registerCommandSchedules(): void
  {
    // $this->app->booted(function () {
    //     $schedule = $this->app->make(Schedule::class);
    //     $schedule->command('inspire')->hourly();
    // });
  }

  /**
  * Register translations.
  */
  public function registerTranslations(): void
  {
    $langPath = resource_path('lang/modules/'.$this->nameLower);

    if (is_dir($langPath)) {
      $this->loadTranslationsFrom($langPath, $this->nameLower);
      $this->loadJsonTranslationsFrom($langPath);
    } else {
      $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
      $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
    }
  }

  /**
  * Register config.
  */
  protected function registerConfig(): void
  {
    $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

    if (is_dir($configPath)) {
      $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

      foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
          $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
          $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
          $segments = explode('.', $this->nameLower.'.'.$config_key);

          // Remove duplicated adjacent segments
          $normalized = [];
          foreach ($segments as $segment) {
            if (end($normalized) !== $segment) {
              $normalized[] = $segment;
            }
          }

          $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

          $this->publishes([$file->getPathname() => config_path($config)], 'config');
          $this->merge_config_from($file->getPathname(), $key);
        }
      }
    }
  }

  /**
  * Merge config from the given path recursively.
  */
  protected function merge_config_from(string $path, string $key): void
  {
    $existing = config($key, []);
    $module_config = require $path;

    config([$key => array_replace_recursive($existing, $module_config)]);
  }

  /**
  * Register views.
  */
  public function registerViews(): void
  {
    $viewPath = resource_path('views/modules/'.$this->nameLower);
    $sourcePath = module_path($this->name, 'resources/views');

    $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

    $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

    Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);
  }

  /**
  * Get the services provided by the provider.
  */
  public function provides(): array
  {
    return [];
  }

  private function getPublishableViewPaths(): array
  {
    $paths = [];
    foreach (config('view.paths') as $path) {
      if (is_dir($path.'/modules/'.$this->nameLower)) {
        $paths[] = $path.'/modules/'.$this->nameLower;
      }
    }

    return $paths;
  }
}