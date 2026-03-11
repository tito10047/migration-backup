<?php

namespace Tito10047\MigrationBackup\Driver;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\BackupFailedException;

class PostgresBackupDriver implements BackupDriverInterface {
	public function __construct(
		private readonly Filesystem $fs,
		private readonly string     $pgDumpPath = 'pg_dump',
	) {}

	public function supports(string $driverName): bool {
		return in_array($driverName, ['pdo_pgsql', 'pgsql', 'postgres'], true);
	}

	public function dump(ConnectionParams $params, string $outputPath): void {
		$cmd = [
			$this->pgDumpPath,
			'-h', $params->host,
			'-p', $params->port,
			'-U', $params->user,
			'-f', $outputPath,
			$params->database,
		];

		$process = new Process($cmd, null, [
			'PGPASSWORD' => $params->password,
		]);

		$process->run();

		if (!$process->isSuccessful()) {
			throw new BackupFailedException('Could not dump PostgreSQL database: ' . $process->getErrorOutput());
		}
	}
}
