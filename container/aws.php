<?php declare(strict_types=1);

use AsyncAws\Core\AwsClientFactory;
use AsyncAws\Ssm\SsmClient;
use Circli\Core\Config;
use Psr\Container\ContainerInterface;

return [
	SsmClient::class => static function(ContainerInterface $container) {
		return $container->get(AwsClientFactory::class)->ssm();
	},
	AwsClientFactory::class => static function(ContainerInterface $container) {
		$appConfig = $container->get(Config::class);
		if ($appConfig->has('aws')) {
			return new AwsClientFactory($appConfig->get('aws')['core']);
		}

		return new AwsClientFactory();
	},
];
