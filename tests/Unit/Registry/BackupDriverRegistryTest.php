<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Tito10047\MigrationBackup\Driver\BackupDriverInterface;
use Tito10047\MigrationBackup\Exception\UnsupportedDatabaseException;
use Tito10047\MigrationBackup\Registry\BackupDriverRegistry;

class BackupDriverRegistryTest extends TestCase {
	public function testGetDriverSuccessful(): void {
		$driver1 = $this->createMock(BackupDriverInterface::class);
		$driver1->method('supports')->with('mysql')->willReturn(true);

		$driver2 = $this->createMock(BackupDriverInterface::class);
		$driver2->method('supports')->with('mysql')->willReturn(false);

		$registry = new BackupDriverRegistry([$driver1, $driver2]);

		$this->assertSame($driver1, $registry->getDriver('mysql'));
	}

	public function testGetDriverThrowsExceptionWhenNotFound(): void {
		$driver1 = $this->createMock(BackupDriverInterface::class);
		$driver1->method('supports')->with('pgsql')->willReturn(false);

		$registry = new BackupDriverRegistry([$driver1]);

		$this->expectException(UnsupportedDatabaseException::class);
		$this->expectExceptionMessage('Database driver "pgsql" is not supported.');

		$registry->getDriver('pgsql');
	}
}
