<?php

namespace Tito10047\MigrationBackup\Registry;

use Tito10047\MigrationBackup\Driver\BackupDriverInterface;
use Tito10047\MigrationBackup\Exception\UnsupportedDatabaseException;

class BackupDriverRegistry implements BackupDriverRegistryInterface {
	/**
	 * @param iterable<BackupDriverInterface> $drivers
	 */
	public function __construct(
		private readonly iterable $drivers
	) {}

	public function getDriver(string $driverName): BackupDriverInterface {
		foreach ($this->drivers as $driver) {
			if ($driver->supports($driverName)) {
				return $driver;
			}
		}

		throw new UnsupportedDatabaseException($driverName);
	}
}
