<?php

namespace Tito10047\MigrationBackup\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tito10047\MigrationBackup\Dto\BackupFile;

class RestoreStartedEvent extends Event {
	public function __construct(
		public readonly string     $connectionName,
		public readonly BackupFile $file
	) {}
}
