<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Driver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Driver\MysqlRestoreDriver;
use Tito10047\MigrationBackup\Driver\RestoreDriverInterface;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\RestoreFailedException;

class MysqlRestoreDriverTest extends TestCase {
	private ConnectionParams $params;

	protected function setUp(): void {
		$this->params = new ConnectionParams('db.example.com', '3307', 'shop', 'root', 'secret', 'pdo_mysql');
	}

	public function testIsRestoreDriver(): void {
		$this->assertInstanceOf(RestoreDriverInterface::class, new MysqlRestoreDriver(new Filesystem()));
	}

	public function testSupports(): void {
		$driver = new MysqlRestoreDriver(new Filesystem());

		$this->assertTrue($driver->supports('pdo_mysql'));
		$this->assertTrue($driver->supports('mysqli'));
		$this->assertTrue($driver->supports('mysql'));
		$this->assertFalse($driver->supports('pdo_pgsql'));
		$this->assertFalse($driver->supports('pdo_sqlite'));
	}

	public function testBuildCommandUsesConnectionParams(): void {
		$driver = new MysqlRestoreDriver(new Filesystem());

		$this->assertSame(
			['mysql', '-h', 'db.example.com', '-P', '3307', '-u', 'root', 'shop'],
			$driver->buildCommand($this->params)
		);
	}

	public function testBuildCommandUsesCustomBinary(): void {
		$driver = new MysqlRestoreDriver(new Filesystem(), '/usr/local/bin/mysql');

		$this->assertSame('/usr/local/bin/mysql', $driver->buildCommand($this->params)[0]);
	}

	public function testRestoreThrowsWhenDumpIsMissing(): void {
		$driver = new MysqlRestoreDriver(new Filesystem());

		$this->expectException(RestoreFailedException::class);
		$this->expectExceptionMessage('not found');

		$driver->restore($this->params, '/tmp/does-not-exist-' . uniqid() . '.sql');
	}

	public function testRestoreThrowsWhenClientBinaryFails(): void {
		$driver = new MysqlRestoreDriver(new Filesystem(), '/nonexistent/mysql');
		$dump   = tempnam(sys_get_temp_dir(), 'mb_restore_');
		file_put_contents($dump, 'SELECT 1;');

		try {
			$this->expectException(RestoreFailedException::class);
			$this->expectExceptionMessage('Could not restore');

			$driver->restore($this->params, $dump);
		} finally {
			unlink($dump);
		}
	}
}
