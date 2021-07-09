<?php declare(strict_types=1);

namespace Circli\ApiBase\Tenant\Factory;

use Atlas\Orm\Atlas;
use Atlas\Orm\Transaction\AutoCommit;
use Atlas\Pdo\Connection;
use Circli\Database\Service;
use Circli\Database\WhereUuid;
use Stefna\SecretsManager\Provider\ProviderInterface;

/**
 * @implements ServiceFactory<Service>
 */
final class DatabaseFactory implements ServiceFactory
{
	private ?Service $databaseService = null;

	public function __construct(
		private ProviderInterface $secretsManager,
	) {}

	public function create(mixed ...$args)
	{
		if (!$this->databaseService) {
			$dbConfig = $this->secretsManager->getSecret('/db');
			$dsn = $dbConfig['dsn'];
			if (!$dsn) {
				$dsnOpts = [
					'dbname=' . $dbConfig['name'],
					'host=' . $dbConfig['host'],
					'charset=utf8',
				];
				$dsn = 'mysql:' . implode(';', $dsnOpts);
			}
			$username = $dbConfig['username'];
			$password = $dbConfig['password'];

			$connection = Connection::new($dsn, $username, $password);
			WhereUuid::preCalculateDriver($connection);

			$this->databaseService = new Service(Atlas::new($connection, AutoCommit::class), $connection);
		}
		return $this->databaseService;
	}

	public function reload(): void
	{
		$this->databaseService = null;
	}
}
