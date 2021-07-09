<?php declare(strict_types=1);

namespace Circli\ApiBase\EventHandlers;

use Circli\ApiAuth\Events\AuthenticationFailed;
use Psr\Log\LoggerInterface;

final class LogAuthenticationFailure
{
	public function __construct(
		private LoggerInterface $logger,
	) {}

	public function __invoke(AuthenticationFailed $event): void
	{
		$this->logger->warning('Authentication failure', [
			'message' => $event->getException()->getMessage(),
			'exception' => $event->getException(),
		]);
	}
}
