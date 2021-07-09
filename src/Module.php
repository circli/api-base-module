<?php declare(strict_types=1);

namespace Circli\ApiBase;

use AsyncAws\Ssm\SsmClient;
use Circli\Contracts\ModuleInterface;
use Circli\Contracts\PathContainer;
use Circli\Core\ConditionalDefinition;

final class Module implements ModuleInterface
{
	/**
	 * @return string[]|callable[]|ConditionalDefinition[]
	 */
	public function configure(PathContainer $pathContainer = null): array
	{
		$configFolder = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'container';
		return [
			$configFolder . DIRECTORY_SEPARATOR . 'tenant.php',
			$configFolder . DIRECTORY_SEPARATOR . 'auth.php',
			$configFolder . DIRECTORY_SEPARATOR . 'cors.php',
			$configFolder . DIRECTORY_SEPARATOR . 'event-handlers.php',
			$configFolder . DIRECTORY_SEPARATOR . 'adr.php',
		];
	}
}
