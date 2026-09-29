<?php

namespace Tito10047\MigrationBackup\Exception;

use Exception;

class UnsupportedCompressionException extends Exception {
	public function __construct(string $extension) {
		parent::__construct(sprintf(
			'No decompressor is registered for backup files with the "%s" extension. '
			. 'A custom compressor has to implement %s to be usable for restoring.',
			$extension,
			'Tito10047\MigrationBackup\Compressor\DecompressorInterface',
		));
	}
}
