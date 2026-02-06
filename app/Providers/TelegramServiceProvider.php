<?php

namespace Modules\Telegram\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Modules\Telegram\Services\TelegramService;
use Modules\Telegram\Services\Handlers\CommandDispatcher;
use Modules\Telegram\Services\Handlers\MessageHandler;
use Modules\Telegram\Services\Handlers\Commands\HelpCommand;
use Modules\Telegram\Services\Handlers\Commands\StartCommand;
use Modules\Telegram\Services\Handlers\Commands\UnlinkCommand;
use Modules\Telegram\Services\Middlewares\AuthMiddleware;
use Modules\Telegram\Services\Support\TelegramApi;

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
			class_exists($class = config($this->nameLower . ".hooks.class"))
		) {
			$this->registerHooks($class);
		}
	}

	// Register middleware
	protected function registerMiddlewares(CommandDispatcher $dispatcher): void
	{
		$dispatcher->registerMiddleware(
			"auth",
			new AuthMiddleware(
				$this->app->make(TelegramService::class),
				$this->app->make(TelegramApi::class)
			)
		);
	}

	// Register command
	protected function registerCommandHandlers(
		CommandDispatcher $dispatcher
	): void {
		$dispatcher->registerCommand(
			new StartCommand($this->app->make(TelegramApi::class))
		);
		$dispatcher->registerCommand(
			new HelpCommand(
				$this->app->make(TelegramApi::class),
				$this->app->make(CommandDispatcher::class)
			)
		);
		$dispatcher->registerCommand(
			new UnlinkCommand(
				$this->app->make(TelegramApi::class),
				$this->app->make(TelegramService::class)
			),
			["auth"]
		);
	}

	/**
	 * Register the service provider.
	 */
	public function register(): void
	{
		$this->app->register(EventServiceProvider::class);
		$this->app->register(RouteServiceProvider::class);

		$this->app->singleton(CommandDispatcher::class, function ($app) {
			$dispatcher = new CommandDispatcher();
			$this->registerCommandHandlers($dispatcher);
			$this->registerMiddlewares($dispatcher);
			return $dispatcher;
		});

		$this->app->bind(MessageHandler::class, function ($app) {
			return new MessageHandler(
				$this->app->make(CommandDispatcher::class),
				$this->app->make(TelegramApi::class)
			);
		});
	}

	protected function registerHooks($hookService): void
	{
		// Add telegram section in user profile settings
		$hookService::add(
			"social.accounts",
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
			10
		);

		// Add telegram button login in auth form
		$hookService::add(
			"auth.socials",
			function ($data) {
				$service = app(TelegramService::class);
				if ($service->checkDeviceKnown()) {
					return view("telegram::auth.button")->render();
				}

				return "";
			},
			10
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
				$this->nameLower
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
			config("modules.paths.generator.config.path")
		);

		if (is_dir($configPath)) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($configPath)
			);

			foreach ($iterator as $file) {
				if ($file->isFile() && $file->getExtension() === "php") {
					$config = str_replace(
						$configPath . DIRECTORY_SEPARATOR,
						"",
						$file->getPathname()
					);
					$config_key = str_replace(
						[DIRECTORY_SEPARATOR, ".php"],
						[".", ""],
						$config
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
						"config"
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
			["views", $this->nameLower . "-module-views"]
		);

		$this->loadViewsFrom(
			array_merge($this->getPublishableViewPaths(), [$sourcePath]),
			$this->nameLower
		);

		Blade::componentNamespace(
			config("modules.namespace") . "\\" . $this->name . "\\View\\Components",
			$this->nameLower
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
