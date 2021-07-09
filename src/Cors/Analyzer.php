<?php declare(strict_types=1);

namespace Circli\ApiBase\Cors;

use Neomerx\Cors\Analyzer as BaseAnalyzer;
use Neomerx\Cors\Contracts\AnalysisStrategyInterface;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Neomerx\Cors\Contracts\Constants\SimpleRequestHeaders;
use Neomerx\Cors\Contracts\Constants\SimpleRequestMethods;
use Neomerx\Cors\Contracts\Factory\FactoryInterface;

final class Analyzer extends BaseAnalyzer
{
	/** @var array<string, bool> */
	protected const SIMPLE_METHODS = [
		SimpleRequestMethods::GET  => true,
		SimpleRequestMethods::HEAD => true,
		SimpleRequestMethods::POST => true,
	];

	/** @var string[] */
	protected const SIMPLE_LC_HEADERS_EXCLUDING_CONTENT_TYPE = [
		SimpleRequestHeaders::LC_ACCEPT,
		SimpleRequestHeaders::LC_ACCEPT_LANGUAGE,
		SimpleRequestHeaders::LC_CONTENT_LANGUAGE,
	];

	public function __construct(
		private AnalysisStrategyInterface $strategyGetter,
		FactoryInterface $factory,
	) {
		parent::__construct($strategyGetter, $factory);
	}

	public function getStrategy(): AnalysisStrategyInterface
	{
		return $this->strategyGetter;
	}

	public static function instance(AnalysisStrategyInterface $strategy): AnalyzerInterface
	{
		return new Analyzer($strategy, Analyzer::getFactory());
	}
}
