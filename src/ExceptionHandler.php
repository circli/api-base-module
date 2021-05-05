<?php declare(strict_types=1);

namespace Circli\ApiBase;

use Circli\ApiBase\Common\InvalidPayload;
use Circli\WebCore\Common\Payload\AccessDeniedPayload;
use Circli\WebCore\Common\Payload\InvalidArgumentPayload;
use Circli\WebCore\Common\Payload\NotFoundPayload;
use Circli\WebCore\Exception\AccessDenied;
use Circli\WebCore\Exception\NotFoundInterface;
use PayloadInterop\DomainPayload;
use Polus\Adr\ExceptionDomainPayload;
use Polus\Adr\Interfaces\ExceptionHandler as BaseExceptionHandler;
use Psr\Http\Message\ResponseInterface;

final class ExceptionHandler implements BaseExceptionHandler
{
	public function handle(\Throwable $e): DomainPayload|ResponseInterface
	{
		if ($e instanceof \BadMethodCallException) {
			return new InvalidPayload($e);
		}
		if ($e instanceof NotFoundInterface) {
			return new NotFoundPayload($e);
		}
		if ($e instanceof AccessDenied) {
			return new AccessDeniedPayload();
		}
		if ($e instanceof \InvalidArgumentException) {
			return new InvalidArgumentPayload($e);
		}
		if ($e instanceof \DomainException) {
			return new ExceptionDomainPayload($e);
		}
		throw $e;
	}
}
