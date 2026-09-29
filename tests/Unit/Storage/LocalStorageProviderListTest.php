<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Exception\BackupNotFoundException;
use Tito10047\MigrationBackup\Storage\ListableStorageProviderInterface;
use Tito10047\MigrationBackup\Storage\LocalStorageProvider;

class LocalStorageProviderListTest extends TestCase {
	private Filesystem $fs;
	private string $backupPath;
	private LocalStorageProvider $provider;

	protected function setUp(): void {
		$this->fs         = new Filesystem();
		$this->backupPath = sys_get_temp_dir() . '/mb_storage_' . uniqid();
		$this->fs->mkdir($this->backupPath);
		$this->provider = new LocalStorageProvider($this->fs, $this->backupPath);
	}

	protected function tearDown(): void {
		$this->fs->remove($this->backupPath);
	}

	public function testProviderIsListable(): void {
		$this->assertInstanceOf(ListableStorageProviderInterface::class, $this->provider);
	}

	public function testListReturnsNewestFirst(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql.gz');
		$this->createBackup('default-2024-03-13-10-00-00.sql.gz');
		$this->createBackup('default-2024-03-12-10-00-00.sql.gz');

		$files = $this->provider->list('default');

		$this->assertCount(3, $files);
		$this->assertSame('default-2024-03-13-10-00-00.sql.gz', $files[0]->filename);
		$this->assertSame('default-2024-03-12-10-00-00.sql.gz', $files[1]->filename);
		$this->assertSame('default-2024-03-11-10-00-00.sql.gz', $files[2]->filename);
	}

	public function testListFiltersByConnection(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql');
		$this->createBackup('legacy-2024-03-11-10-00-00.sql');

		$this->assertCount(1, $this->provider->list('default'));
		$this->assertCount(2, $this->provider->list());
	}

	public function testListIgnoresForeignFiles(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql');
		$this->createBackup('notes.txt');

		$files = $this->provider->list();

		$this->assertCount(1, $files);
		$this->assertSame('default-2024-03-11-10-00-00.sql', $files[0]->filename);
	}

	public function testListOnMissingDirectoryReturnsEmptyArray(): void {
		$provider = new LocalStorageProvider($this->fs, $this->backupPath . '/nope');

		$this->assertSame([], $provider->list());
	}

	public function testGetReturnsBackup(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql');

		$file = $this->provider->get('default-2024-03-11-10-00-00.sql');

		$this->assertSame('default', $file->connectionName);
	}

	public function testGetThrowsWhenMissing(): void {
		$this->expectException(BackupNotFoundException::class);

		$this->provider->get('default-2024-03-11-10-00-00.sql');
	}

	public function testGetRejectsPathTraversal(): void {
		$this->expectException(BackupNotFoundException::class);

		$this->provider->get('../../etc/passwd');
	}

	public function testFetchCopiesBackupWithoutTouchingTheOriginal(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql', 'dump content');
		$file   = $this->provider->get('default-2024-03-11-10-00-00.sql');
		$target = $this->backupPath . '/working-copy.sql';

		$this->provider->fetch($file, $target);

		$this->assertSame('dump content', file_get_contents($target));
		$this->assertFileExists($file->path);
	}

	public function testRemove(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql');
		$file = $this->provider->get('default-2024-03-11-10-00-00.sql');

		$this->provider->remove($file);

		$this->assertFileDoesNotExist($file->path);
		$this->assertSame([], $this->provider->list());
	}

	public function testCleanupKeepsNewestByBackupDateNotByMtime(): void {
		// The newest backup is deliberately given the oldest modification time:
		// a `touch` must not decide which backup survives.
		$this->createBackup('default-2024-03-11-10-00-00.sql', 'a', strtotime('2024-03-20 00:00:00'));
		$this->createBackup('default-2024-03-12-10-00-00.sql', 'b', strtotime('2024-03-19 00:00:00'));
		$this->createBackup('default-2024-03-13-10-00-00.sql', 'c', strtotime('2024-03-18 00:00:00'));

		$this->provider->cleanup('default', 1);

		$files = $this->provider->list('default');
		$this->assertCount(1, $files);
		$this->assertSame('default-2024-03-13-10-00-00.sql', $files[0]->filename);
	}

	public function testCleanupKeepsEverythingWhenKeepIsZero(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql');
		$this->createBackup('default-2024-03-12-10-00-00.sql');

		$this->provider->cleanup('default', 0);

		$this->assertCount(2, $this->provider->list('default'));
	}

	public function testCleanupOnlyTouchesTheGivenConnection(): void {
		$this->createBackup('default-2024-03-11-10-00-00.sql');
		$this->createBackup('default-2024-03-12-10-00-00.sql');
		$this->createBackup('legacy-2024-03-11-10-00-00.sql');

		$this->provider->cleanup('default', 1);

		$this->assertCount(1, $this->provider->list('default'));
		$this->assertCount(1, $this->provider->list('legacy'));
	}

	private function createBackup(string $filename, string $content = 'dump', ?int $mtime = null): void {
		$path = $this->backupPath . '/' . $filename;
		file_put_contents($path, $content);
		if ($mtime !== null) {
			touch($path, $mtime);
		}
	}
}
