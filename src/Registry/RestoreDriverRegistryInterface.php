<?php

namespace Tito10047\MigrationBackup\Registry;

use Tito10047\MigrationBackup\Driver\RestoreDriverInterface;
use Tito10047\MigrationBackup\Exception\UnsupportedDatabaseException;

interface RestoreDriverRegistryInterface {
	/**
	 * @throws UnsupportedDatabaseException
	 */
	public function getDriver(string $driverName): RestoreDriverInterface;
}
