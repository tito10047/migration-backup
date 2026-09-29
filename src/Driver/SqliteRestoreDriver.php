<?php

namespace Tito10047\MigrationBackup\Driver;

use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\RestoreFailedException;

class SqliteRestoreDriver implements RestoreDriverInterface {
	public function __construct(
		private readonly Filesystem $fs
	) {}

	public function supports(string $driverName): bool {
		return $driverName === 'pdo_sqlite' || $driverName === 'sqlite3';
	}

	public function restore(ConnectionParams $params, string $inputPath): void {
		if ($params->path === null) {
			throw new RestoreFailedException('SQLite path is not defined');
		}

		if (!$this->fs->exists($inputPath)) {
			throw new RestoreFailedException('Dump file not found at ' . $inputPath);
		}

		try {
			// The backup of an SQLite database is the database file itself.
			$this->fs->copy($inputPath, $params->path, true);
		} catch (\Exception $e) {
			throw new RestoreFailedException('Could not restore SQLite database: ' . $e->getMessage(), 0, $e);
		}
	}
}
