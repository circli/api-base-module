<?php declare(strict_types=1);

namespace Circli\ApiBase\OpenApi;

use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

final class ValidationMiddleware implements MiddlewareInterface
{
	public const REQUEST_VALIDATED = 'openapi:request-validated';
	public const REQUEST_ERROR = 'openapi:request-error';

	public function __construct(
		private ServerRequestValidator $requestValidator,
		private ResponseValidator $responseValidator,
		private LoggerInterface $logger,
	) {}

	/**
	 * Process an incoming server request.
	 *
	 * Processes an incoming server request in order to produce a response.
	 * If unable to produce the response itself, it may delegate to the provided
	 * request handler to do so.
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
	{
		$matchedOASOperation = null;
		try {
			// 1. Validate request
			$matchedOASOperation = $this->requestValidator->validate($request);
			$request = $request->withAttribute(self::REQUEST_VALIDATED, true);
		}
		catch (ValidationFailed|SchemaMismatch $e) {
			$previous = $e->getPrevious();
			$previousMessage = $previous ? $previous->getMessage() : '';
			$breadCrumb = '';
			$body = $request->getParsedBody();
			if ($previous instanceof SchemaMismatch) {
				$rawBreadCrumb = $previous->dataBreadCrumb();
				$arrayBreadCrumb = $rawBreadCrumb ? $rawBreadCrumb->buildChain() : [];
				$breadCrumb = implode('.', $arrayBreadCrumb);
				if ($arrayBreadCrumb) {
					foreach ($arrayBreadCrumb as $value) {
						if (!isset($body[$value])) {
							break;
						}
						$body = $body[$value];
					}
				}
			}
			$this->logger->info('Info about validation failure', [
				'breadCrumb' => $breadCrumb,
				'exception' => $previousMessage,
				'requestBody' => $body,
			]);
			$this->logger->error('Failed to validate request', [
				'message' => $e->getMessage(),
				'exception' => $e,
			]);
			$request = $request->withAttribute(self::REQUEST_VALIDATED, false);
			$request = $request->withAttribute(self::REQUEST_ERROR, $e);
		}
		catch (\Throwable $e) {
			$previous = $e->getPrevious();
			$this->logger->error('Unknown error in request validation', [
				'type' => get_class($e),
				'message' => $e->getMessage(),
				'requestBody' => $request->getParsedBody(),
				'previousMessage' => $previous ? $previous->getMessage() : null,
			]);
			throw $e;
		}

		// 2. Process request
		$response = $handler->handle($request);

		try {
			$responseLength = mb_strlen($response->getBody()->__toString());
			if ($matchedOASOperation && $responseLength < 10000) {
				// 3. Validate response
				$this->responseValidator->validate($matchedOASOperation, $response);
			}
		}
		catch (ValidationFailed $e) {
			$previous = $e->getPrevious();
			$context = [
				'message' => $e->getMessage(),
				'response' => $response->getBody()->__toString(),
				'previousMessage' => $previous ? $previous->getMessage() : null,
			];
			if ($previous instanceof SchemaMismatch) {
				$breadCrumb = $previous->dataBreadCrumb();
				if ($breadCrumb) {
					$context['breadCrumb'] = implode('.', $breadCrumb->buildChain());
				}
			}
			$this->logger->notice('Failed to validate response', $context);
		}
		catch (\Throwable $e) {
			$previous = $e->getPrevious();
			$this->logger->error('Unknown error in response validation', [
				'type' => get_class($e),
				'message' => $e->getMessage(),
				'responseCode' => $response->getStatusCode(),
				'responseHeaders' => $response->getHeaders(),
				'responseBody' => $response->getBody()->__toString(),
				'previousMessage' => $previous ? $previous->getMessage() : null,
			]);
		}
		return $response;
	}
}
