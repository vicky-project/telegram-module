<?php

namespace Modules\Telegram\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegisterAppEvent
{
  use Dispatchable,
  SerializesModels;

  public array $app;

  public function __construct(array $app) {
    $this->app = $app;
  }
}