<?php

namespace Tito10047\MigrationBackup\Compressor;

use Symfony\Component\Filesystem\Filesystem;
use RuntimeException;

class Lz4Compressor implements CompressorInterface {
	public function __construct(
		private readonly Filesystem $fs
	) {}

	public function compress(string $path): string {
		if (!$this->isAvailable()) {
			throw new RuntimeException('LZ4 compression is not available (lz4 extension missing).');
		}

		$data = file_get_contents($path);
		if ($data === false) {
			throw new RuntimeException('Could not read file for compression: ' . $path);
		}

		$compressed = lz4_compress($data);
		if ($compressed === false) {
			throw new RuntimeException('LZ4 compression failed for file: ' . $path);
		}

		$this->fs->dumpFile($path, $compressed);

		return $path;
	}

	public function getExtension(): string {
		return '.lz4';
	}

	public function isAvailable(): bool {
		return function_exists('lz4_compress');
	}
}
