<?php

namespace Tito10047\MigrationBackup\Storage;

use Symfony\Component\Filesystem\Filesystem;

class LocalStorageProvider implements StorageProviderInterface {
	public function __construct(
		private readonly Filesystem $fs,
		private readonly string     $backupPath,
	) {}

	public function store(string $sourcePath, string $targetFilename): string {
		if (!$this->fs->exists($this->backupPath)) {
			$this->fs->mkdir($this->backupPath);
		}

		$targetPath = rtrim($this->backupPath, '/') . '/' . $targetFilename;

		if ($sourcePath !== $targetPath) {
			$this->fs->copy($sourcePath, $targetPath, true);
		}

		return $targetPath;
	}

	public function cleanup(string $connectionName, int $keepLastN): void {
		if ($keepLastN <= 0) {
			return;
		}

		if (!$this->fs->exists($this->backupPath)) {
			return;
		}

		$pattern = rtrim($this->backupPath, '/') . '/' . $connectionName . '-*';
		$files   = glob($pattern);

		if ($files === false || count($files) <= $keepLastN) {
			return;
		}

		// Sort by modification time, newest first
		usort($files, function ($a, $b) {
			return filemtime($b) <=> filemtime($a);
		});

		$filesToDelete = array_slice($files, $keepLastN);

		foreach ($filesToDelete as $file) {
			$this->fs->remove($file);
		}
	}
}
