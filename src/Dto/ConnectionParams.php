<?php

namespace Tito10047\MigrationBackup\Dto;

final readonly class ConnectionParams {
	public function __construct(
		public string $host,
		public string $port,
		public string $database,
		public string $user,
		public string $password,
		public string $driver,
		public ?string $path = null,
	) {}
}
