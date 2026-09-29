<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Driver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Driver\PostgresRestoreDriver;
use Tito10047\MigrationBackup\Driver\RestoreDriverInterface;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\RestoreFailedException;

class PostgresRestoreDriverTest extends TestCase {
	private ConnectionParams $params;

	protected function setUp(): void {
		$this->params = new ConnectionParams('db.example.com', '5433', 'shop', 'postgres', 'secret', 'pdo_pgsql');
	}

	public function testIsRestoreDriver(): void {
		$this->assertInstanceOf(RestoreDriverInterface::class, new PostgresRestoreDriver(new Filesystem()));
	}

	public function testSupports(): void {
		$driver = new PostgresRestoreDriver(new Filesystem());

		$this->assertTrue($driver->supports('pdo_pgsql'));
		$this->assertTrue($driver->supports('pgsql'));
		$this->assertTrue($driver->supports('postgres'));
		$this->assertFalse($driver->supports('pdo_mysql'));
	}

	public function testBuildCommandUsesConnectionParams(): void {
		$driver = new PostgresRestoreDriver(new Filesystem());

		$this->assertSame(
			['psql', '-h', 'db.example.com', '-p', '5433', '-U', 'postgres', '-d', 'shop', '-v', 'ON_ERROR_STOP=1', '-f', '/tmp/dump.sql'],
			$driver->buildCommand($this->params, '/tmp/dump.sql')
		);
	}

	public function testBuildCommandUsesCustomBinary(): void {
		$driver = new PostgresRestoreDriver(new Filesystem(), '/usr/local/bin/psql');

		$this->assertSame('/usr/local/bin/psql', $driver->buildCommand($this->params, '/tmp/dump.sql')[0]);
	}

	public function testRestoreThrowsWhenDumpIsMissing(): void {
		$driver = new PostgresRestoreDriver(new Filesystem());

		$this->expectException(RestoreFailedException::class);
		$this->expectExceptionMessage('not found');

		$driver->restore($this->params, '/tmp/does-not-exist-' . uniqid() . '.sql');
	}

	public function testRestoreThrowsWhenClientBinaryFails(): void {
		$driver = new PostgresRestoreDriver(new Filesystem(), '/nonexistent/psql');
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
