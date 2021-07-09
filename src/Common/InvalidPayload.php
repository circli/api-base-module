<?php declare(strict_types=1);

namespace Circli\ApiBase\Common;

use Circli\WebCore\DomainStatus;
use Circli\WebCore\Exception\MessageCodeAware;
use PayloadInterop\DomainPayload;

final class InvalidPayload implements DomainPayload
{
	public function __construct(
		private \BadMethodCallException $exception,
	) {}

	public function getStatus(): string
	{
		return DomainStatus::INVALID;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getResult(): array
	{
		$result = [
			'messages' => $this->exception->getMessage(),
		];
		if ($this->exception instanceof MessageCodeAware) {
			$result['code'] = $this->exception->getMessageCode();
		}
		return $result;
	}
}
