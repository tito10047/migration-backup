<?php

namespace Tito10047\MigrationBackup\Storage;

use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Dto\BackupFile;
use Tito10047\MigrationBackup\Exception\BackupNotFoundException;

class LocalStorageProvider implements ListableStorageProviderInterface {
	public function __construct(
		private readonly Filesystem $fs,
		private readonly string     $backupPath,
	) {}

	public function store(string $sourcePath, string $targetFilename): string {
		if (!$this->fs->exists($this->backupPath)) {
			$this->fs->mkdir($this->backupPath);
		}

		$targetPath = $this->path($targetFilename);

		if ($sourcePath !== $targetPath) {
			$this->fs->copy($sourcePath, $targetPath, true);
		}

		return $targetPath;
	}

	public function cleanup(string $connectionName, int $keepLastN): void {
		if ($keepLastN <= 0) {
			return;
		}

		foreach (array_slice($this->list($connectionName), $keepLastN) as $file) {
			$this->remove($file);
		}
	}

	public function list(?string $connectionName = null): array {
		if (!$this->fs->exists($this->backupPath)) {
			return [];
		}

		$paths = glob(rtrim($this->backupPath, '/') . '/*.sql*');
		if ($paths === false) {
			return [];
		}

		$files = [];
		foreach ($paths as $path) {
			$file = BackupFile::fromPath($path);
			if ($file === null) {
				continue;
			}
			if ($connectionName !== null && $file->connectionName !== $connectionName) {
				continue;
			}
			$files[] = $file;
		}

		// Newest first, by the timestamp in the file name: a stray `touch` must
		// not change which backup is considered the latest one.
		usort($files, static fn (BackupFile $a, BackupFile $b) => $b->createdAt <=> $a->createdAt);

		return $files;
	}

	public function get(string $filename): BackupFile {
		// Never let a caller-supplied name escape the backup directory.
		if ($filename !== basename($filename)) {
			throw BackupNotFoundException::forFilename($filename);
		}

		$file = BackupFile::fromPath($this->path($filename));
		if ($file === null) {
			throw BackupNotFoundException::forFilename($filename);
		}

		return $file;
	}

	public function fetch(BackupFile $file, string $targetPath): void {
		$this->fs->copy($file->path, $targetPath, true);
	}

	public function remove(BackupFile $file): void {
		$this->fs->remove($file->path);
	}

	private function path(string $filename): string {
		return rtrim($this->backupPath, '/') . '/' . $filename;
	}
}
