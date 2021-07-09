<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use Circli\ApiBase\Tenant\Factory\DatabaseFactory;
use Circli\ApiBase\Tenant\Factory\ServiceFactory;
use Circli\Core\Config;
use Circli\Core\Environment;
use Circli\Database\Service as DatabaseService;
use Circli\TenantExtension\Tenant;
use Circli\TenantExtension\TenantId;
use Circli\TenantExtension\TenantRepository;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Stefna\SecretsManager\Provider\ProviderInterface;

abstract class AbstractFactory implements Factory
{
	/**
	 * @template T
	 * @var array<class-string<T>, ServiceFactory<T>>
	 */
	protected array $factories = [];
	protected ?Tenant $tenant = null;
	protected KeyPrefixSecretsProvider $secretsManager;

	public function __construct(
		protected Config $config,
		ProviderInterface $secretsManager,
		protected Environment $environment,
		protected TenantRepository $tenantRepository,
		protected LoggerInterface $logger,
	) {
		if (!$secretsManager instanceof KeyPrefixSecretsProvider) {
			$secretsManager = new KeyPrefixSecretsProvider($secretsManager);
		}
		$this->secretsManager = $secretsManager;
		$this->initFactories();
	}

	protected function initFactories(): void
	{
		$this->factories[DatabaseService::class] = new DatabaseFactory($this->secretsManager);
	}

	public function getAllTenants(): array
	{
		return $this->tenantRepository->findAll();
	}

	public function configureTenant(Tenant $tenant): void
	{
		$this->tenant = $tenant;
		$this->loadTenantConfig($tenant);
		foreach ($this->factories as $factory) {
			$factory->reload();
		}
	}

	/**
	 * @template C
	 * @param class-string<C> $service
	 * @return C
	 */
	public function create(string $service, ...$args)
	{
		if (!isset($this->factories[$service])) {
			throw new \BadMethodCallException(sprintf('No factory for "%s" found', $service));
		}
		/** @var ServiceFactory<C> $factory */
		$factory = $this->factories[$service];

		return $factory->create($args);
	}

	public function configureById(TenantId $tenantId): Tenant
	{
		$customer = $this->tenantRepository->findById($tenantId);
		$this->configureTenant($customer);
		return $customer;
	}

	public function getCurrentTenant(): Tenant
	{
		if (!$this->tenant) {
			throw new \BadMethodCallException('Tenant not loaded');
		}
		return $this->tenant;
	}

	public function createTenantId(string $tenant): TenantId
	{
		return new DefaultTenantId(Uuid::fromString($tenant));
	}

	abstract protected function loadTenantConfig(Tenant $tenant): void;
}
