<?php

namespace Tito10047\MigrationBackup\Driver;

use Tito10047\MigrationBackup\Dto\ConnectionParams;

interface BackupDriverInterface {
	public function supports(string $driverName): bool;

	public function dump(ConnectionParams $params, string $outputPath): void;
}
