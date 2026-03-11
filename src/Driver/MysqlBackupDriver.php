<?php

namespace Tito10047\MigrationBackup\Driver;

use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\BackupFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class MysqlBackupDriver implements BackupDriverInterface {
	public function __construct(
		private readonly Filesystem $fs,
		private readonly string     $mysqldumpPath = 'mysqldump',
	) {}

	public function supports(string $driverName): bool {
		return in_array($driverName, ['pdo_mysql', 'mysqli', 'mysql'], true);
	}

	public function dump(ConnectionParams $params, string $outputPath): void {
		$cmd = [
			$this->mysqldumpPath,
			'-h', $params->host,
			'-P', $params->port,
			'-B', $params->database,
			'-u', $params->user,
			'--hex-blob',
		];

		$process = new Process($cmd, null, [
			'MYSQL_PWD' => $params->password,
		]);

		$process->run();

		if (!$process->isSuccessful()) {
			throw new BackupFailedException('Could not dump database: ' . $process->getErrorOutput());
		}

		$this->fs->dumpFile($outputPath, $process->getOutput());
	}
}
