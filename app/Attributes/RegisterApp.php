<?php

namespace Modules\Telegram\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class RegisterApp
{
  public function __construct(
    public string $id,
    public string $name,
    public string $description,
    public ?string $iconUrl = null,
    public ?string $iconEmoji = null,
    public ?string $launchUrl = null
  ) {}
}