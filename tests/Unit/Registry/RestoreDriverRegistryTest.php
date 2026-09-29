<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Tito10047\MigrationBackup\Driver\RestoreDriverInterface;
use Tito10047\MigrationBackup\Exception\UnsupportedDatabaseException;
use Tito10047\MigrationBackup\Registry\RestoreDriverRegistry;

class RestoreDriverRegistryTest extends TestCase {
	public function testGetDriverReturnsTheSupportingDriver(): void {
		$mysql = $this->driverFor('pdo_mysql');
		$other = $this->driverFor('pdo_sqlite');

		$registry = new RestoreDriverRegistry([$other, $mysql]);

		$this->assertSame($mysql, $registry->getDriver('pdo_mysql'));
	}

	public function testGetDriverThrowsForUnsupportedDatabase(): void {
		$registry = new RestoreDriverRegistry([$this->driverFor('pdo_mysql')]);

		$this->expectException(UnsupportedDatabaseException::class);
		$this->expectExceptionMessage('pdo_oci');

		$registry->getDriver('pdo_oci');
	}

	private function driverFor(string $driverName): RestoreDriverInterface {
		$driver = $this->createMock(RestoreDriverInterface::class);
		$driver->method('supports')->willReturnCallback(
			static fn (string $name): bool => $name === $driverName
		);

		return $driver;
	}
}
