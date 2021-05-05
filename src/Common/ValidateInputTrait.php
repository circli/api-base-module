<?php declare(strict_types=1);

namespace Circli\ApiBase\Common;

use Circli\ApiBase\Exceptions\ValidationFailure;
use Circli\ApiBase\OpenApi\ValidationMiddleware;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Psr\Http\Message\ServerRequestInterface;

trait ValidateInputTrait
{
	/**
	 * @throws \InvalidArgumentException
	 */
	protected function validateInput(ServerRequestInterface $request): void
	{
		if (!$request->getAttribute(ValidationMiddleware::REQUEST_VALIDATED)) {
			/** @var \Throwable $error */
			$error = $request->getAttribute(ValidationMiddleware::REQUEST_ERROR);
			if ($error->getPrevious()) {
				$error = $error->getPrevious();
			}
			$validationFailure = new ValidationFailure($error->getMessage(), $error->getCode(), $error);
			if ($error instanceof SchemaMismatch && $error->dataBreadCrumb()) {
				$classParts = explode('\\', get_class($error));
				$class = (string)array_pop($classParts);
				$const = preg_replace('/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', '_', $class);

				$validationFailure->setMessageCode(
					strtoupper(implode('_', $error->dataBreadCrumb()->buildChain()) . '_' . $const)
				);
			}

			throw $validationFailure;
		}
	}
}
