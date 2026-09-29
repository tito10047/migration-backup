<?php

namespace Tito10047\MigrationBackup\Exception;

use Exception;

class BackupNotFoundException extends Exception {
	public static function forFilename(string $filename): self {
		return new self(sprintf('Backup "%s" was not found.', $filename));
	}

	public static function forConnection(string $connectionName): self {
		return new self(sprintf('There is no backup of the connection "%s".', $connectionName));
	}
}
