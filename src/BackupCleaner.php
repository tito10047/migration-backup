<?php

namespace Tito10047\MigrationBackup;

use Tito10047\MigrationBackup\Dto\BackupFile;
use Tito10047\MigrationBackup\Storage\ListableStorageProviderInterface;

/**
 * Removes old backups on demand, with the same retention rule the automatic
 * cleanup after a backup uses.
 */
class BackupCleaner {
	public function __construct(
		private readonly ListableStorageProviderInterface $storageProvider,
		private readonly int                              $defaultKeepLastN = 0,
	) {}

	/**
	 * @param int|null $keepLastN number of newest backups to keep, the configured `keep_last_n_backups` by default; 0 keeps all of them
	 *
	 * @return list<BackupFile> the removed backups, oldest first (in a dry run the ones that would be removed)
	 */
	public function clean(string $connectionName, ?int $keepLastN = null, bool $dryRun = false): array {
		$keepLastN ??= $this->defaultKeepLastN;

		if ($keepLastN <= 0) {
			return [];
		}

		$obsolete = array_slice($this->storageProvider->list($connectionName), $keepLastN);
		$obsolete = array_reverse($obsolete);

		if (!$dryRun) {
			foreach ($obsolete as $file) {
				$this->storageProvider->remove($file);
			}
		}

		return $obsolete;
	}
}
