<?php

namespace Tito10047\MigrationBackup\Storage;

interface StorageProviderInterface {
	public function store(string $sourcePath, string $targetFilename): string;

	/**
	 * Remove old backups for a given connection
	 */
	public function cleanup(string $connectionName, int $keepLastN): void;
}
