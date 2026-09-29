<?php

namespace Tito10047\MigrationBackup\Compressor;

use Symfony\Component\Filesystem\Filesystem;
use RuntimeException;

class GzipCompressor implements CompressorInterface, DecompressorInterface {
	public function __construct(
		private readonly Filesystem $fs
	) {}

	public function compress(string $path): string {
		if (!$this->isAvailable()) {
			throw new RuntimeException('Gzip compression is not available (zlib extension missing).');
		}

		$gzPath = $path . '.gz';
		$fp     = gzopen($gzPath, 'w9');
		if (!$fp) {
			throw new RuntimeException('Could not open file for compression: ' . $gzPath);
		}

		$handle = fopen($path, 'rb');
		if (!$handle) {
			gzclose($fp);
			throw new RuntimeException('Could not open file for reading: ' . $path);
		}

		while (!feof($handle)) {
			gzwrite($fp, fread($handle, 1024 * 512));
		}

		fclose($handle);
		gzclose($fp);

		$this->fs->remove($path);
		$this->fs->rename($gzPath, $path);

		return $path;
	}

	public function decompress(string $path): string {
		if (!$this->isAvailable()) {
			throw new RuntimeException('Gzip compression is not available (zlib extension missing).');
		}

		$plainPath = $path . '.plain';
		$fp        = gzopen($path, 'rb');
		if (!$fp) {
			throw new RuntimeException('Could not open file for decompression: ' . $path);
		}

		$handle = fopen($plainPath, 'wb');
		if (!$handle) {
			gzclose($fp);
			throw new RuntimeException('Could not open file for writing: ' . $plainPath);
		}

		while (!gzeof($fp)) {
			fwrite($handle, gzread($fp, 1024 * 512));
		}

		fclose($handle);
		gzclose($fp);

		$this->fs->remove($path);
		$this->fs->rename($plainPath, $path);

		return $path;
	}

	public function getExtension(): string {
		return '.gz';
	}

	public function isAvailable(): bool {
		return function_exists('gzopen');
	}
}
