<?php

namespace Modules\Telegram\Contracts;

interface AppRegistry
{
  public function registerApp(): array;
}