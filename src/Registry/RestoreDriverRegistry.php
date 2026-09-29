<?php

namespace Tito10047\MigrationBackup\Registry;

use Tito10047\MigrationBackup\Driver\RestoreDriverInterface;
use Tito10047\MigrationBackup\Exception\UnsupportedDatabaseException;

class RestoreDriverRegistry implements RestoreDriverRegistryInterface {
	/**
	 * @param iterable<RestoreDriverInterface> $drivers
	 */
	public function __construct(
		private readonly iterable $drivers
	) {}

	public function getDriver(string $driverName): RestoreDriverInterface {
		foreach ($this->drivers as $driver) {
			if ($driver->supports($driverName)) {
				return $driver;
			}
		}

		throw new UnsupportedDatabaseException($driverName);
	}
}
