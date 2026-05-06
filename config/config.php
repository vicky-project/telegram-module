<?php

return [
  'name' => 'Telegram',
  'base_url' => env('APP_URL').'/telegram',
  "logo_url" => env("LOGO_URL", "/homeserver.png"),

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

  // Inject telegram connect button and detail to User Management Module profile via hook core's.
  "hooks" => [
    "enabled" => false,
    "service" => \Modules\CoreUI\Services\UIService::class,
  ],
  "timezone" => env("TELEGRAM_TIMEZONE", "Asia/Makassar"),

  /**
  * Resolver id telegram in different model notifiable
  */
  "telegram_id_resolver" => \Modules\Telegram\Services\TelegramNotificationResolver::class,

  'app_cache_key' => 'telegram.registered_apps_final',
];