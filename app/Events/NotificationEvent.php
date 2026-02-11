<?php
namespace Modules\Telegram\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class NotificationEvent
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	protected $chatId;
	protected $template;
	protected $data;
	protected $options;

	public function __construct(
		$chatId,
		string $template,
		array $data = [],
		array $options = []
	) {
		$this->chatId = $chatId;
		$this->template = $template;
		$this->data = $data;
		$this->options = $options;
	}

	public function getChatId()
	{
		return $this->chatId;
	}

	public function getTemplate(): string
	{
		return $this->template;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function getOptions(): array
	{
		return $this->options;
	}

	abstract public function getModule(): string;
}
