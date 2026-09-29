<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Compressor;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Compressor\Bzip2Compressor;
use Tito10047\MigrationBackup\Compressor\CompressorInterface;
use Tito10047\MigrationBackup\Compressor\DecompressorInterface;
use Tito10047\MigrationBackup\Compressor\GzipCompressor;
use Tito10047\MigrationBackup\Compressor\Lz4Compressor;
use Tito10047\MigrationBackup\Compressor\NoneCompressor;
use Tito10047\MigrationBackup\Compressor\ZipCompressor;
use Tito10047\MigrationBackup\Compressor\ZstdCompressor;

/**
 * Every shipped compressor must be able to undo its own work, otherwise a backup
 * cannot be restored.
 */
class DecompressorTest extends TestCase {
	private string $tempFile;

	protected function setUp(): void {
		$this->tempFile = tempnam(sys_get_temp_dir(), 'test_decompress_');
	}

	protected function tearDown(): void {
		if (file_exists($this->tempFile)) {
			unlink($this->tempFile);
		}
	}

	/**
	 * @return array<string, array{class-string<CompressorInterface>}>
	 */
	public static function compressorProvider(): array {
		return [
			'gzip'  => [GzipCompressor::class],
			'bzip2' => [Bzip2Compressor::class],
			'zstd'  => [ZstdCompressor::class],
			'zip'   => [ZipCompressor::class],
			'lz4'   => [Lz4Compressor::class],
			'none'  => [NoneCompressor::class],
		];
	}

	/**
	 * @dataProvider compressorProvider
	 *
	 * @param class-string<CompressorInterface> $class
	 */
	public function testRoundTrip(string $class): void {
		$compressor = $this->createCompressor($class);

		if (!$compressor->isAvailable()) {
			$this->markTestSkipped($class . ' is not available in this environment');
		}

		$content = str_repeat("INSERT INTO product VALUES (1, 'test');\n", 100);
		file_put_contents($this->tempFile, $content);

		$compressor->compress($this->tempFile);
		$resultPath = $compressor->decompress($this->tempFile);

		$this->assertEquals($this->tempFile, $resultPath);
		$this->assertEquals($content, file_get_contents($this->tempFile));
	}

	/**
	 * @dataProvider compressorProvider
	 *
	 * @param class-string<CompressorInterface> $class
	 */
	public function testCompressorIsDecompressor(string $class): void {
		$this->assertInstanceOf(DecompressorInterface::class, $this->createCompressor($class));
	}

	public function testNoneCompressorLeavesFileUntouched(): void {
		file_put_contents($this->tempFile, 'plain dump');

		(new NoneCompressor())->decompress($this->tempFile);

		$this->assertEquals('plain dump', file_get_contents($this->tempFile));
	}

	/**
	 * @param class-string<CompressorInterface> $class
	 */
	private function createCompressor(string $class): CompressorInterface {
		return $class === NoneCompressor::class ? new NoneCompressor() : new $class(new Filesystem());
	}
}
