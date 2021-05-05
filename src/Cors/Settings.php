<?php declare(strict_types=1);

namespace Circli\ApiBase\Cors;

class Settings extends \Neomerx\Cors\Strategies\Settings
{
	public function __construct()
	{
		$this->loadDefaults();
	}

	protected function loadDefaults(): void
	{
		$this
			->setServerOrigin('https', $_SERVER['HTTP_HOST'], 443) // todo need to replace $_SERVER
			->setPreFlightCacheMaxAge(0)
			->disableCheckHost()
			->setAllowedMethods(['GET', 'PUT', 'POST', 'DELETE'])
			->enableAllOriginsAllowed()
			->enableAllMethodsAllowed()
			->setCredentialsSupported()
			->disableAddAllowedMethodsToPreFlightResponse()
			->setAllowedHeaders([
				'x-api-key',
				'content-type',
				'accept',
				'origin',
				'authorization',
				'authentication',
			])
			->setExposedHeaders([
				'x-api-key',
				'content-type',
				'accept',
				'origin',
				'authorization',
				'authentication',
				'location',
			]);
	}
}
