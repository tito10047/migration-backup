<?php

namespace Tito10047\MigrationBackup;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Tito10047\MigrationBackup\Dto\BackupFile;
use Tito10047\MigrationBackup\Event\RestoreFailedEvent;
use Tito10047\MigrationBackup\Event\RestoreFinishedEvent;
use Tito10047\MigrationBackup\Event\RestoreStartedEvent;
use Tito10047\MigrationBackup\Exception\BackupNotFoundException;
use Tito10047\MigrationBackup\Registry\DecompressorRegistryInterface;
use Tito10047\MigrationBackup\Registry\RestoreDriverRegistryInterface;
use Tito10047\MigrationBackup\Resolver\ConnectionResolverInterface;
use Tito10047\MigrationBackup\Storage\ListableStorageProviderInterface;

/**
 * The counterpart of {@see BackupManager}: loads a stored backup back into the
 * database.
 */
class RestoreManager {
	public function __construct(
		private readonly ConnectionResolverInterface      $connectionResolver,
		private readonly RestoreDriverRegistryInterface   $driverRegistry,
		private readonly ListableStorageProviderInterface $storageProvider,
		private readonly DecompressorRegistryInterface    $decompressorRegistry,
		private readonly EventDispatcherInterface         $eventDispatcher,
		private readonly Filesystem                       $fs,
	) {}

	/**
	 * @param string|null $targetConnection connection to restore into, the one the backup was taken from by default
	 */
	public function restore(BackupFile $file, ?string $targetConnection = null): void {
		$connectionName = $targetConnection ?? $file->connectionName;

		$this->eventDispatcher->dispatch(new RestoreStartedEvent($connectionName, $file));

		$workingCopy = null;
		try {
			// Everything that can fail without touching the database fails first.
			$params       = $this->connectionResolver->resolve($connectionName);
			$driver       = $this->driverRegistry->getDriver($params->driver);
			$decompressor = $this->decompressorRegistry->getForExtension($file->compressionExtension);

			// The stored backup is never modified: it is decompressed in a copy.
			$workingCopy = tempnam(sys_get_temp_dir(), 'mb_restore_');
			$this->storageProvider->fetch($file, $workingCopy);
			$decompressor->decompress($workingCopy);

			$driver->restore($params, $workingCopy);

			$this->eventDispatcher->dispatch(new RestoreFinishedEvent($connectionName, $file));
		} catch (Throwable $e) {
			$this->eventDispatcher->dispatch(new RestoreFailedEvent($connectionName, $file, $e));
			throw $e;
		} finally {
			if ($workingCopy && $this->fs->exists($workingCopy)) {
				$this->fs->remove($workingCopy);
			}
		}
	}

	/**
	 * @return list<BackupFile>
	 */
	public function list(?string $connectionName = null): array {
		return $this->storageProvider->list($connectionName);
	}

	/**
	 * @throws BackupNotFoundException
	 */
	public function get(string $filename): BackupFile {
		return $this->storageProvider->get($filename);
	}

	/**
	 * @throws BackupNotFoundException
	 */
	public function latest(string $connectionName): BackupFile {
		$files = $this->storageProvider->list($connectionName);

		if ($files === []) {
			throw BackupNotFoundException::forConnection($connectionName);
		}

		return $files[0];
	}
}
