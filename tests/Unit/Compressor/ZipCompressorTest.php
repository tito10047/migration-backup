<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Compressor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Compressor\ZipCompressor;
use ZipArchive;

class ZipCompressorTest extends TestCase {
	private Filesystem $fs;
	private ZipCompressor $compressor;
	private string $tempFile;

	protected function setUp(): void {
		$this->fs         = new Filesystem();
		$this->compressor = new ZipCompressor($this->fs);
		$this->tempFile   = tempnam(sys_get_temp_dir(), 'test_zip_');
		file_put_contents($this->tempFile, 'test content');
	}

	protected function tearDown(): void {
		if (file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}

	public function testCompress(): void {
		if (!class_exists(ZipArchive::class)) {
			$this->markTestSkipped('zip extension not available');
		}

		$resultPath = $this->compressor->compress($this->tempFile);

		$this->assertEquals($this->tempFile, $resultPath);
		$this->assertTrue(file_exists($resultPath));
		
		$zip = new ZipArchive();
		if ($zip->open($resultPath) === true) {
			$content = $zip->getFromIndex(0);
			$zip->close();
			$this->assertEquals('test content', $content);
		} else {
			$this->fail('Could not open zip file');
		}
	}

	public function testGetExtension(): void {
		$this->assertEquals('.zip', $this->compressor->getExtension());
	}

	public function testIsAvailable(): void {
		$this->assertEquals(class_exists(ZipArchive::class), $this->compressor->isAvailable());
	}
}
