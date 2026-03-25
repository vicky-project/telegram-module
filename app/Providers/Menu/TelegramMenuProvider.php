<?php
namespace Modules\Telegram\Providers\Menu;

use Modules\Telegram\Constants\Permission;
use Modules\CoreUI\Services\BaseMenuProvider;

class TelegramMenuProvider extends BaseMenuProvider
{
  protected array $config = [
    "group" => "server",
    "location" => "sidebar",
    "icon" => "bi bi-server",
    "order" => 2,
    "permission" => null,
  ];

  public function __construct() {
    $moduleName = "Telegram";
    parent::__construct($moduleName);
  }

  /**
  * Get all menus
  */
  public function getMenus(): array
  {
    return [
      $this->item([
        "title" => "Telegram Users",
        "icon" => "bi bi-telegram",
        "order" => 50,
        "route" => "admin.telegram.index",
        "permission" => Permission::VIEW_TELEGRAM_USERS,
      ]),
    ];
  }
}