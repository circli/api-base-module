<?php declare(strict_types=1);

namespace Circli\ApiBase;

use Polus\Adr\Interfaces\Resolver;
use Polus\Adr\Interfaces\Responder;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

final class ActionResolver implements Resolver
{
	public function __construct(
		private ContainerInterface $container,
	) {}

	public function resolve(?string $key): callable
	{
		if (\is_callable($key)) {
			return $key;
		}
		if (!$key) {
			throw new class('Can\'t resolve key') extends \RuntimeException implements NotFoundExceptionInterface {};
		}
		try {
			$callable = $this->container->get($key);
			if ($callable instanceof LoggerAwareInterface) {
				$callable->setLogger($this->container->get(LoggerInterface::class));
			}
			return $callable;
		}
		catch (NotFoundExceptionInterface $e) {
			if (\is_string($key) && class_exists($key)) {
				return new $key();
			}
		}
		throw new class($key . ' not found') extends \RuntimeException implements NotFoundExceptionInterface {};
	}

	public function resolveResponder(?string $responder): Responder
	{
		if ($responder === null) {
			$responder = ApiResponder::class;
		}
		$responder = $this->resolve($responder);
		if (!$responder instanceof Responder) {
			throw new class extends \RuntimeException implements NotFoundExceptionInterface {};
		}
		return $responder;
	}
}
