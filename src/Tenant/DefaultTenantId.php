<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use Circli\TenantExtension\TenantId;
use Ramsey\Uuid\UuidInterface;

final class DefaultTenantId implements TenantId
{
	private UuidInterface $uuid;

	public function __construct(UuidInterface $uuid)
	{
		$this->uuid = $uuid;
	}

	public function toString(): string
	{
		return $this->uuid->toString();
	}
}
