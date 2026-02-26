<?php

namespace Modules\Telegram\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\Telegram\Services;

class TelegramServiceProvider extends ServiceProvider
{
	use PathNamespace;

	protected string $name = "Telegram";

	protected string $nameLower = "telegram";

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
		$this->loadMigrationsFrom(module_path($this->name, "database/migrations"));

		if (
			config($this->nameLower . ".hooks.enabled", false) &&
			class_exists($class = config($this->nameLower . ".hooks.service"))
		) {
			$this->registerHooks($class);
		}

		$this->app["router"]->aliasMiddleware(
			"telegram",
			\Modules\Telegram\Http\Middleware\VerifyTelegramData::class,
		);

		Auth::extend("telegram", function ($app, $name, array $config) {
			return new \Modules\Telegram\Auth\TelegramGuard(
				Auth::createUserProvider($config["provider"]),
				$app["request"],
				$app->make("session"),
				$app->make(Services\TelegramService::class),
			);
		});

		Config::set("auth.guards.telegram", [
			"driver" => "telegram",
			"provider" => "users",
		]);
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
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->app->register(EventServiceProvider::class);
		$this->app->register(RouteServiceProvider::class);

		$this->app->singleton(Services\Handlers\CommandDispatcher::class, function (
			$app,
		) {
			$dispatcher = new Services\Handlers\CommandDispatcher();
			$this->registerCommandHandlers($dispatcher);
			$this->registerMiddlewares($dispatcher);
			return $dispatcher;
		});

		$this->app->singleton(Services\Handlers\CallbackHandler::class, function (
			$app,
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

		$this->app->bind(Services\Handlers\MessageHandler::class, function ($app) {
			return new Services\Handlers\MessageHandler(
				$this->app->make(Services\Handlers\CommandDispatcher::class),
				$this->app->make(Services\Handlers\ReplyDispatcher::class),
				$this->app->make(Services\Support\TelegramApi::class),
			);
		});

		$this->app->singleton(Services\TelegramAuthService::class);
	}

	protected function registerHooks($hookService): void
	{
		// Add telegram section in user profile settings
		$hookService::add(
			"social-accounts",
			function ($data) {
				if (Auth::check()) {
					$user = Auth::user();
					$hasSocialAccount = $user->socialAccounts->isNotEmpty();

					return view("telegram::partials.telegram_info", [
						"telegram" => $hasSocialAccount
							? $user
								->socialAccounts()
								->byProvider("telegram")
								->first()->providerable
							: [],
					])->render();
				}

				return "";
			},
			10,
		);

		// Add telegram button login in auth form
		$hookService::add(
			"auth.socials",
			function ($data) {
				$service = app(Services\TelegramService::class);
				if ($service->checkDeviceKnown()) {
					return view("telegram::auth.button")->render();
				}

				return "";
			},
			10,
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
		$langPath = resource_path("lang/modules/" . $this->nameLower);

		if (is_dir($langPath)) {
			$this->loadTranslationsFrom($langPath, $this->nameLower);
			$this->loadJsonTranslationsFrom($langPath);
		} else {
			$this->loadTranslationsFrom(
				module_path($this->name, "lang"),
				$this->nameLower,
			);
			$this->loadJsonTranslationsFrom(module_path($this->name, "lang"));
		}
	}

	/**
	 * Register config.
	 */
	protected function registerConfig(): void
	{
		$configPath = module_path(
			$this->name,
			config("modules.paths.generator.config.path"),
		);

		if (is_dir($configPath)) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($configPath),
			);

			foreach ($iterator as $file) {
				if ($file->isFile() && $file->getExtension() === "php") {
					$config = str_replace(
						$configPath . DIRECTORY_SEPARATOR,
						"",
						$file->getPathname(),
					);
					$config_key = str_replace(
						[DIRECTORY_SEPARATOR, ".php"],
						[".", ""],
						$config,
					);
					$segments = explode(".", $this->nameLower . "." . $config_key);

					// Remove duplicated adjacent segments
					$normalized = [];
					foreach ($segments as $segment) {
						if (end($normalized) !== $segment) {
							$normalized[] = $segment;
						}
					}

					$key =
						$config === "config.php"
							? $this->nameLower
							: implode(".", $normalized);

					$this->publishes(
						[$file->getPathname() => config_path($config)],
						"config",
					);
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
		$viewPath = resource_path("views/modules/" . $this->nameLower);
		$sourcePath = module_path($this->name, "resources/views");

		$this->publishes(
			[$sourcePath => $viewPath],
			["views", $this->nameLower . "-module-views"],
		);

		$this->loadViewsFrom(
			array_merge($this->getPublishableViewPaths(), [$sourcePath]),
			$this->nameLower,
		);

		Blade::componentNamespace(
			config("modules.namespace") . "\\" . $this->name . "\\View\\Components",
			$this->nameLower,
		);
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
		foreach (config("view.paths") as $path) {
			if (is_dir($path . "/modules/" . $this->nameLower)) {
				$paths[] = $path . "/modules/" . $this->nameLower;
			}
		}

		return $paths;
	}
}
