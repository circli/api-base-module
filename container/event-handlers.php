<?php declare(strict_types=1);

use Circli\ApiBase\EventHandlers\ConfigureTenant;
use Circli\ApiBase\EventHandlers\LogAuthenticationFailure;
use Circli\EventDispatcher\ListenerProvider\ContainerListenerProvider;
use Circli\EventDispatcher\ListenerProvider\DefaultProvider;
use Circli\TenantExtension\Events\TenantLoaded;
use Fig\EventDispatcher\AggregateProvider;
use Psr\Container\ContainerInterface;
use Circli\ApiAuth\Events\AuthenticationFailed;
use function DI\decorate;

return [
	DefaultProvider::class => decorate(static function (
		DefaultProvider $provider,
		ContainerInterface $container
	): DefaultProvider {
		$provider->listen(TenantLoaded::class, $container->get(ConfigureTenant::class));
		$provider->listen(AuthenticationFailed::class, $container->get(LogAuthenticationFailure::class));

		return $provider;
	}),
	ContainerListenerProvider::class => static function (ContainerInterface $container) {
		$provider = new ContainerListenerProvider($container);
		$container->get(AggregateProvider::class)->addProvider($provider);
		return $provider;
	},
];
