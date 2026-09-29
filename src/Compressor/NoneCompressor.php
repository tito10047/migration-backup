<?php

namespace Tito10047\MigrationBackup\Compressor;

class NoneCompressor implements CompressorInterface, DecompressorInterface {
	public function compress(string $path): string {
		return $path;
	}

	public function decompress(string $path): string {
		return $path;
	}

	public function getExtension(): string {
		return '';
	}

	public function isAvailable(): bool {
		return true;
	}
}
