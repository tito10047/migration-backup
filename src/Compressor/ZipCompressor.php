<?php

namespace Tito10047\MigrationBackup\Compressor;

use Symfony\Component\Filesystem\Filesystem;
use RuntimeException;
use ZipArchive;

class ZipCompressor implements CompressorInterface {
	public function __construct(
		private readonly Filesystem $fs
	) {}

	public function compress(string $path): string {
		if (!$this->isAvailable()) {
			throw new RuntimeException('Zip compression is not available (zip extension missing).');
		}

		$zipPath = $path . '.zip';
		$zip     = new ZipArchive();
		if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			throw new RuntimeException('Could not open file for compression: ' . $zipPath);
		}

		$zip->addFile($path, basename($path));
		$zip->close();

		$this->fs->remove($path);
		$this->fs->rename($zipPath, $path);

		return $path;
	}

	public function getExtension(): string {
		return '.zip';
	}

	public function isAvailable(): bool {
		return class_exists(ZipArchive::class);
	}
}
