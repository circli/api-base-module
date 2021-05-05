<?php declare(strict_types=1);

namespace Circli\ApiBase\Exceptions;

use Circli\WebCore\Exception\MessageCodeAware;
use Circli\WebCore\Exception\MessageCodeTrait;

final class ValidationFailure extends \InvalidArgumentException implements MessageCodeAware
{
	use MessageCodeTrait;

	public function setMessageCode(string $code): void
	{
		$this->messageCode = $code;
	}
}
