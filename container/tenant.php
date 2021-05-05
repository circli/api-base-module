<?php declare(strict_types=1);

use Circli\ApiBase\Tenant\Factory;
use Circli\Database\Service as DatabaseService;
use Circli\TenantExtension\TenantFactory;
use Psr\Container\ContainerInterface;
use function DI\get;

return [
	TenantFactory::class => get(Factory::class),
	DatabaseService::class => static function (ContainerInterface $container) {
		$factory = $container->get(Factory::class);

		return $factory->create(DatabaseService::class);
	},
];
