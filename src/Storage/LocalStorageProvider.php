<?php

namespace Tito10047\MigrationBackup\Storage;

use Symfony\Component\Filesystem\Filesystem;

class LocalStorageProvider implements StorageProviderInterface {
	public function __construct(
		private readonly Filesystem $fs,
		private readonly string     $backupPath,
	) {}

	public function store(string $sourcePath, string $targetFilename): string {
		$targetPath = rtrim($this->backupPath, '/') . '/' . $targetFilename;

		if ($sourcePath !== $targetPath) {
			$this->fs->copy($sourcePath, $targetPath, true);
		}

		return $targetPath;
	}
}
