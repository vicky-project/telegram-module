<?php

return [
  "notifications" => [
    "new-device" => [
      "template" => \Modules\Telegram\Notifications\NewDevice:class
    ],
    "failed-login" => [
      "template" => \Modules\Telegram\Notifications\FailedLogin:class
    ]
  ]
];