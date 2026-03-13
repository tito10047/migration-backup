<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Compressor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Compressor\Bzip2Compressor;

class Bzip2CompressorTest extends TestCase {
	private Filesystem $fs;
	private Bzip2Compressor $compressor;
	private string $tempFile;

	protected function setUp(): void {
		$this->fs         = new Filesystem();
		$this->compressor = new Bzip2Compressor($this->fs);
		$this->tempFile   = tempnam(sys_get_temp_dir(), 'test_bz2_');
		file_put_contents($this->tempFile, 'test content');
	}

	protected function tearDown(): void {
		if (file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}

	public function testCompress(): void {
		if (!function_exists('bzopen')) {
			$this->markTestSkipped('bz2 extension not available');
		}

		$resultPath = $this->compressor->compress($this->tempFile);

		$this->assertEquals($this->tempFile, $resultPath);
		$this->assertTrue(file_exists($resultPath));
		
		$handle = bzopen($resultPath, 'r');
		$content = bzread($handle, 100);
		bzclose($handle);
		
		$this->assertEquals('test content', $content);
	}

	public function testGetExtension(): void {
		$this->assertEquals('.bz2', $this->compressor->getExtension());
	}

	public function testIsAvailable(): void {
		$this->assertEquals(function_exists('bzopen'), $this->compressor->isAvailable());
	}
}
