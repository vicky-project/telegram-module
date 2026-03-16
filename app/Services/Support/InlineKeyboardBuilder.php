<?php
namespace Modules\Telegram\Services\Support;

use Telegram\Bot\Keyboard\Keyboard;

class InlineKeyboardBuilder
{
  protected string $scope = 'global';
  protected ?string $module = null;
  protected ?string $entity = null;

  public function setScope(string $scope = 'global'): self
  {
    $this->scope = $scope;
    return $this;
  }

  public function setModule(?string $module): self
  {
    $this->module = $module;
    return $this;
  }

  public function setEntity(?string $entity): self
  {
    $this->entity = $entity;
    return $this;
  }

  /**
  * Buat tombol inline dengan callback data
  */
  protected function makeInlineButton(string $text, string $action, $value = null, array $params = []): array
  {
    $callbackData = GlobalCallbackBuilder::build(
      $this->scope,
      $this->module,
      $this->entity,
      $action,
      $value,
      $params
    );
    return Keyboard::inlineButton(['text' => $text, 'callback_data' => $callbackData]);
  }

  /**
  * Buat tombol inline dengan URL
  */
  protected function makeUrlButton(string $text, string $url): array
  {
    return Keyboard::inlineButton(['text' => $text, 'url' => $url]);
  }

  /**
  * Buat tombol reply (untuk reply keyboard)
  */
  protected function makeReplyButton(string $text, bool $requestLocation = false, bool $requestContact = false): array
  {
    $button = ['text' => $text];
    if ($requestLocation) {
      $button['request_location'] = true;
    }
    if ($requestContact) {
      $button['request_contact'] = true;
    }
    return $button;
  }

  /**
  * Build pagination keyboard (inline)
  */
  public function pagination(
    string $action,
    int $currentPage,
    int $totalPages,
    array $extraParams = []
  ): array {
    $row = [];

    // Previous button
    if ($currentPage > 1) {
      $prevParams = array_merge($extraParams, ['page' => $currentPage - 1]);
      $row[] = $this->makeInlineButton(
        '⬅️ Sebelumnya',
        $action,
        $extraParams['id'] ?? null,
        $prevParams
      );
    }

    // Current page indicator (tombol dummy tanpa aksi)
    $row[] = ['text' => "{$currentPage}/{$totalPages}",
      'callback_data' => 'noop'];

    // Next button
    if ($currentPage < $totalPages) {
      $nextParams = array_merge($extraParams, ['page' => $currentPage + 1]);
      $row[] = $this->makeInlineButton(
        'Berikutnya ➡️',
        $action,
        $extraParams['id'] ?? null,
        $nextParams
      );
    }

    // Kembalikan array baris (akan dibungkus inline_keyboard di luar)
    return [$row];
  }

  /**
  * Build confirmation keyboard (inline)
  */
  public function confirmation(
    string $action,
    ?string $itemId = null,
    string $confirmText = '✅ Ya',
    string $cancelText = '❌ Batal'
  ): array {
    $row = [
      $this->makeInlineButton($confirmText, $action . '_confirm', $itemId),
      $this->makeInlineButton($cancelText, $action . '_cancel', $itemId),
    ];
    return [$row];
  }

  /**
  * Build grid keyboard dari array items (inline)
  * @param array $items Item dengan format:
  *   - 'text' => string (wajib)
  *   - 'callback_data' => ['action' => string, 'value' => mixed] (opsional)
  *   - 'url' => string (opsional)
  *   - 'login_url' => array (opsional)
  * @param int $columns Jumlah kolom
  */
  public function grid(array $items, int $columns = 2): array
  {
    $keyboard = [];
    $row = [];

    foreach ($items as $item) {
      $text = $item['text'] ?? '';

      if (isset($item['callback_data'])) {
        $action = $item['callback_data']['action'] ?? '';
        $value = $item['callback_data']['value'] ?? null;
        $button = $this->makeInlineButton($text, $action, $value);
      } elseif (isset($item['url'])) {
        $button = $this->makeUrlButton($text, $item['url']);
      } elseif (isset($item['login_url'])) {
        // Login URL perlu struktur khusus
        $button = Keyboard::inlineButton([
          'text' => $text,
          'login_url' => $item['login_url'],
        ]);
      } else {
        // Tombol tanpa aksi (hanya teks) – jarang digunakan di inline
        $button = ['text' => $text,
          'callback_data' => 'noop'];
      }

      $row[] = $button;

      if (count($row) >= $columns) {
        $keyboard[] = $row;
        $row = [];
      }
    }

    if (!empty($row)) {
      $keyboard[] = $row;
    }

    return $keyboard;
  }

  /**
  * Build reply keyboard grid dengan dukungan tombol lokasi/kontak
  * @param array $items Item dengan format:
  *   - 'text' => string (wajib)
  *   - 'request_location' => bool (opsional)
  *   - 'request_contact' => bool (opsional)
  * @param int $columns Jumlah kolom
  * @param bool $oneTimeKeyboard Keyboard hanya muncul sekali
  * @param bool $resizeKeyboard Resize keyboard otomatis
  * @return array ReplyKeyboardMarkup siap pakai
  */
  public function replyKeyboardGrid(
    array $items,
    int $columns = 2,
    bool $oneTimeKeyboard = true,
    bool $resizeKeyboard = true
  ): array {
    $keyboard = [];
    $row = [];

    foreach ($items as $item) {
      $text = $item['text'] ?? '';
      $requestLocation = $item['request_location'] ?? false;
      $requestContact = $item['request_contact'] ?? false;

      $button = $this->makeReplyButton($text, $requestLocation, $requestContact);
      $row[] = $button;

      if (count($row) >= $columns) {
        $keyboard[] = $row;
        $row = [];
      }
    }

    if (!empty($row)) {
      $keyboard[] = $row;
    }

    return [
      'keyboard' => $keyboard,
      'one_time_keyboard' => $oneTimeKeyboard,
      'resize_keyboard' => $resizeKeyboard,
    ];
  }
}