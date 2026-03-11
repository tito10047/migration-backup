<?php

namespace Tito10047\MigrationBackup\Registry;

use Tito10047\MigrationBackup\Driver\BackupDriverInterface;

interface BackupDriverRegistryInterface {
	public function getDriver(string $driverName): BackupDriverInterface;
}
