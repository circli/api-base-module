<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use Circli\TenantExtension\TenantFactory;

interface Factory extends TenantFactory
{
	/**
	 * @param mixed ...$args
	 * @return mixed
	 */
	public function create(string $service, ...$args);
}
