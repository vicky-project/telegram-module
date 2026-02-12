<?php

return [
	"name" => "Telegram",
	"backto_server_url" => "",

	"bot" => [
		"token" => env("TELEGRAM_BOT_TOKEN"),
		"username" => env("TELEGRAM_BOT_USERNAME"),
		"webhook_url" => env("TELEGRAM_WEBHOOK_URL", "/api/telegram/webhook"),
		"webhook_secret" => env("TELEGRAM_WEBHOOK_SECRET"),
		"admin" => env("TELEGRAM_ADMINS", ""), // String of id with comma separated
		"allowed_updates" => ["message", "callback_query", "edited_message"],
		"cache" => [
			"prefix" => env("TELEGRAM_BOT_CACHE_PREFIX", "telegram_reply:"),
			"duration" => env("TELEGRAM_BOT_CACHE_DURATION", 60), // in minutes
		],
	],

	"widgets" => [
		"size" => "large",
		"userpic" => false,
		// for user auth in setting profile page
		"redirect_url_auth" => env(
			"TELEGRAM_AUTH_REDIRECT_URL",
			url("/telegram/redirect-auth")
		),
		"route_after_auth" => "cores.dashboard", // Route name for after login with telegram

		// for form login
		"redirect_url_login" => env(
			"TELEGRAM_LOGIN_REDIRECT_URL",
			url("/telegram/redirect-login")
		),
	],

	"commander" => [
		// For EnsureUserLoginMiddleware to except from checking
		"no_auth" => ["start", "help", "link"],
	],

	// Inject telegram connect button and detail to User Management Module profile via hook core's.
	"hooks" => [
		"enabled" => true,
		"name" => "social.accounts",
		"service" => \Modules\Core\Services\HookService::class,
		"icon-provider" => "fontawesome", // fontawesome, bootstrap-icon
	],
];
