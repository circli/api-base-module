<?php declare(strict_types=1);

use Circli\ApiBase\Cors\Analyzer;
use Circli\ApiBase\Cors\Settings;
use Circli\Core\Environment;
use Neomerx\Cors\Analyzer as NeomerxAnalyzer;
use Neomerx\Cors\Contracts\AnalysisStrategyInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use function DI\autowire;

return [
	AnalysisStrategyInterface::class => autowire(Settings::class),
	NeomerxAnalyzer::class => static function (ContainerInterface $container) {
		$analyzer = Analyzer::instance($container->get(AnalysisStrategyInterface::class));

		//only do logging on none production serv
		if (!$container->get(Environment::class)->is(Environment::PRODUCTION())) {
			$analyzer->setLogger($container->get(LoggerInterface::class));
		}
		return $analyzer;
	},
];
