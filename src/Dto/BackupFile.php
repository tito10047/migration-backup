<?php

namespace Tito10047\MigrationBackup\Dto;

use DateTimeImmutable;

/**
 * A single backup file found in the storage.
 *
 * File names follow the pattern produced by {@see BackupFile::buildFilename()}:
 * `<connection>-<Y-m-d-H-i-s>.sql<compression extension>`
 */
final readonly class BackupFile {
	public const DATE_FORMAT = 'Y-m-d-H-i-s';

	private const FILENAME_PATTERN = '/^(?P<connection>.+)-(?P<date>\d{4}-\d{2}-\d{2}-\d{2}-\d{2}-\d{2})\.sql(?P<extension>\.[a-z0-9]+)?$/i';

	public function __construct(
		public string            $filename,
		public string            $path,
		public string            $connectionName,
		public DateTimeImmutable $createdAt,
		public int               $size,
		public string            $compressionExtension = '',
	) {}

	public static function buildFilename(string $connectionName, DateTimeImmutable $createdAt, string $compressionExtension = ''): string {
		return $connectionName . '-' . $createdAt->format(self::DATE_FORMAT) . '.sql' . $compressionExtension;
	}

	/**
	 * Builds the DTO from an existing file, or returns null when the file does not
	 * exist or its name was not produced by this bundle.
	 */
	public static function fromPath(string $path): ?self {
		if (!is_file($path)) {
			return null;
		}

		$filename = basename($path);

		if (!preg_match(self::FILENAME_PATTERN, $filename, $matches)) {
			return null;
		}

		$createdAt = DateTimeImmutable::createFromFormat(self::DATE_FORMAT, $matches['date']);
		if ($createdAt === false) {
			return null;
		}

		return new self(
			$filename,
			$path,
			$matches['connection'],
			$createdAt,
			(int)(filesize($path) ?: 0),
			$matches['extension'] ?? '',
		);
	}

	public function isCompressed(): bool {
		return $this->compressionExtension !== '';
	}
}
