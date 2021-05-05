<?php declare(strict_types=1);

namespace Circli\ApiBase;

use Circli\ApiAuth\JwtHandler;
use Circli\ApiAuth\Provider\ApiAuthProvider;
use Circli\WebCore\Common\Payload\LocationAwareInterface;
use Circli\WebCore\DomainStatusToHttpStatus;
use PayloadInterop\DomainPayload;
use Polus\Adr\Interfaces\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ApiResponder implements Responder
{
	public function __construct(
		private JwtHandler $jwtHandler,
	) {}

	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		DomainPayload $payload
	): ResponseInterface {
		$responseCode = DomainStatusToHttpStatus::httpCode($payload);
		$response = $response->withStatus($responseCode);

		if ($payload instanceof LocationAwareInterface && in_array($responseCode, [201, 302, 303], true)) {
			$location = $payload->getLocation($request);
			if ($location) {
				$token = $this->jwtHandler->createQueryAccessTokenFromRequest($location, $request);
				$location .= '?' . ApiAuthProvider::REDIRECT_ACCESS_KEY . '=' . $token->toString();

				return $response->withHeader('Location', $location);
			}
		}
		$response = $response->withHeader('Content-Type', 'application/json');
		// Overwrite the body instead of making a copy and dealing with the stream.
		try {
			$response->getBody()->write((string)json_encode($payload->getResult(), JSON_THROW_ON_ERROR));
		}
		catch (\JsonException $e) {
			$response->getBody()->write((string)json_encode([
				'messages' => $e->getMessage(),
				'code' => 'INVALID_RESPONSE_BODY',
			]));
		}

		return $response;
	}
}
