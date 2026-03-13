<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Compressor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Compressor\ZstdCompressor;

class ZstdCompressorTest extends TestCase {
	private Filesystem $fs;
	private ZstdCompressor $compressor;
	private string $tempFile;

	protected function setUp(): void {
		$this->fs         = new Filesystem();
		$this->compressor = new ZstdCompressor($this->fs);
		$this->tempFile   = tempnam(sys_get_temp_dir(), 'test_zstd_');
		file_put_contents($this->tempFile, 'test content');
	}

	protected function tearDown(): void {
		if (file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}

	public function testCompress(): void {
		if (!function_exists('zstd_compress')) {
			$this->markTestSkipped('zstd extension not available');
		}

		$resultPath = $this->compressor->compress($this->tempFile);

		$this->assertEquals($this->tempFile, $resultPath);
		$this->assertTrue(file_exists($resultPath));
		
		$compressedContent = file_get_contents($resultPath);
		$content = zstd_uncompress($compressedContent);
		
		$this->assertEquals('test content', $content);
	}

	public function testGetExtension(): void {
		$this->assertEquals('.zst', $this->compressor->getExtension());
	}

	public function testIsAvailable(): void {
		$this->assertEquals(function_exists('zstd_compress'), $this->compressor->isAvailable());
	}
}
