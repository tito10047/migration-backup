<?php

namespace Tito10047\MigrationBackup\Compressor;

use Symfony\Component\Filesystem\Filesystem;
use RuntimeException;

class Bzip2Compressor implements CompressorInterface {
	public function __construct(
		private readonly Filesystem $fs
	) {}

	public function compress(string $path): string {
		if (!$this->isAvailable()) {
			throw new RuntimeException('Bzip2 compression is not available (bz2 extension missing).');
		}

		$bzPath = $path . '.bz2';
		$fp     = bzopen($bzPath, 'w');
		if (!$fp) {
			throw new RuntimeException('Could not open file for compression: ' . $bzPath);
		}

		$handle = fopen($path, 'rb');
		if (!$handle) {
			bzclose($fp);
			throw new RuntimeException('Could not open file for reading: ' . $path);
		}

		while (!feof($handle)) {
			bzwrite($fp, fread($handle, 1024 * 512));
		}

		fclose($handle);
		bzclose($fp);

		$this->fs->remove($path);
		$this->fs->rename($bzPath, $path);

		return $path;
	}

	public function getExtension(): string {
		return '.bz2';
	}

	public function isAvailable(): bool {
		return function_exists('bzopen');
	}
}
