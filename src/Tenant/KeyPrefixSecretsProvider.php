<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use Stefna\SecretsManager\Provider\ProviderInterface;
use Stefna\SecretsManager\Values\Secret;

final class KeyPrefixSecretsProvider implements ProviderInterface
{
	public function __construct(
		private ProviderInterface $provider,
		private string $prefix = '',
	) {}

	public function setPrefix(string $prefix): void
	{
		$this->prefix = $prefix;
	}

	public function getPrefix(): string
	{
		return $this->prefix;
	}

	public function getSecret(string $key, ?array $options = []): Secret
	{
		return $this->provider->getSecret($this->prefix . $key, $options);
	}

	public function putSecret(Secret $secret, ?array $options = []): Secret
	{
		$prefixedSecret = new Secret(
			$this->prefix . $secret->getKey(),
			$secret->getValue(),
			$secret->getMetadata(),
		);
		return $this->provider->putSecret($prefixedSecret, $options);
	}

	public function deleteSecret(Secret $secret, ?array $options = []): void
	{
		$prefixedSecret = new Secret($this->prefix . $secret->getKey(), '');
		$this->provider->deleteSecret($prefixedSecret, $options);
	}
}
