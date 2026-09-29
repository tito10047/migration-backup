<?php

namespace Tito10047\MigrationBackup\Tests\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Tests\Fixture\TestKernel;

/**
 * The full round trip against a real MySQL server: mysqldump writes the backup,
 * the mysql client loads it back.
 */
class MysqlRestoreTest extends KernelTestCase {
	private const BACKUP_PATH = '/var/test-backups-mysql';
	private const TABLE       = 'migration_backup_restore_test';

	private Filesystem $fs;
	private string $backupPath;
	private Connection $connection;

	protected static function createKernel(array $options = []): TestKernel {
		return new TestKernel(
			$options['environment'] ?? 'test',
			$options['debug'] ?? true,
			$options['db_config'] ?? [],
			$options['migration_backup_config'] ?? ['backup_path' => '%kernel.project_dir%' . self::BACKUP_PATH],
		);
	}

	protected function setUp(): void {
		$dbUrl = $_SERVER['DATABASE_URL'] ?? $_ENV['DATABASE_URL'] ?? null;
		if (!is_string($dbUrl) || !str_starts_with($dbUrl, 'mysql')) {
			$this->markTestSkipped('DATABASE_URL for mysql not found');
		}

		self::bootKernel(['db_config' => ['url' => $dbUrl]]);

		$this->fs         = new Filesystem();
		$this->backupPath = self::$kernel->getProjectDir() . self::BACKUP_PATH;
		$this->fs->remove($this->backupPath);

		/** @var ManagerRegistry $registry */
		$registry = self::getContainer()->get('doctrine');
		/** @var Connection $connection */
		$connection       = $registry->getConnection();
		$this->connection = $connection;

		$this->connection->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE);
		$this->connection->executeStatement('CREATE TABLE ' . self::TABLE . ' (id INT PRIMARY KEY, note VARCHAR(255) NOT NULL)');
	}

	protected function tearDown(): void {
		if (isset($this->connection)) {
			$this->connection->executeStatement('DROP TABLE IF EXISTS ' . self::TABLE);
		}
		if (isset($this->fs)) {
			$this->fs->remove($this->backupPath);
		}
		parent::tearDown();
	}

	public function testRestoreBringsBackTheDroppedRows(): void {
		$this->connection->executeStatement('INSERT INTO ' . self::TABLE . ' (id, note) VALUES (1, "before the migration")');

		$this->runCommand('migration-backup:backup')->assertCommandIsSuccessful();

		$this->connection->executeStatement('UPDATE ' . self::TABLE . ' SET note = "broken by the migration" WHERE id = 1');
		$this->connection->executeStatement('INSERT INTO ' . self::TABLE . ' (id, note) VALUES (2, "added by the migration")');

		$this->runCommand('migration-backup:restore', ['--latest' => true, '--force' => true])->assertCommandIsSuccessful();

		$rows = $this->connection->fetchAllKeyValue('SELECT id, note FROM ' . self::TABLE . ' ORDER BY id');

		$this->assertSame([1 => 'before the migration'], $rows);
	}

	/**
	 * @param array<string, mixed> $input
	 */
	private function runCommand(string $command, array $input = []): CommandTester {
		$application = new Application(self::$kernel);
		$application->setAutoExit(false);

		$tester = new CommandTester($application->find($command));
		$tester->execute($input, ['interactive' => false]);

		return $tester;
	}
}
