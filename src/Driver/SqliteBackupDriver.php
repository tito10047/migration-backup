<?php

namespace Tito10047\MigrationBackup\Driver;

use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\BackupFailedException;

class SqliteBackupDriver implements BackupDriverInterface {
	public function __construct(
		private readonly Filesystem $fs
	) {}

	public function supports(string $driverName): bool {
		return $driverName === 'pdo_sqlite' || $driverName === 'sqlite3';
	}

	public function dump(ConnectionParams $params, string $outputPath): void {
		if ($params->path === null) {
			throw new BackupFailedException("SQLite path is not defined");
		}

		if (!$this->fs->exists($params->path)) {
			// maybe database is not yet created, so we create an empty file if needed or throw exception
			// but usually we want to backup existing database
			throw new BackupFailedException("SQLite database file not found at " . $params->path);
		}

		try {
			$this->fs->copy($params->path, $outputPath, true);
		} catch (\Exception $e) {
			throw new BackupFailedException("Failed to copy SQLite database: " . $e->getMessage(), 0, $e);
		}
	}
}
