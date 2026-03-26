<?php

return [
  "notifications" => [
    'new-device' => [
      // Send the NewDevice notification
      'enabled' => env('NEW_DEVICE_NOTIFICATION', true),

      // Use torann/geoip to attempt to get a location
      // Defaults to false if geoip is not installed
      'location' => function_exists('geoip'),

      // The Notification class to send
      'template' => \Modules\Telegram\Notifications\NewDevice::class,

      // Rate limiting for notifications
      'rate_limit' => env('NEW_DEVICE_NOTIFICATION_RATE_LIMIT', 3),
      'rate_limit_decay' => env('NEW_DEVICE_NOTIFICATION_RATE_LIMIT_DECAY', 60),

      // Number of minutes after which a user is no longer considered "new"
      // Users connecting from multiple devices/locations shortly after registration won't trigger notifications
      'new_user_threshold_minutes' => env('NEW_DEVICE_NEW_USER_THRESHOLD_MINUTES', 1),
    ],
    'failed-login' => [
      // Send the FailedLogin notification
      'enabled' => env('FAILED_LOGIN_NOTIFICATION', false),

      // Use torann/geoip to attempt to get a location
      // Defaults to false if geoip is not installed
      'location' => function_exists('geoip'),

      // The Notification class to send
      'template' => \Modules\Telegram\Notifications\FailedLogin::class,

      // Rate limiting for notifications
      'rate_limit' => env('FAILED_LOGIN_NOTIFICATION_RATE_LIMIT', 5),
      'rate_limit_decay' => env('FAILED_LOGIN_NOTIFICATION_RATE_LIMIT_DECAY', 60),
    ],
    'suspicious-activity' => [
      // Send the SuspiciousActivity notification
      'enabled' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION', false),

      // Use torann/geoip to attempt to get a location
      // Defaults to false if geoip is not installed
      'location' => function_exists('geoip'),

      // The Notification class to send
      'template' => \Rappasoft\LaravelAuthenticationLog\Notifications\SuspiciousActivity::class,

      // Rate limiting for notifications
      'rate_limit' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT', 3),
      'rate_limit_decay' => env('SUSPICIOUS_ACTIVITY_NOTIFICATION_RATE_LIMIT_DECAY', 60),
    ],
  ]
];