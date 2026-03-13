<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Compressor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Compressor\Lz4Compressor;

class Lz4CompressorTest extends TestCase {
	private Filesystem $fs;
	private Lz4Compressor $compressor;
	private string $tempFile;

	protected function setUp(): void {
		$this->fs         = new Filesystem();
		$this->compressor = new Lz4Compressor($this->fs);
		$this->tempFile   = tempnam(sys_get_temp_dir(), 'test_lz4_');
		file_put_contents($this->tempFile, 'test content');
	}

	protected function tearDown(): void {
		if (file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}

	public function testCompress(): void {
		if (!function_exists('lz4_compress')) {
			$this->markTestSkipped('lz4 extension not available');
		}

		$resultPath = $this->compressor->compress($this->tempFile);

		$this->assertEquals($this->tempFile, $resultPath);
		$this->assertTrue(file_exists($resultPath));
		
		$compressedContent = file_get_contents($resultPath);
		$content = lz4_uncompress($compressedContent);
		
		$this->assertEquals('test content', $content);
	}

	public function testGetExtension(): void {
		$this->assertEquals('.lz4', $this->compressor->getExtension());
	}

	public function testIsAvailable(): void {
		$this->assertEquals(function_exists('lz4_compress'), $this->compressor->isAvailable());
	}
}
