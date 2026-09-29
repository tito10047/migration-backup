<?php

namespace Tito10047\MigrationBackup\Driver;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\RestoreFailedException;

class MysqlRestoreDriver implements RestoreDriverInterface {
	public function __construct(
		private readonly Filesystem $fs,
		private readonly string     $mysqlPath = 'mysql',
	) {}

	public function supports(string $driverName): bool {
		return in_array($driverName, ['pdo_mysql', 'mysqli', 'mysql'], true);
	}

	/**
	 * @return list<string>
	 */
	public function buildCommand(ConnectionParams $params): array {
		return [
			$this->mysqlPath,
			'-h', $params->host,
			'-P', $params->port,
			'-u', $params->user,
			$params->database,
		];
	}

	public function restore(ConnectionParams $params, string $inputPath): void {
		if (!$this->fs->exists($inputPath)) {
			throw new RestoreFailedException('Dump file not found at ' . $inputPath);
		}

		$dump = fopen($inputPath, 'rb');
		if ($dump === false) {
			throw new RestoreFailedException('Could not open the dump file ' . $inputPath);
		}

		// The dump is streamed into the client instead of being read into memory,
		// backups of real databases do not fit into PHP's memory limit.
		$process = new Process($this->buildCommand($params), null, [
			'MYSQL_PWD' => $params->password,
		], $dump, null);

		$process->run();
		fclose($dump);

		if (!$process->isSuccessful()) {
			throw new RestoreFailedException('Could not restore database: ' . $process->getErrorOutput());
		}
	}
}
