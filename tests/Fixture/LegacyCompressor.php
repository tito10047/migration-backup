<?php

namespace Tito10047\MigrationBackup\Tests\Fixture;

use Tito10047\MigrationBackup\Compressor\CompressorInterface;

/**
 * A custom compressor written against the pre-restore API: it can compress but
 * not decompress.
 */
class LegacyCompressor implements CompressorInterface {
	public function compress(string $path): string {
		return $path;
	}

	public function getExtension(): string {
		return '.legacy';
	}

	public function isAvailable(): bool {
		return true;
	}
}
