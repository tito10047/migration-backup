<?php

namespace Tito10047\MigrationBackup\Tests\Unit;

use DateTimeImmutable;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tito10047\MigrationBackup\Compressor\DecompressorInterface;
use Tito10047\MigrationBackup\Driver\RestoreDriverInterface;
use Tito10047\MigrationBackup\Dto\BackupFile;
use Tito10047\MigrationBackup\Dto\ConnectionParams;
use Tito10047\MigrationBackup\Event\RestoreFailedEvent;
use Tito10047\MigrationBackup\Event\RestoreFinishedEvent;
use Tito10047\MigrationBackup\Event\RestoreStartedEvent;
use Tito10047\MigrationBackup\Exception\BackupNotFoundException;
use Tito10047\MigrationBackup\Registry\DecompressorRegistryInterface;
use Tito10047\MigrationBackup\Registry\RestoreDriverRegistryInterface;
use Tito10047\MigrationBackup\Resolver\ConnectionResolverInterface;
use Tito10047\MigrationBackup\RestoreManager;
use Tito10047\MigrationBackup\Storage\ListableStorageProviderInterface;

class RestoreManagerTest extends TestCase {
	private ConnectionResolverInterface $connectionResolver;
	private RestoreDriverRegistryInterface $driverRegistry;
	private ListableStorageProviderInterface $storageProvider;
	private DecompressorRegistryInterface $decompressorRegistry;
	private EventDispatcherInterface $eventDispatcher;
	private RestoreManager $restoreManager;
	private ConnectionParams $params;

	/** @var list<object> */
	private array $dispatchedEvents = [];

	protected function setUp(): void {
		$this->connectionResolver   = $this->createMock(ConnectionResolverInterface::class);
		$this->driverRegistry       = $this->createMock(RestoreDriverRegistryInterface::class);
		$this->storageProvider      = $this->createMock(ListableStorageProviderInterface::class);
		$this->decompressorRegistry = $this->createMock(DecompressorRegistryInterface::class);
		$this->eventDispatcher      = $this->createMock(EventDispatcherInterface::class);
		$this->params               = new ConnectionParams('localhost', '3306', 'db', 'user', 'pass', 'pdo_mysql');

		$this->dispatchedEvents = [];
		$this->eventDispatcher->method('dispatch')->willReturnCallback(function (object $event): object {
			$this->dispatchedEvents[] = $event;

			return $event;
		});

		$this->restoreManager = new RestoreManager(
			$this->connectionResolver,
			$this->driverRegistry,
			$this->storageProvider,
			$this->decompressorRegistry,
			$this->eventDispatcher,
			new Filesystem(),
		);
	}

	public function testRestoreDecompressesAWorkingCopyAndFeedsItToTheDriver(): void {
		$file   = $this->backupFile('.gz');
		$driver = $this->createMock(RestoreDriverInterface::class);

		$this->connectionResolver->expects($this->once())->method('resolve')->with('default')->willReturn($this->params);
		$this->driverRegistry->expects($this->once())->method('getDriver')->with('pdo_mysql')->willReturn($driver);

		$decompressor = $this->createMock(DecompressorInterface::class);
		$this->decompressorRegistry->expects($this->once())
			->method('getForExtension')
			->with('.gz')
			->willReturn($decompressor);

		$this->storageProvider->expects($this->once())
			->method('fetch')
			->willReturnCallback(static function (BackupFile $file, string $target): void {
				file_put_contents($target, 'compressed dump');
			});

		$decompressor->expects($this->once())
			->method('decompress')
			->willReturnCallback(static function (string $path): string {
				file_put_contents($path, 'plain dump');

				return $path;
			});

		$workingCopy = null;
		$driver->expects($this->once())
			->method('restore')
			->willReturnCallback(function (ConnectionParams $params, string $path) use (&$workingCopy): void {
				$this->assertSame($this->params, $params);
				$this->assertSame('plain dump', file_get_contents($path));
				$workingCopy = $path;
			});

		$this->restoreManager->restore($file);

		$this->assertNotNull($workingCopy);
		$this->assertFileDoesNotExist($workingCopy, 'the working copy has to be cleaned up');
	}

	public function testRestoreDispatchesStartedAndFinishedEvents(): void {
		$file = $this->backupFile();
		$this->givenRestoreSucceeds();

		$this->restoreManager->restore($file);

		$this->assertCount(2, $this->dispatchedEvents);
		$this->assertInstanceOf(RestoreStartedEvent::class, $this->dispatchedEvents[0]);
		$this->assertInstanceOf(RestoreFinishedEvent::class, $this->dispatchedEvents[1]);
		$this->assertSame($file, $this->dispatchedEvents[1]->file);
		$this->assertSame('default', $this->dispatchedEvents[1]->connectionName);
	}

