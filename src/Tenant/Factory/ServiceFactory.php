<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant\Factory;

/**
 * @template T
 */
interface ServiceFactory
{
	/**
	 * @return T
	 */
	public function create(mixed ...$args);

	public function reload(): void;
}
