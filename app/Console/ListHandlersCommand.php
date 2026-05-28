<?php
namespace Modules\Telegram\Console;

use Illuminate\Console\Command;
use Modules\Telegram\Services\Handlers\CommandDispatcher;
use Modules\Telegram\Services\Handlers\CallbackHandler;
use Modules\Telegram\Services\Handlers\ReplyDispatcher;
use Modules\Telegram\Services\Handlers\LocationDispatcher;
use Modules\Telegram\Services\Handlers\InlineQueryHandler;

class ListHandlersCommand extends Command
{
  protected $signature = 'app:telegram-handlers';
  protected $description = 'Menampilkan semua handler yang terdaftar di bot Telegram';

  public function handle(
    CommandDispatcher $commandDispatcher,
    CallbackHandler $callbackHandler,
    ReplyDispatcher $replyDispatcher,
    LocationDispatcher $locationDispatcher,
    InlineQueryHandler $inlineQueryHandler
  ): int {
    // 1. Command Handlers
    $this->info('📋 Command Handlers');
    $commands = $commandDispatcher->getCommands();
    if (empty($commands)) {
      $this->line('  <fg=gray>Tidak ada command terdaftar.</>');
    } else {
      $rows = [];
      foreach ($commands as $name => $command) {
        $rows[] = ['/' . $name,
          $command->getDescription()];
      }
      $this->table(['Command', 'Description'], $rows);
    }

    // 2. Callback Handlers
    $this->newLine();
    $this->info('📋 Callback Handlers');
    $callbacks = $callbackHandler->getHandlers();
    if (empty($callbacks)) {
      $this->line('  <fg=gray>Tidak ada callback handler terdaftar.</>');
    } else {
      $rows = [];
      foreach ($callbacks as $pattern => $handler) {
        $rows[] = [$handler->getName(),
          $pattern];
      }
      $this->table(['Name', 'Pattern'], $rows);
    }

    // 3. Reply Handlers
    $this->newLine();
    $this->info('📋 Reply Handlers');
    $replies = $replyDispatcher->getHandlers();
    if (empty($replies)) {
      $this->line('  <fg=gray>Tidak ada reply handler terdaftar.</>');
    } else {
      $rows = [];
      foreach ($replies as $identifier => $handler) {
        $rows[] = [$identifier,
          get_class($handler)];
      }
      $this->table(['Identifier', 'Class'], $rows);
    }

    // 4. Location Handlers
    $this->newLine();
    $this->info('📋 Location Handlers');
    $locations = $locationDispatcher->getHandlers();
    if (empty($locations)) {
      $this->line('  <fg=gray>Tidak ada location handler terdaftar.</>');
    } else {
      $rows = [];
      foreach ($locations as $name => $handler) {
        $rows[] = [$name,
          get_class($handler)];
      }
      $this->table(['Name', 'Class'], $rows);
    }

    // 5. Inline Handlers
    $this->newLine();
    $this->info('📋 Inline Handlers');
    $inlines = $inlineQueryHandler->getHandlers();
    if (empty($inlines)) {
      $this->line('  <fg=gray>Tidak ada inline handler terdaftar.</>');
    } else {
      $rows = [];
      foreach ($inlines as $pattern => $handler) {
        $rows[] = [$handler->getName(),
          $pattern];
      }
      $this->table(['Name', 'Pattern'], $rows);
    }

    return 0;
  }
}