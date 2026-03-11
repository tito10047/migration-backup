<?php

namespace Tito10047\MigrationBackup\Storage;

interface StorageProviderInterface {
	public function store(string $sourcePath, string $targetFilename): string;
}
