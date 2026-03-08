<?php

return [
  'telegram' => [
    'bot' => env('TELEGRAM_BOT_USERNAME'), // The bot's username
    'client_id' => null,
    'client_secret' => env('TELEGRAM_BOT_TOKEN'),
    'redirect' => env('TELEGRAM_REDIRECT_URI', '/auth/telegram/callback'),
  ],];