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
		"redirect_url" => env(
			"TELEGRAM_AUTH_REDIRECT_URL",
			url("/telegram/redirect")
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
