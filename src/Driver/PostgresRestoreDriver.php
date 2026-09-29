<?php

namespace Tito10047\MigrationBackup\Driver;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\RestoreFailedException;

class PostgresRestoreDriver implements RestoreDriverInterface {
	public function __construct(
		private readonly Filesystem $fs,
		private readonly string     $psqlPath = 'psql',
	) {}

	public function supports(string $driverName): bool {
		return in_array($driverName, ['pdo_pgsql', 'pgsql', 'postgres'], true);
	}

	/**
	 * @return list<string>
	 */
	public function buildCommand(ConnectionParams $params, string $inputPath): array {
		return [
			$this->psqlPath,
			'-h', $params->host,
			'-p', $params->port,
			'-U', $params->user,
			'-d', $params->database,
			// Without this psql happily reports success after a failed statement.
			'-v', 'ON_ERROR_STOP=1',
			'-f', $inputPath,
		];
	}

	public function restore(ConnectionParams $params, string $inputPath): void {
		if (!$this->fs->exists($inputPath)) {
			throw new RestoreFailedException('Dump file not found at ' . $inputPath);
		}

		$process = new Process($this->buildCommand($params, $inputPath), null, [
			'PGPASSWORD' => $params->password,
		], null, null);

		$process->run();

		if (!$process->isSuccessful()) {
			throw new RestoreFailedException('Could not restore PostgreSQL database: ' . $process->getErrorOutput());
		}
	}
}
