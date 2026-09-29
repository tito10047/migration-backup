<?php

namespace Tito10047\MigrationBackup\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;
use Tito10047\MigrationBackup\Dto\BackupFile;

class RestoreFailedEvent extends Event {
	public function __construct(
		public readonly string      $connectionName,
		public readonly ?BackupFile $file,
		public readonly Throwable   $exception
	) {}
}
