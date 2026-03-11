<?php

namespace Tito10047\MigrationBackup\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Throwable;

class BackupFailedEvent extends Event {
	public function __construct(
		public readonly string    $connectionName,
		public readonly Throwable $exception
	) {}
}
