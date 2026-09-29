<?php

namespace Tito10047\MigrationBackup\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Compressor\GzipCompressor;
use Tito10047\MigrationBackup\Compressor\NoneCompressor;
use Tito10047\MigrationBackup\Compressor\ZipCompressor;
use Tito10047\MigrationBackup\Exception\UnsupportedCompressionException;
use Tito10047\MigrationBackup\Registry\DecompressorRegistry;
use Tito10047\MigrationBackup\Tests\Fixture\LegacyCompressor;

class DecompressorRegistryTest extends TestCase {
	private DecompressorRegistry $registry;
	private GzipCompressor $gzip;
	private ZipCompressor $zip;

	protected function setUp(): void {
		$fs             = new Filesystem();
		$this->gzip     = new GzipCompressor($fs);
		$this->zip      = new ZipCompressor($fs);
		$this->registry = new DecompressorRegistry([$this->gzip, $this->zip, new NoneCompressor()]);
	}

	public function testGetForExtension(): void {
		$this->assertSame($this->gzip, $this->registry->getForExtension('.gz'));
		$this->assertSame($this->zip, $this->registry->getForExtension('.zip'));
	}

	public function testGetForExtensionIsCaseInsensitive(): void {
		$this->assertSame($this->gzip, $this->registry->getForExtension('.GZ'));
	}

	public function testEmptyExtensionReturnsNoneCompressor(): void {
		$this->assertInstanceOf(NoneCompressor::class, $this->registry->getForExtension(''));
	}

	public function testUnknownExtensionThrows(): void {
		$this->expectException(UnsupportedCompressionException::class);
		$this->expectExceptionMessage('.rar');

		$this->registry->getForExtension('.rar');
	}

	public function testCompressorWithoutDecompressSupportIsIgnored(): void {
		$registry = new DecompressorRegistry([new LegacyCompressor()]);

		$this->expectException(UnsupportedCompressionException::class);
		$registry->getForExtension('.legacy');
	}
}
