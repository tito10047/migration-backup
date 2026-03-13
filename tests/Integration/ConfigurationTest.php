<?php

namespace Tito10047\MigrationBackup\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Tito10047\MigrationBackup\Tests\Fixture\TestKernel;

class ConfigurationTest extends KernelTestCase {

	protected static function createKernel(array $options = []): TestKernel {
		return new TestKernel(
			$options['environment'] ?? 'test',
			$options['debug'] ?? true,
			$options['db_config'] ?? [],
			$options['migration_backup_config'] ?? []
		);
	}

	public function testInvalidCompressionFormatThrowsException(): void {
		$this->expectException(InvalidConfigurationException::class);
		$this->expectExceptionMessage('The value "invalid_format" is not allowed for path "migration_backup.compression_format". Permissible values: "gzip", "bzip2", "zstd", "zip", "lz4", "none"');

		self::bootKernel([
			'migration_backup_config' => [
				'compression_format' => 'invalid_format'
			]
		]);
	}

	public function testValidCompressionFormats(): void {
		foreach (['gzip', 'bzip2', 'zstd', 'zip', 'lz4', 'none'] as $format) {
			self::bootKernel([
				'migration_backup_config' => [
					'compression_format' => $format
				]
			]);
			$this->addToAssertionCount(1);
			self::ensureKernelShutdown();
		}
	}
}
