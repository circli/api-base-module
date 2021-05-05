<?php declare(strict_types=1);

use Circli\Extension\Auth\Voter\AccessCheckers;
use Circli\Extension\Auth\Voter\AuthRequiredActionVoter;
use Circli\Extension\Auth\Voter\DefaultAllowRouteVoter;
use Psr\Container\ContainerInterface;
use Circli\ApiAuth\AccessDenied\Responder as AccessDeniedResponder;
use Circli\ApiBase\ApiResponder;
use function DI\decorate;

return [
	AccessDeniedResponder::class => static function (ContainerInterface $container) {
		return new AccessDeniedResponder($container->get(ApiResponder::class));
	},
	AccessCheckers::class => decorate(static function ($previous, ContainerInterface $container) {
		if (!$previous instanceof AccessCheckers) {
			$previous = new AccessCheckers();
		}
		$previous->addVoter($container->get(AuthRequiredActionVoter::class));
		$previous->addVoter($container->get(DefaultAllowRouteVoter::class));

		return $previous;
	}),
	AuthRequiredActionVoter::class => decorate(function ($voter, ContainerInterface $container) {
		if (!$voter) {
			$voter = $container->get(AuthRequiredActionVoter::class);
		}

		return $voter;
	}),
];
