<?php declare(strict_types=1);

use Circli\ApiAuth\Middleware\ApiAuthenticationMiddleware;
use Circli\ApiBase\ActionResolver;
use Circli\ApiBase\ApiResponder;
use Circli\ApiBase\Cors\Middleware as CorsMiddleware;
use Circli\ApiBase\ExceptionHandler as ApiExceptionHandler;
use Circli\ApiBase\OpenApi\ValidationMiddleware as OpenApiValidationMiddleware;
use Circli\Core\Config;
use Circli\Extension\Auth\Web\Middleware\AuthAwareRouterMiddleware;
use Circli\Middlewares\ClientIp;
use Circli\Middlewares\JsonContentHandler;
use Circli\Middlewares\ResponseSigningMiddleware;
use Circli\WebCore\Common\Responder\ApiResponder as CoreApiResponder;
use Circli\WebCore\Middleware\Container as MiddlewareContainer;
use DI\NotFoundException;
use Laminas\Diactoros\UriFactory;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;
use League\OpenAPIValidation\PSR7\ValidatorBuilder;
use Polus\Adr\Interfaces\ExceptionHandler;
use Polus\Adr\Interfaces\Resolver;
use Polus\Adr\Interfaces\Responder;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Stefna\Logger\Middleware\LambdaContextMiddleware;
use Stefna\Logger\Middleware\LambdaProcessorMiddleware;
use function DI\autowire;
use function DI\decorate;

return [
	ExceptionHandler::class => autowire(ApiExceptionHandler::class),
	Resolver::class => static function (ContainerInterface $container) {
		return new ActionResolver($container);
	},
	'middlewares' => decorate(static function ($previous, ContainerInterface $container) {
		if (!$previous instanceof MiddlewareContainer) {
			$previous = new MiddlewareContainer((array) $previous);
		}
		$config = $container->get(Config::class);

		$previous->insert(LambdaContextMiddleware::class, 5);
		$previous->insert(LambdaProcessorMiddleware::class, 10);

		$doCors = true;
		if ($config->has('app.cors')) {
			$doCors = $config->get('app.cors') !== false;
		}
		if ($doCors) {
			$previous->addPreRouter(CorsMiddleware::class);
		}
		$previous->addPreRouter(ApiAuthenticationMiddleware::class);
		$previous->addPostRouter(JsonContentHandler::class);

		$previous->addPostRouter(OpenApiValidationMiddleware::class);

		$previous->addPostRouter(AuthAwareRouterMiddleware::class);
		$previous->addPostRouter(new class implements MiddlewareInterface {
			public function process(
				ServerRequestInterface $request,
				RequestHandlerInterface $handler
			): ResponseInterface {
				if ($request->getUri()->getQuery()) {
					parse_str(preg_replace_callback('/(?:^|(?<=&))[^=[]+/', function ($match) {
						// the replace is because bref has broken the querystring
						return bin2hex(str_replace('_', '.', urldecode($match[0])));
					}, $request->getUri()->getQuery()), $values);
					$request = $request->withQueryParams(
						array_combine(array_map('hex2bin', array_keys($values)), $values)
					);
				}
				return $handler->handle($request);
			}
		});
		return $previous;
	}),
	CoreApiResponder::class => autowire(ApiResponder::class),
	Responder::class => autowire(ApiResponder::class),
	UriFactoryInterface::class => DI\autowire(UriFactory::class),
	ClientIp::class => DI\autowire(),
	ResponseSigningMiddleware::class => DI\autowire(),
	JsonContentHandler::class => DI\autowire(),
	ValidatorBuilder::class => static function (ContainerInterface $container) {
		$builder = new ValidatorBuilder();
		if ($container->has(CacheItemPoolInterface::class)) {
			$builder->setCache($container->get(CacheItemPoolInterface::class));
		}

		$config = $container->get(Config::class);
		$schemaRoot = $config->get('openapi.base');
		if (is_file($schemaRoot)) {
			return $builder->fromJsonFile($schemaRoot);
		}
		elseif (file_exists($schemaRoot . '/full.json')) {
			return $builder->fromJsonFile($schemaRoot . '/full.json');
		}

		throw new NotFoundException('Can\'t create validator builder openapi specification not found');
	},
	ServerRequestValidator::class => static function (ContainerInterface $container) {
		return $container->get(ValidatorBuilder::class)->getServerRequestValidator();
	},
	ResponseValidator::class => static function (ContainerInterface $container) {
		return $container->get(ValidatorBuilder::class)->getResponseValidator();
	},
];
