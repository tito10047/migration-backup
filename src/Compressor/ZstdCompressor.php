<?php

namespace Tito10047\MigrationBackup\Compressor;

use Symfony\Component\Filesystem\Filesystem;
use RuntimeException;

class ZstdCompressor implements CompressorInterface {
	public function __construct(
		private readonly Filesystem $fs
	) {}

	public function compress(string $path): string {
		if (!$this->isAvailable()) {
			throw new RuntimeException('Zstd compression is not available (zstd extension missing).');
		}

		$data = file_get_contents($path);
		if ($data === false) {
			throw new RuntimeException('Could not read file for compression: ' . $path);
		}

		$compressed = zstd_compress($data, 3);
		if ($compressed === false) {
			throw new RuntimeException('Zstd compression failed for file: ' . $path);
		}

		$this->fs->dumpFile($path, $compressed);

		return $path;
	}

	public function getExtension(): string {
		return '.zst';
	}

	public function isAvailable(): bool {
		return function_exists('zstd_compress');
	}
}
