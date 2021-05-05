<?php declare(strict_types=1);

namespace Circli\ApiBase\EventHandlers;

use Circli\TenantExtension\Events\TenantLoaded;
use Circli\TenantExtension\TenantFactory;

final class ConfigureTenant
{
	public function __construct(
		private TenantFactory $tenantFactory,
	) {}

	public function __invoke(TenantLoaded $event): void
	{
		$this->tenantFactory->configureTenant($event->getTenant());
	}
}
