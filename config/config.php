<?php

return [
	"name" => "Telegram",
	"token" => env("TELEGRAM_BOT_TOKEN"),
	"username" => env("TELEGRAM_BOT_USERNAME"),
	"redirect_url" => env(
		"TELEGRAM_AUTH_REDIRECT_URL",
		route("telegram.redirect")
	),

	/*
	| Where to redirect after success login using telegram. Using route name.
	*/
	"auth_redirect_to_route" => "cores.dashboard",
];
