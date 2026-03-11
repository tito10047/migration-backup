<?php

namespace Tito10047\MigrationBackup\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tito10047\MigrationBackup\BackupManager;
use Tito10047\MigrationBackup\Driver\BackupDriverInterface;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Registry\BackupDriverRegistryInterface;
use Tito10047\MigrationBackup\Resolver\ConnectionResolverInterface;
use Tito10047\MigrationBackup\Storage\StorageProviderInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tito10047\MigrationBackup\Event\BackupStartedEvent;
use Tito10047\MigrationBackup\Event\BackupFinishedEvent;
use Tito10047\MigrationBackup\Event\BackupFailedEvent;
use Symfony\Component\Filesystem\Filesystem;
use Exception;

class BackupManagerTest extends TestCase {
	private ConnectionResolverInterface $connectionResolver;
	private BackupDriverRegistryInterface $driverRegistry;
	private StorageProviderInterface $storageProvider;
	private EventDispatcherInterface $eventDispatcher;
	private Filesystem $fs;
	private BackupManager $backupManager;

	protected function setUp(): void {
		$this->connectionResolver = $this->createMock(ConnectionResolverInterface::class);
		$this->driverRegistry     = $this->createMock(BackupDriverRegistryInterface::class);
		$this->storageProvider    = $this->createMock(StorageProviderInterface::class);
		$this->eventDispatcher    = $this->createMock(EventDispatcherInterface::class);
		$this->fs                 = $this->createMock(Filesystem::class);
		$this->backupManager      = new BackupManager(
			$this->connectionResolver,
			$this->driverRegistry,
			$this->storageProvider,
			$this->eventDispatcher,
			$this->fs,
			0
		);
	}

	public function testBackupSuccessful(): void {
		$connectionName = 'default';
		$params         = new ConnectionParams('localhost', '3306', 'db', 'user', 'pass', 'pdo_mysql');
		$driver         = $this->createMock(BackupDriverInterface::class);

		$this->connectionResolver->expects($this->once())
			->method('resolve')
			->with($connectionName)
			->willReturn($params);

		$this->driverRegistry->expects($this->once())
			->method('getDriver')
			->with('pdo_mysql')
			->willReturn($driver);

		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatch')
			->withConsecutive(
				[$this->isInstanceOf(BackupStartedEvent::class)],
				[$this->isInstanceOf(BackupFinishedEvent::class)]
			);

		$driver->expects($this->once())
			->method('dump')
			->with($params, $this->callback(function (string $path) {
				return str_contains($path, sys_get_temp_dir());
			}));

		$this->storageProvider->expects($this->once())
			->method('store')
			->with($this->callback(function (string $path) {
				return str_contains($path, sys_get_temp_dir());
			}), $this->callback(function (string $filename) {
				return str_starts_with($filename, 'default-') && str_ends_with($filename, '.sql');
			}))
			->willReturn('stored_path');

		$this->storageProvider->expects($this->once())
			->method('cleanup')
			->with($connectionName, 0);

		$result = $this->backupManager->backup($connectionName);

		$this->assertEquals('stored_path', $result);
	}

	public function testBackupFailureDispatchesFailedEvent(): void {
		$connectionName = 'default';
		$params         = new ConnectionParams('localhost', '3306', 'db', 'user', 'pass', 'pdo_mysql');

		$this->connectionResolver->method('resolve')->willReturn($params);
		$this->driverRegistry->method('getDriver')->willThrowException(new Exception('Error'));

		$this->eventDispatcher->expects($this->exactly(2))
			->method('dispatch')
			->withConsecutive(
				[$this->isInstanceOf(BackupStartedEvent::class)],
				[$this->isInstanceOf(BackupFailedEvent::class)]
			);

		$this->expectException(Exception::class);
		$this->backupManager->backup($connectionName);
	}

	public function testCleanupIsCalled(): void {
		$connectionName = 'default';
		$params         = new ConnectionParams('localhost', '3306', 'db', 'user', 'pass', 'pdo_mysql');
		$driver         = $this->createMock(BackupDriverInterface::class);

		$this->connectionResolver->method('resolve')->willReturn($params);
		$this->driverRegistry->method('getDriver')->willReturn($driver);
		$this->storageProvider->method('store')->willReturn('path');

		$this->storageProvider->expects($this->once())
			->method('cleanup')
			->with($connectionName, 0);

		$this->backupManager->backup($connectionName);
	}

	public function testTempFileIsRemovedOnFailure(): void {
		$connectionName = 'default';
		$params         = new ConnectionParams('localhost', '3306', 'db', 'user', 'pass', 'pdo_mysql');
		$driver         = $this->createMock(BackupDriverInterface::class);

		$this->connectionResolver->method('resolve')->willReturn($params);
		$this->driverRegistry->method('getDriver')->willReturn($driver);
		
		$driver->method('dump')->willThrowException(new Exception('Dump failed'));

		// Expect fs->remove() to be called at least once (for the temp file)
		$this->fs->expects($this->atLeastOnce())
			->method('remove');

		$this->fs->method('exists')->willReturn(true);

		$this->expectException(Exception::class);
		$this->backupManager->backup($connectionName);
	}
}
