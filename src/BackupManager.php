<?php

namespace Tito10047\MigrationBackup;

use Tito10047\MigrationBackup\Registry\BackupDriverRegistryInterface;
use Tito10047\MigrationBackup\Resolver\ConnectionResolverInterface;
use Tito10047\MigrationBackup\Storage\StorageProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tito10047\MigrationBackup\Event\BackupStartedEvent;
use Tito10047\MigrationBackup\Event\BackupFinishedEvent;
use Tito10047\MigrationBackup\Event\BackupFailedEvent;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

class BackupManager {
	public function __construct(
		private readonly ConnectionResolverInterface   $connectionResolver,
		private readonly BackupDriverRegistryInterface $driverRegistry,
		private readonly StorageProviderInterface      $storageProvider,
		private readonly EventDispatcherInterface      $eventDispatcher,
		private readonly Filesystem                    $fs,
		private readonly int                           $keepLastN = 0,
		private readonly bool                          $compress = false,
	) {}

	public function backup(string $connectionName): string {
		$this->eventDispatcher->dispatch(new BackupStartedEvent($connectionName));

		$tempPath = null;
		try {
			$params = $this->connectionResolver->resolve($connectionName);
			$driver = $this->driverRegistry->getDriver($params->driver);

			$extension = $this->compress ? '.sql.gz' : '.sql';
			$filename  = $connectionName . '-' . date('Y-m-d-H-i-s') . $extension;
			$tempPath  = tempnam(sys_get_temp_dir(), 'mb_');

			$driver->dump($params, $tempPath);

			if ($this->compress) {
				$this->compressFile($tempPath);
			}

			$storedPath = $this->storageProvider->store($tempPath, $filename);

			$this->storageProvider->cleanup($connectionName, $this->keepLastN);

			$this->eventDispatcher->dispatch(new BackupFinishedEvent($connectionName, $storedPath));

			return $storedPath;
		} catch (Throwable $e) {
			$this->eventDispatcher->dispatch(new BackupFailedEvent($connectionName, $e));
			throw $e;
		} finally {
			if ($tempPath && $this->fs->exists($tempPath)) {
				$this->fs->remove($tempPath);
			}
		}
	}

	private function compressFile(string $path): void {
		$gzPath = $path . '.gz';
		$fp     = gzopen($gzPath, 'w9');
		if (!$fp) {
			throw new \RuntimeException('Could not open file for compression: ' . $gzPath);
		}

		$handle = fopen($path, 'rb');
		if (!$handle) {
			gzclose($fp);
			throw new \RuntimeException('Could not open file for reading: ' . $path);
		}

		while (!feof($handle)) {
			gzwrite($fp, fread($handle, 1024 * 512));
		}

		fclose($handle);
		gzclose($fp);

		$this->fs->rename($gzPath, $path, true);
	}
}
