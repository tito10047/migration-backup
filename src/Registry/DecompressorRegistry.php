<?php

namespace Tito10047\MigrationBackup\Registry;

use Tito10047\MigrationBackup\Compressor\CompressorInterface;
use Tito10047\MigrationBackup\Compressor\DecompressorInterface;
use Tito10047\MigrationBackup\Exception\UnsupportedCompressionException;

/**
 * Picks the decompressor by the extension of an existing backup file, which is
 * not necessarily the format currently configured for new backups.
 */
class DecompressorRegistry implements DecompressorRegistryInterface {
	/**
	 * @param iterable<CompressorInterface> $compressors
	 */
	public function __construct(
		private readonly iterable $compressors
	) {}

	public function getForExtension(string $extension): DecompressorInterface {
		$extension = strtolower($extension);

		foreach ($this->compressors as $compressor) {
			if (!$compressor instanceof DecompressorInterface) {
				continue;
			}

			if (strtolower($compressor->getExtension()) === $extension) {
				return $compressor;
			}
		}

		throw new UnsupportedCompressionException($extension);
	}
}
