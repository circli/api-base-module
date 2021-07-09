<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use Circli\TenantExtension\TenantId;
use Ramsey\Uuid\UuidInterface;

final class DefaultTenantId implements TenantId
{
	public function __construct(
		private UuidInterface $uuid,
	) {}

	public function toString(): string
	{
		return $this->uuid->toString();
	}
}
