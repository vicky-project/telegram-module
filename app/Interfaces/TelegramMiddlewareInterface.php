<?php
namespace Modules\Telegram\Interfaces;

interface TelegramMiddlewareInterface
{
	/**
	 * Handle the middleware
	 *
	 * @param array $context Command context
	 * @param callable $next Next middleware or command handler
	 * @return mixed
	 */
	public function handle(array $context, callable $next);
}
