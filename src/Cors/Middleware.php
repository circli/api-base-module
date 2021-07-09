<?php declare(strict_types=1);

namespace Circli\ApiBase\Cors;

use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\Constants\CorsRequestHeaders;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Middleware implements MiddlewareInterface
{
	public function __construct(
		private ResponseFactoryInterface $responseFactory,
		private Analyzer $analyzer,
	) {}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if ($request->hasHeader(CorsRequestHeaders::ORIGIN)) {
			$origin = $request->getHeaderLine(CorsRequestHeaders::ORIGIN);
			if ($origin === 'file://' || trim($origin) === '') {
				$request = $request->withHeader(CorsRequestHeaders::ORIGIN, 'file://android');
			}
		}

		$cors = $this->analyzer->analyze($request);
		switch ($cors->getRequestType()) {
			case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
			case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
			case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
				$response = $this->responseFactory->createResponse(400);
				foreach ($cors->getResponseHeaders() as $key => $value) {
					$response = $response->withHeader($key, $value);
				}
				$response->getBody()->write('Invalid cors request');
				return $response;
			case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
				$response = $this->responseFactory->createResponse(200);
				foreach ($cors->getResponseHeaders() as $key => $value) {
					$response = $response->withHeader($key, $value);
				}
				return $response;
			case AnalysisResultInterface::ERR_NO_HOST_HEADER:
			case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
				return $handler->handle($request);
			default:
				// actual CORS request
				$response = $handler->handle($request);

				foreach ($cors->getResponseHeaders() as $key => $value) {
					$response = $response->withHeader($key, $value);
				}

				return $response;
		}
	}
}
