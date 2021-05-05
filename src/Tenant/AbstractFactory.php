<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant;

use Atlas\Orm\Atlas;
use Atlas\Orm\Transaction\AutoCommit;
use Atlas\Pdo\Connection;
use Circli\Core\Config;
use Circli\Core\Environment;
use Circli\Database\Service as DatabaseService;
use Circli\Database\WhereUuid;
use Circli\TenantExtension\Tenant;
use Circli\TenantExtension\TenantId;
use Circli\TenantExtension\TenantRepository;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

abstract class AbstractFactory implements Factory
{
	protected ?Tenant $tenant = null;
	/** @var array<callable> */
	protected array $factories = [];
	protected Environment $environment;

	private ?DatabaseService $databaseService = null;

	public function __construct(
		protected Config $config,
		protected TenantRepository $tenantRepository,
		protected LoggerInterface $logger
	) {
		$this->environment = $config->get('app.mode');

		$this->factories[DatabaseService::class] = function () {
			if (!$this->databaseService) {
				$connection = $this->createConnection();
				WhereUuid::preCalculateDriver($connection);

				$this->databaseService = new DatabaseService(Atlas::new($connection, AutoCommit::class), $connection);
			}
			return $this->databaseService;
		};

		$this->initFactories();
	}

	protected function initFactories(): void
	{
	}

	public function getAllTenants(): array
	{
		return $this->tenantRepository->findAll();
	}

	public function configureTenant(Tenant $tenant): void
	{
		$this->tenant = $tenant;
		$this->loadTenantConfig($tenant);
	}

	public function create(string $service, ...$args)
	{
		if (!isset($this->factories[$service])) {
			throw new \BadMethodCallException('No factory for service found');
		}
		$factory = $this->factories[$service];

		return $factory(...$args);
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

	protected function createConnection(): Connection
	{
		$config = $this->config;
		$dsn = $config->get('db.dsn');
		$username = $config->get('db.username');
		$password = $config->get('db.password');

		return Connection::new($dsn, $username, $password);
	}

	abstract protected function loadTenantConfig(Tenant $tenant): void;

	/**
	 * @return array<string, mixed>
	 */
	abstract protected function getConfig(string $section): array;
}
