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
	],

	"widgets" => [
		"size" => "large",
		"userpic" => false,
		// for user auth in setting profile page
		"redirect_url_auth" => env(
			"TELEGRAM_AUTH_REDIRECT_URL",
			url("/telegram/redirect-auth")
		),

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
		"class" => \Modules\Core\Services\HookService::class,
		"icon-provider" => "fontawesome", // fontawesome, bootstrap-icon
	],
];
