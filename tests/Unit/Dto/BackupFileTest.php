<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Dto;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tito10047\MigrationBackup\Dto\BackupFile;

class BackupFileTest extends TestCase {
	private string $dir;

	protected function setUp(): void {
		$this->dir = sys_get_temp_dir() . '/mb_backup_file_' . uniqid();
		mkdir($this->dir);
	}

	protected function tearDown(): void {
		foreach (glob($this->dir . '/*') ?: [] as $file) {
			unlink($file);
		}
		if (is_dir($this->dir)) {
			rmdir($this->dir);
		}
	}

	public function testBuildFilename(): void {
		$createdAt = new DateTimeImmutable('2024-03-11 15:55:01');

		$this->assertSame(
			'default-2024-03-11-15-55-01.sql.gz',
			BackupFile::buildFilename('default', $createdAt, '.gz')
		);
		$this->assertSame(
			'default-2024-03-11-15-55-01.sql',
			BackupFile::buildFilename('default', $createdAt, '')
		);
	}

	public function testFromPathParsesFilename(): void {
		$path = $this->write('default-2024-03-11-15-55-01.sql.gz', 'dump');

		$file = BackupFile::fromPath($path);

		$this->assertNotNull($file);
		$this->assertSame('default-2024-03-11-15-55-01.sql.gz', $file->filename);
		$this->assertSame($path, $file->path);
		$this->assertSame('default', $file->connectionName);
		$this->assertSame('.gz', $file->compressionExtension);
		$this->assertSame('2024-03-11 15:55:01', $file->createdAt->format('Y-m-d H:i:s'));
		$this->assertSame(4, $file->size);
	}

	public function testFromPathParsesUncompressedFilename(): void {
		$file = BackupFile::fromPath($this->write('default-2024-03-11-15-55-01.sql', 'dump'));

		$this->assertNotNull($file);
		$this->assertSame('', $file->compressionExtension);
	}

	public function testFromPathKeepsDashesInConnectionName(): void {
		$file = BackupFile::fromPath($this->write('my-second-db-2024-03-11-15-55-01.sql.zst', 'dump'));

		$this->assertNotNull($file);
		$this->assertSame('my-second-db', $file->connectionName);
		$this->assertSame('.zst', $file->compressionExtension);
	}

	public function testFromPathReturnsNullForForeignFile(): void {
		$this->assertNull(BackupFile::fromPath($this->write('readme.txt', 'nope')));
		$this->assertNull(BackupFile::fromPath($this->write('default-nonsense.sql', 'nope')));
	}

	public function testFromPathReturnsNullForMissingFile(): void {
		$this->assertNull(BackupFile::fromPath($this->dir . '/default-2024-03-11-15-55-01.sql'));
	}

	public function testIsCompressed(): void {
		$compressed = BackupFile::fromPath($this->write('default-2024-03-11-15-55-01.sql.gz', 'dump'));
		$plain      = BackupFile::fromPath($this->write('default-2024-03-11-15-55-02.sql', 'dump'));

		$this->assertTrue($compressed->isCompressed());
		$this->assertFalse($plain->isCompressed());
	}

	private function write(string $filename, string $content): string {
		$path = $this->dir . '/' . $filename;
		file_put_contents($path, $content);

		return $path;
	}
}
