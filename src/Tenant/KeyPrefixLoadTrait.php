<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use AsyncAws\Ssm\Input\GetParametersByPathRequest;
use AsyncAws\Ssm\SsmClient;
use Circli\TenantExtension\Tenant;

trait AwsSsmLoadTrait
{
	private SsmClient $ssmClient;
	private string $searchKey;
	private array $tenantConfig = [];

	protected function loadTenantConfig(Tenant $tenant): void
	{
		$tmpFolder = sys_get_temp_dir();
		$cacheFileName = $tmpFolder . '/' . $tenant->getId()->toString();
		if (file_exists($cacheFileName)) {
			$this->tenantConfig = require $cacheFileName;
		}
		else {
			$searchPath = '/' . $this->searchKey . '/tenant-' . $tenant->getId()->toString();
			$parameters = $this->ssmClient->getParametersByPath(new GetParametersByPathRequest([
				'Path' => $searchPath,
				'Recursive' => true,
				'WithDecryption' => true,
			]));

			$secrets = [
				'dev' => [],
				'prod' => [],
			];
			foreach ($parameters as $parameter) {
				$name = trim(str_replace($searchPath, '', $parameter->getName()), '/');
				[$stage, $section, $key] = explode('/', $name, 3);

				if (!isset($secrets[$stage][$section])) {
					$secrets[$stage][$section] = [];
				}

				$secrets[$stage][$section][$key] = $parameter->getValue();
			}

			$content = '<?php return ';
			$content .= var_export($secrets, true) . ';';

			file_put_contents($cacheFileName, $content);

			$this->tenantConfig = $secrets;
		}
	}
}
