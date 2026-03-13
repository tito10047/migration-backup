<?php

namespace Tito10047\MigrationBackup\Compressor;

interface CompressorInterface {
	public function compress(string $path): string;
	public function getExtension(): string;
	public function isAvailable(): bool;
}
