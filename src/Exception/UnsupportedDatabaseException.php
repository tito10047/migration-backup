<?php

namespace Tito10047\MigrationBackup\Exception;

use Exception;

class UnsupportedDatabaseException extends Exception {
	public function __construct(string $driverName) {
		parent::__construct(sprintf('Database driver "%s" is not supported.', $driverName));
	}
}
