<?php

namespace Tito10047\MigrationBackup\Registry;

use Tito10047\MigrationBackup\Compressor\DecompressorInterface;
use Tito10047\MigrationBackup\Exception\UnsupportedCompressionException;

interface DecompressorRegistryInterface {
	/**
	 * @param string $extension compression extension including the dot ('.gz'), or an empty string for uncompressed backups
	 *
	 * @throws UnsupportedCompressionException
	 */
	public function getForExtension(string $extension): DecompressorInterface;
}
