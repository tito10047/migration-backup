<?php

namespace Tito10047\MigrationBackup\Compressor;

/**
 * Undoes what a {@see CompressorInterface} did, so a backup can be restored.
 *
 * It is a separate interface on purpose: custom compressors written against
 * older versions of this bundle keep working for backups, they just cannot be
 * used for restoring until they implement this interface as well.
 */
interface DecompressorInterface {
	/**
	 * Decompresses the file in place and returns its path.
	 */
	public function decompress(string $path): string;
}
