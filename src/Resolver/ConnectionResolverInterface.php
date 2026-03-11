<?php

namespace Tito10047\MigrationBackup\Resolver;

use Tito10047\MigrationBackup\Dto\ConnectionParams;

interface ConnectionResolverInterface {
	public function resolve(string $connectionName): ConnectionParams;
}
