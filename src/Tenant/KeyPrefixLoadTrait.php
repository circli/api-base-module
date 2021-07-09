<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use AsyncAws\Ssm\Input\GetParametersByPathRequest;
use AsyncAws\Ssm\SsmClient;
use Circli\TenantExtension\Tenant;

/**
 * @property KeyPrefixSecretsProvider $secretsManger
 */
trait KeyPrefixLoadTrait
{
	private string $searchKey;
	private array $tenantConfig = [];

	protected function loadTenantConfig(Tenant $tenant): void
	{
		$this->secretsManger->setPrefix('/' . $this->searchKey . '/tenant-' . $tenant->getId()->toString());
	}
}
