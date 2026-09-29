<?php

namespace Tito10047\MigrationBackup\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Tito10047\MigrationBackup\Tests\Fixture\SectionlessConsoleOutput;
use Tito10047\MigrationBackup\Tests\Fixture\TestKernel;

/**
 * Backup, list, restore and clean, exercised the way a user runs them: through
 * the console, on a real (SQLite) database, with gzip compression turned on.
 */
class CommandTest extends KernelTestCase {
	private const BACKUP_PATH = '/var/test-backups';

	private Filesystem $fs;
	private string $backupPath;
	private string $databasePath;

	protected static function createKernel(array $options = []): TestKernel {
		return new TestKernel(
			$options['environment'] ?? 'test',
			$options['debug'] ?? true,
			$options['db_config'] ?? [],
			$options['migration_backup_config'] ?? ['backup_path' => '%kernel.project_dir%' . self::BACKUP_PATH],
		);
	}

	protected function setUp(): void {
		self::bootKernel();

		$this->fs           = new Filesystem();
		$this->backupPath   = self::$kernel->getProjectDir() . self::BACKUP_PATH;
		$this->databasePath = self::$kernel->getProjectDir() . '/var/data.db';

		$this->fs->remove($this->backupPath);
	}

	protected function tearDown(): void {
		$this->fs->remove($this->backupPath);
		// The restore tests write plain text into the database file; an empty file
		// is a valid empty SQLite database, so the next test starts from a sane one.
		file_put_contents($this->databasePath, '');
		parent::tearDown();
	}

	public function testBackupCommandCreatesABackup(): void {
		$tester = $this->runCommand('migration-backup:backup');

		$tester->assertCommandIsSuccessful();
		$this->assertStringContainsString('default', $tester->getDisplay());
		$this->assertCount(1, $this->storedBackups());
	}

	public function testListCommandShowsTheBackup(): void {
		$this->runCommand('migration-backup:backup');

		$tester = $this->runCommand('migration-backup:list');

		$tester->assertCommandIsSuccessful();
		$this->assertStringContainsString(basename($this->storedBackups()[0]), $tester->getDisplay());
		$this->assertStringContainsString('default', $tester->getDisplay());
	}

	public function testListCommandTellsWhenThereIsNothing(): void {
		$tester = $this->runCommand('migration-backup:list');

		$tester->assertCommandIsSuccessful();
		$this->assertStringContainsString('No backup', $tester->getDisplay());
	}

	/**
	 * `SymfonyStyle::table()` renders into a console section, which the output
	 * behind `CommandTester` refuses to create — so a table rendered that way
	 * makes the command untestable for anyone using the bundle.
	 */
	public function testListCommandRendersWithoutConsoleSections(): void {
		$this->runCommand('migration-backup:backup');

		$application = new Application(self::$kernel);
		$application->setAutoExit(false);

		$output = new SectionlessConsoleOutput(fopen('php://memory', 'w+'));
		$input  = new ArrayInput([]);
		$input->setInteractive(false);

		$status = $application->find('migration-backup:list')->run($input, $output);

		$this->assertSame(Command::SUCCESS, $status);
		$this->assertStringContainsString(basename($this->storedBackups()[0]), $output->fetch());
	}

	public function testRestoreCommandBringsTheDatabaseBack(): void {
		file_put_contents($this->databasePath, 'the good database');
		$this->runCommand('migration-backup:backup');

		file_put_contents($this->databasePath, 'the broken database');
		$tester = $this->runCommand('migration-backup:restore', ['--latest' => true, '--force' => true]);

		$tester->assertCommandIsSuccessful();
		$this->assertSame('the good database', file_get_contents($this->databasePath));
	}

	public function testRestoreCommandAcceptsAFilename(): void {
		file_put_contents($this->databasePath, 'the good database');
		$this->runCommand('migration-backup:backup');
		$filename = basename($this->storedBackups()[0]);

		file_put_contents($this->databasePath, 'the broken database');
		$tester = $this->runCommand('migration-backup:restore', ['--file' => $filename, '--force' => true]);

		$tester->assertCommandIsSuccessful();
		$this->assertSame('the good database', file_get_contents($this->databasePath));
	}

	public function testRestoreCommandLeavesTheBackupFileInPlace(): void {
		$this->runCommand('migration-backup:backup');
		$backup = $this->storedBackups()[0];
		$before = file_get_contents($backup);

		$this->runCommand('migration-backup:restore', ['--latest' => true, '--force' => true]);

		$this->assertSame($before, file_get_contents($backup), 'restoring must not consume the backup');
	}

