<?php

namespace Tito10047\MigrationBackup\Driver;

use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\RestoreFailedException;

/**
 * The counterpart of {@see BackupDriverInterface}: loads a dump back into the
 * database.
 *
 * It is a separate interface on purpose: custom backup drivers written against
 * older versions of this bundle keep working for backups.
 */
interface RestoreDriverInterface {
	public function supports(string $driverName): bool;

	/**
	 * @param string $inputPath path to the already decompressed dump
	 *
	 * @throws RestoreFailedException
	 */
	public function restore(ConnectionParams $params, string $inputPath): void;
}
