<?php

namespace Tito10047\MigrationBackup\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BackupFinishedEvent extends Event {
	public function __construct(
		public readonly string $connectionName,
		public readonly string $path
	) {}
}
