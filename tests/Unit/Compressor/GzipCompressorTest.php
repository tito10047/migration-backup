<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Compressor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Compressor\GzipCompressor;
use RuntimeException;

class GzipCompressorTest extends TestCase {
	private Filesystem $fs;
	private GzipCompressor $compressor;
	private string $tempFile;

	protected function setUp(): void {
		$this->fs         = new Filesystem();
		$this->compressor = new GzipCompressor($this->fs);
		$this->tempFile   = tempnam(sys_get_temp_dir(), 'test_gzip_');
		file_put_contents($this->tempFile, 'test content');
	}

	protected function tearDown(): void {
		if (file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}

	public function testCompress(): void {
		if (!function_exists('gzopen')) {
			$this->markTestSkipped('zlib extension not available');
		}

		$resultPath = $this->compressor->compress($this->tempFile);

		$this->assertEquals($this->tempFile, $resultPath);
		$this->assertTrue(file_exists($resultPath));
		
		// Check if it's actually compressed
		$handle = gzopen($resultPath, 'rb');
		$content = gzread($handle, 100);
		gzclose($handle);
		
		$this->assertEquals('test content', $content);
	}

	public function testGetExtension(): void {
		$this->assertEquals('.gz', $this->compressor->getExtension());
	}

	public function testIsAvailable(): void {
		$this->assertEquals(function_exists('gzopen'), $this->compressor->isAvailable());
	}
}
