<?php

namespace Modules\Telegram\Providers;

<<<<<<< HEAD
use Modules\Telegram\Listeners\LinkTelegramOnLogin;
use Illuminate\Auth\Events\Login;
=======
>>>>>>> 984b245 (updates)
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
<<<<<<< HEAD
	/**
	 * The event handler mappings for the application.
	 *
	 * @var array<string, array<int, string>>
	 */
	protected $listen = [Login::class => [LinkTelegramOnLogin::class]];

	/**
	 * Indicates if events should be discovered.
	 *
	 * @var bool
	 */
	protected static $shouldDiscoverEvents = true;

	/**
	 * Configure the proper event listeners for email verification.
	 */
	protected function configureEmailVerification(): void
	{
	}
=======
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
>>>>>>> 984b245 (updates)
}
