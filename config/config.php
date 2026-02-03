<?php

return [
	"name" => "Telegram",
	"token" => env("TELEGRAM_BOT_TOKEN"),
	"username" => env("TELEGRAM_BOT_USERNAME"),
	"redirect_url" => env(
		"TELEGRAM_AUTH_REDIRECT_URL",
		url("/telegram/redirect")
	),

	/*
	| Where to redirect after success login using telegram. Using route name.
	*/
	"auth_redirect_to_route" => "cores.dashboard",

	"webhook_url" => env("TELEGRAM_WEBHOOK_URL", "/api/telegram/webhook"),
	"webhook_secret" => env("TELEGRAM_WEBHOOK_SECRET"),
	"admin" => env("TELEGRAM_ADMINS", ""), // String of id with comma separated
];