	public function testRestoreCommandRefusesToRunUnattendedWithoutForce(): void {
		$this->runCommand('migration-backup:backup');

		$tester = $this->runCommand('migration-backup:restore', ['--latest' => true]);

		$this->assertSame(Command::FAILURE, $tester->getStatusCode());
		$this->assertStringContainsString('--force', $tester->getDisplay());
	}

	public function testRestoreCommandFailsWhenThereIsNoBackup(): void {
		$tester = $this->runCommand('migration-backup:restore', ['--latest' => true, '--force' => true]);

		$this->assertSame(Command::FAILURE, $tester->getStatusCode());
		$this->assertStringContainsString('no backup', strtolower($tester->getDisplay()));
	}

	public function testRestoreCommandFailsForAnUnknownFile(): void {
		$tester = $this->runCommand('migration-backup:restore', ['--file' => 'default-2024-03-11-10-00-00.sql', '--force' => true]);

		$this->assertSame(Command::FAILURE, $tester->getStatusCode());
		$this->assertStringContainsString('not found', $tester->getDisplay());
	}

	public function testRestoreCommandLetsTheUserPickABackupAndConfirm(): void {
		file_put_contents($this->databasePath, 'the good database');
		$this->runCommand('migration-backup:backup');

		file_put_contents($this->databasePath, 'the broken database');
		// First answer picks the only offered backup, the second confirms the overwrite.
		$tester = $this->runInteractively('migration-backup:restore', ['0', 'yes']);

		$tester->assertCommandIsSuccessful();
		$this->assertStringContainsString('Which backup do you want to restore?', $tester->getDisplay());
		$this->assertSame('the good database', file_get_contents($this->databasePath));
	}

	public function testRestoreCommandDoesNothingWhenTheUserSaysNo(): void {
		file_put_contents($this->databasePath, 'the good database');
		$this->runCommand('migration-backup:backup');

		file_put_contents($this->databasePath, 'the broken database');
		$tester = $this->runInteractively('migration-backup:restore', ['0', 'no']);

		$this->assertSame(Command::FAILURE, $tester->getStatusCode());
		$this->assertSame('the broken database', file_get_contents($this->databasePath));
	}

	public function testCleanCommandKeepsTheRequestedNumberOfBackups(): void {
		$this->givenStoredBackups('default-2024-03-11-10-00-00.sql.gz', 'default-2024-03-12-10-00-00.sql.gz', 'default-2024-03-13-10-00-00.sql.gz');

		$tester = $this->runCommand('migration-backup:clean', ['--keep' => '1', '--force' => true]);

		$tester->assertCommandIsSuccessful();
		$this->assertSame(['default-2024-03-13-10-00-00.sql.gz'], array_map('basename', $this->storedBackups()));
	}

	public function testCleanCommandDryRunRemovesNothing(): void {
		$this->givenStoredBackups('default-2024-03-11-10-00-00.sql.gz', 'default-2024-03-12-10-00-00.sql.gz');

		$tester = $this->runCommand('migration-backup:clean', ['--keep' => '1', '--dry-run' => true]);

		$tester->assertCommandIsSuccessful();
		$this->assertCount(2, $this->storedBackups());
		$this->assertStringContainsString('default-2024-03-11-10-00-00.sql.gz', $tester->getDisplay());
	}

	public function testCleanCommandRefusesToRunUnattendedWithoutForce(): void {
		$this->givenStoredBackups('default-2024-03-11-10-00-00.sql.gz', 'default-2024-03-12-10-00-00.sql.gz');

		$tester = $this->runCommand('migration-backup:clean', ['--keep' => '1']);

		$this->assertSame(Command::FAILURE, $tester->getStatusCode());
		$this->assertCount(2, $this->storedBackups());
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

	/**
	 * @param list<string> $answers
	 */
	private function runInteractively(string $command, array $answers): CommandTester {
		$application = new Application(self::$kernel);
		$application->setAutoExit(false);

		$tester = new CommandTester($application->find($command));
		$tester->setInputs($answers);
		$tester->execute([], ['interactive' => true]);

		return $tester;
	}

	private function givenStoredBackups(string ...$filenames): void {
		$this->fs->mkdir($this->backupPath);
		foreach ($filenames as $filename) {
			file_put_contents($this->backupPath . '/' . $filename, 'dump');
		}
	}

	/**
	 * @return list<string>
	 */
	private function storedBackups(): array {
		$files = glob($this->backupPath . '/*.sql*');

		return $files === false ? [] : array_values($files);
	}
}
