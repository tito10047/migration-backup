<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Driver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Driver\SqliteBackupDriver;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\BackupFailedException;

class SqliteBackupDriverTest extends TestCase {
	private Filesystem $fs;
	private SqliteBackupDriver $driver;

	protected function setUp(): void {
		$this->fs     = $this->createMock(Filesystem::class);
		$this->driver = new SqliteBackupDriver($this->fs);
	}

	public function testSupports(): void {
		$this->assertTrue($this->driver->supports('pdo_sqlite'));
		$this->assertTrue($this->driver->supports('sqlite3'));
		$this->assertFalse($this->driver->supports('pdo_mysql'));
	}

	public function testDumpThrowsExceptionIfPathMissing(): void {
		$params = new ConnectionParams('localhost', '', 'db', 'user', 'pass', 'pdo_sqlite', null);

		$this->expectException(BackupFailedException::class);
		$this->expectExceptionMessage('SQLite path is not defined');

		$this->driver->dump($params, 'output.sql');
	}

	public function testDumpThrowsExceptionIfFileNotFound(): void {
		$params = new ConnectionParams('localhost', '', 'db', 'user', 'pass', 'pdo_sqlite', '/path/to/db.sqlite');

		$this->fs->expects($this->once())
			->method('exists')
			->with('/path/to/db.sqlite')
			->willReturn(false);

		$this->expectException(BackupFailedException::class);
		$this->expectExceptionMessage('SQLite database file not found');

		$this->driver->dump($params, 'output.sql');
	}

	public function testDumpSuccessful(): void {
		$params = new ConnectionParams('localhost', '', 'db', 'user', 'pass', 'pdo_sqlite', '/path/to/db.sqlite');

		$this->fs->expects($this->once())
			->method('exists')
			->with('/path/to/db.sqlite')
			->willReturn(true);

		$this->fs->expects($this->once())
			->method('copy')
			->with('/path/to/db.sqlite', 'output.sql', true);

		$this->driver->dump($params, 'output.sql');
	}
}
