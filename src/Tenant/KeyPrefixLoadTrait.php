<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use AsyncAws\Ssm\Input\GetParametersByPathRequest;
use AsyncAws\Ssm\SsmClient;
use Circli\TenantExtension\Tenant;

/**
 * @property KeyPrefixSecretsProvider $secretsManager
 * @property string $searchKey
 */
trait KeyPrefixLoadTrait
{
	protected function loadTenantConfig(Tenant $tenant): void
	{
		$this->secretsManager->setPrefix('/' . $this->searchKey . '/tenant-' . $tenant->getId()->toString());
	}
}
