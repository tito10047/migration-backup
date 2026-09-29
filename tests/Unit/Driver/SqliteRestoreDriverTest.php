<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Driver;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Driver\RestoreDriverInterface;
use Tito10047\MigrationBackup\Driver\SqliteRestoreDriver;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Exception\RestoreFailedException;

class SqliteRestoreDriverTest extends TestCase {
	private string $dir;
	private SqliteRestoreDriver $driver;

	protected function setUp(): void {
		$this->driver = new SqliteRestoreDriver(new Filesystem());
		$this->dir    = sys_get_temp_dir() . '/mb_sqlite_restore_' . uniqid();
		mkdir($this->dir);
	}

	protected function tearDown(): void {
		(new Filesystem())->remove($this->dir);
	}

	public function testIsRestoreDriver(): void {
		$this->assertInstanceOf(RestoreDriverInterface::class, $this->driver);
	}

	public function testSupports(): void {
		$this->assertTrue($this->driver->supports('pdo_sqlite'));
		$this->assertTrue($this->driver->supports('sqlite3'));
		$this->assertFalse($this->driver->supports('pdo_mysql'));
	}

	public function testRestoreOverwritesTheDatabaseFile(): void {
		$dbPath = $this->dir . '/data.db';
		$dump   = $this->dir . '/dump.sql';
		file_put_contents($dbPath, 'current database');
		file_put_contents($dump, 'backed up database');

		$this->driver->restore($this->params($dbPath), $dump);

		$this->assertSame('backed up database', file_get_contents($dbPath));
	}

	public function testRestoreCreatesTheDatabaseFileWhenItIsGone(): void {
		$dbPath = $this->dir . '/data.db';
		$dump   = $this->dir . '/dump.sql';
		file_put_contents($dump, 'backed up database');

		$this->driver->restore($this->params($dbPath), $dump);

		$this->assertSame('backed up database', file_get_contents($dbPath));
	}

	public function testRestoreThrowsWhenPathIsNotDefined(): void {
		$this->expectException(RestoreFailedException::class);
		$this->expectExceptionMessage('SQLite path is not defined');

		$this->driver->restore($this->params(null), $this->dir . '/dump.sql');
	}

	public function testRestoreThrowsWhenDumpIsMissing(): void {
		$this->expectException(RestoreFailedException::class);
		$this->expectExceptionMessage('not found');

		$this->driver->restore($this->params($this->dir . '/data.db'), $this->dir . '/nope.sql');
	}

	private function params(?string $path): ConnectionParams {
		return new ConnectionParams('localhost', '', 'db', 'user', 'pass', 'pdo_sqlite', $path);
	}
}
