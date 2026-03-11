<?php

namespace Tito10047\MigrationBackup\Resolver;

use Doctrine\Persistence\ManagerRegistry;
use Tito10047\MigrationBackup\Dto\ConnectionParams;

class ConnectionResolver implements ConnectionResolverInterface {
	public function __construct(
		private readonly ManagerRegistry $registry
	) {}

	public function resolve(string $connectionName): ConnectionParams {
		/** @var \Doctrine\DBAL\Connection $connection */
		$connection = $this->registry->getConnection($connectionName);
		$params     = $connection->getParams();

		return new ConnectionParams(
			(string)($params['host'] ?? 'localhost'),
			(string)($params['port'] ?? '3306'),
			(string)($params['dbname'] ?? ''),
			(string)($params['user'] ?? ''),
			(string)($params['password'] ?? ''),
			(string)($params['driver'] ?? ''),
		);
	}
}
