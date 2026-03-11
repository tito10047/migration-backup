<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Driver;

use PHPUnit\Framework\TestCase;
use Tito10047\MigrationBackup\Driver\MysqlBackupDriver;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Symfony\Component\Filesystem\Filesystem;

class MysqlBackupDriverTest extends TestCase {
	public function testSupports(): void {
		$fs     = $this->createMock(Filesystem::class);
		$driver = new MysqlBackupDriver($fs);

		$this->assertTrue($driver->supports('pdo_mysql'));
		$this->assertTrue($driver->supports('mysqli'));
		$this->assertFalse($driver->supports('pdo_pgsql'));
	}

	public function testUsesCustomBinary(): void {
		$fs     = $this->createMock(Filesystem::class);
		$driver = new MysqlBackupDriver($fs, '/usr/local/bin/mysqldump');

		$reflection = new \ReflectionClass($driver);
		$property   = $reflection->getProperty('mysqldumpPath');
		$property->setAccessible(true);

		$this->assertEquals('/usr/local/bin/mysqldump', $property->getValue($driver));
	}

	// Poznámka: Testovanie dump metódy, ktorá spúšťa externý proces, je v unit testoch náročné.
	// V ideálnom prípade by sme mali ProcessFactory, ktorú by sme mohli mockovať.
	// Pre účely tohto cvičenia sa zameriame na základnú implementáciu.
}
