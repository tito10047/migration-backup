<?php

namespace Tito10047\MigrationBackup;

use Tito10047\MigrationBackup\Compressor\CompressorInterface;
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
		private readonly CompressorInterface           $compressor,
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

			$extension = '.sql';
			if ($this->compress) {
				$extension .= $this->compressor->getExtension();
			}

			$filename = $connectionName . '-' . date('Y-m-d-H-i-s') . $extension;
			$tempPath = tempnam(sys_get_temp_dir(), 'mb_');

			$driver->dump($params, $tempPath);

			if ($this->compress) {
				$this->compressor->compress($tempPath);
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
}