	public function testRestoreDispatchesFailedEventAndRethrows(): void {
		$this->connectionResolver->method('resolve')->willReturn($this->params);
		$this->driverRegistry->method('getDriver')->willThrowException(new Exception('boom'));

		try {
			$this->restoreManager->restore($this->backupFile());
			$this->fail('the exception should have been re-thrown');
		} catch (Exception $e) {
			$this->assertSame('boom', $e->getMessage());
		}

		$this->assertCount(2, $this->dispatchedEvents);
		$this->assertInstanceOf(RestoreFailedEvent::class, $this->dispatchedEvents[1]);
		$this->assertSame('boom', $this->dispatchedEvents[1]->exception->getMessage());
	}

	public function testRestoreRemovesTheWorkingCopyOnFailure(): void {
		$driver       = $this->createMock(RestoreDriverInterface::class);
		$decompressor = $this->createMock(DecompressorInterface::class);

		$this->connectionResolver->method('resolve')->willReturn($this->params);
		$this->driverRegistry->method('getDriver')->willReturn($driver);
		$this->decompressorRegistry->method('getForExtension')->willReturn($decompressor);
		$decompressor->method('decompress')->willReturnArgument(0);

		$workingCopy = null;
		$this->storageProvider->method('fetch')->willReturnCallback(static function (BackupFile $file, string $target) use (&$workingCopy): void {
			file_put_contents($target, 'dump');
			$workingCopy = $target;
		});
		$driver->method('restore')->willThrowException(new Exception('restore failed'));

		$this->expectException(Exception::class);

		try {
			$this->restoreManager->restore($this->backupFile());
		} finally {
			$this->assertNotNull($workingCopy);
			$this->assertFileDoesNotExist($workingCopy);
		}
	}

	public function testRestoreCanTargetADifferentConnection(): void {
		$this->connectionResolver->expects($this->once())->method('resolve')->with('secondary')->willReturn($this->params);
		$this->givenRestoreSucceeds();

		$this->restoreManager->restore($this->backupFile(), 'secondary');

		$this->assertSame('secondary', $this->dispatchedEvents[0]->connectionName);
	}

	public function testLatestReturnsTheNewestBackup(): void {
		$newest = $this->backupFile('', '2024-03-13-10-00-00');
		$this->storageProvider->expects($this->once())
			->method('list')
			->with('default')
			->willReturn([$newest, $this->backupFile('', '2024-03-11-10-00-00')]);

		$this->assertSame($newest, $this->restoreManager->latest('default'));
	}

	public function testLatestThrowsWhenThereIsNoBackup(): void {
		$this->storageProvider->method('list')->willReturn([]);

		$this->expectException(BackupNotFoundException::class);
		$this->expectExceptionMessage('default');

		$this->restoreManager->latest('default');
	}

	public function testListDelegatesToStorage(): void {
		$files = [$this->backupFile()];
		$this->storageProvider->expects($this->once())->method('list')->with(null)->willReturn($files);

		$this->assertSame($files, $this->restoreManager->list());
	}

	public function testGetDelegatesToStorage(): void {
		$file = $this->backupFile();
		$this->storageProvider->expects($this->once())
			->method('get')
			->with('default-2024-03-11-10-00-00.sql')
			->willReturn($file);

		$this->assertSame($file, $this->restoreManager->get('default-2024-03-11-10-00-00.sql'));
	}

	private function givenRestoreSucceeds(): void {
		$driver       = $this->createMock(RestoreDriverInterface::class);
		$decompressor = $this->createMock(DecompressorInterface::class);

		$this->connectionResolver->method('resolve')->willReturn($this->params);
		$this->driverRegistry->method('getDriver')->willReturn($driver);
		$this->decompressorRegistry->method('getForExtension')->willReturn($decompressor);
		$decompressor->method('decompress')->willReturnArgument(0);
		$this->storageProvider->method('fetch')->willReturnCallback(static function (BackupFile $file, string $target): void {
			file_put_contents($target, 'dump');
		});
	}

	private function backupFile(string $extension = '', string $date = '2024-03-11-10-00-00'): BackupFile {
		return new BackupFile(
			'default-' . $date . '.sql' . $extension,
			'/backups/default-' . $date . '.sql' . $extension,
			'default',
			DateTimeImmutable::createFromFormat(BackupFile::DATE_FORMAT, $date),
			42,
			$extension,
		);
	}
}
