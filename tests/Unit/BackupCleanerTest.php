<?php

namespace Tito10047\MigrationBackup\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\BackupCleaner;
use Tito10047\MigrationBackup\Storage\LocalStorageProvider;

class BackupCleanerTest extends TestCase {
	private Filesystem $fs;
	private string $backupPath;
	private LocalStorageProvider $storage;

	protected function setUp(): void {
		$this->fs         = new Filesystem();
		$this->backupPath = sys_get_temp_dir() . '/mb_cleaner_' . uniqid();
		$this->fs->mkdir($this->backupPath);
		$this->storage = new LocalStorageProvider($this->fs, $this->backupPath);

		foreach (['11', '12', '13'] as $day) {
			file_put_contents($this->backupPath . '/default-2024-03-' . $day . '-10-00-00.sql', 'dump');
		}
		file_put_contents($this->backupPath . '/legacy-2024-03-11-10-00-00.sql', 'dump');
	}

	protected function tearDown(): void {
		$this->fs->remove($this->backupPath);
	}

	public function testCleanRemovesAllButTheNewest(): void {
		$removed = (new BackupCleaner($this->storage, 0))->clean('default', 1);

		$this->assertCount(2, $removed);
		$this->assertSame('default-2024-03-11-10-00-00.sql', $removed[0]->filename);
		$this->assertSame('default-2024-03-12-10-00-00.sql', $removed[1]->filename);
		$this->assertCount(1, $this->storage->list('default'));
	}

	public function testCleanFallsBackToTheConfiguredKeepCount(): void {
		$removed = (new BackupCleaner($this->storage, 2))->clean('default');

		$this->assertCount(1, $removed);
		$this->assertCount(2, $this->storage->list('default'));
	}

	public function testDryRunRemovesNothing(): void {
		$removed = (new BackupCleaner($this->storage, 0))->clean('default', 1, true);

		$this->assertCount(2, $removed);
		$this->assertCount(3, $this->storage->list('default'));
	}

	public function testKeepAllRemovesNothing(): void {
		$cleaner = new BackupCleaner($this->storage, 0);

		$this->assertSame([], $cleaner->clean('default'));
		$this->assertCount(3, $this->storage->list('default'));
	}

	public function testCleanLeavesOtherConnectionsAlone(): void {
		(new BackupCleaner($this->storage, 0))->clean('default', 1);

		$this->assertCount(1, $this->storage->list('legacy'));
	}

	public function testCleanNothingToRemove(): void {
		$this->assertSame([], (new BackupCleaner($this->storage, 0))->clean('default', 10));
	}
}
