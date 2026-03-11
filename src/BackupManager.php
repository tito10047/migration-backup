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
		private readonly string                        $backupPath,
	) {}

	public function backup(string $connectionName): string {
		$this->eventDispatcher->dispatch(new BackupStartedEvent($connectionName));

		try {
			if (!$this->fs->exists($this->backupPath)) {
				$this->fs->mkdir($this->backupPath);
			}

			$params = $this->connectionResolver->resolve($connectionName);
			$driver = $this->driverRegistry->getDriver($params->driver);

			$filename = $connectionName . '-' . date('Y-m-d-H-i-s') . '.sql';
			$tempPath = rtrim($this->backupPath, '/') . '/' . $filename;

			$driver->dump($params, $tempPath);

			$storedPath = $this->storageProvider->store($tempPath, $filename);

			$this->eventDispatcher->dispatch(new BackupFinishedEvent($connectionName, $storedPath));

			return $storedPath;
		} catch (Throwable $e) {
			$this->eventDispatcher->dispatch(new BackupFailedEvent($connectionName, $e));
			throw $e;
		}
	}
}
