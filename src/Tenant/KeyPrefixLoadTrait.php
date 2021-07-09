<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use AsyncAws\Ssm\Input\GetParametersByPathRequest;
use AsyncAws\Ssm\SsmClient;
use Circli\TenantExtension\Tenant;

/**
 * @property KeyPrefixSecretsProvider $secretsManager
 */
trait KeyPrefixLoadTrait
{
	private string $searchKey;

	protected function loadTenantConfig(Tenant $tenant): void
	{
		$this->secretsManager->setPrefix('/' . $this->searchKey . '/tenant-' . $tenant->getId()->toString());
	}
}
