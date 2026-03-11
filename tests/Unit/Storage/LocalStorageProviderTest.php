<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Storage;

use PHPUnit\Framework\TestCase;
use Tito10047\MigrationBackup\Storage\LocalStorageProvider;
use Symfony\Component\Filesystem\Filesystem;

class LocalStorageProviderTest extends TestCase {
	public function testStore(): void {
		$fs         = $this->createMock(Filesystem::class);
		$sourcePath = '/tmp/source.sql';
		$targetFile = 'target.sql';
		$targetPath = '/backups/target.sql';

		$fs->expects($this->once())
			->method('copy')
			->with($sourcePath, $targetPath, true);

		$provider = new LocalStorageProvider($fs, '/backups');
		$result   = $provider->store($sourcePath, $targetFile);

		$this->assertEquals($targetPath, $result);
	}
}
