<?php

namespace Tito10047\MigrationBackup\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Tito10047\MigrationBackup\BackupManager;

#[AsCommand(
	name: 'migration-backup:backup',
	description: 'Backs up the configured databases right now',
)]
class BackupCommand extends Command {
	/**
	 * @param list<string> $databases
	 */
	public function __construct(
		private readonly BackupManager $backupManager,
		private readonly array         $databases,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->addArgument('connections', InputArgument::IS_ARRAY, 'Connections to back up, all configured ones by default')
			->setHelp(<<<'HELP'
				The <info>%command.name%</info> command backs up the databases listed under
				<comment>migration_backup.database</comment>, without running any migration:

				  <info>php %command.full_name%</info>
				  <info>php %command.full_name% default legacy</info>
				HELP);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		/** @var list<string> $connections */
		$connections = $input->getArgument('connections');
		if ($connections === []) {
			$connections = $this->databases;
		}

		$failed = false;
		foreach ($connections as $connectionName) {
			try {
				$path = $this->backupManager->backup($connectionName);
				$io->success(sprintf('Backup of database %s created in %s', $connectionName, $path));
			} catch (Throwable $e) {
				$failed = true;
				$io->error(sprintf('Backup of database %s failed: %s', $connectionName, $e->getMessage()));
			}
		}

		return $failed ? Command::FAILURE : Command::SUCCESS;
	}
}
