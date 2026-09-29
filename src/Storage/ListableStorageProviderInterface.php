<?php

namespace Tito10047\MigrationBackup\Storage;

use Tito10047\MigrationBackup\Dto\BackupFile;
use Tito10047\MigrationBackup\Exception\BackupNotFoundException;

/**
 * A storage that can also be browsed, which is what listing, cleaning and
 * restoring need.
 *
 * It is a separate interface on purpose: custom storage providers written
 * against older versions of this bundle keep working for backups.
 */
interface ListableStorageProviderInterface extends StorageProviderInterface {
	/**
	 * Backups of the given connection (or of all connections), newest first.
	 *
	 * @return list<BackupFile>
	 */
	public function list(?string $connectionName = null): array;

	/**
	 * @throws BackupNotFoundException
	 */
	public function get(string $filename): BackupFile;

	/**
	 * Copies the backup to a working location, leaving the stored file untouched.
	 */
	public function fetch(BackupFile $file, string $targetPath): void;

	public function remove(BackupFile $file): void;
}
