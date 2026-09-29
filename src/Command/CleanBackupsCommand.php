<?php

namespace Tito10047\MigrationBackup\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tito10047\MigrationBackup\BackupCleaner;

#[AsCommand(
	name: 'migration-backup:clean',
	description: 'Removes old backups and keeps only the newest ones',
)]
class CleanBackupsCommand extends Command {
	/**
	 * @param list<string> $databases
	 */
	public function __construct(
		private readonly BackupCleaner $cleaner,
		private readonly array         $databases,
		private readonly int           $keepLastN,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->addArgument('connections', InputArgument::IS_ARRAY, 'Connections to clean, all configured ones by default')
			->addOption('keep', 'k', InputOption::VALUE_REQUIRED, 'How many of the newest backups to keep (0 keeps all)')
			->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show what would be removed')
			->addOption('force', null, InputOption::VALUE_NONE, 'Do not ask for confirmation')
			->setHelp(<<<'HELP'
				The <info>%command.name%</info> command applies the retention rule of
				<comment>migration_backup.keep_last_n_backups</comment> on demand:

				  <info>php %command.full_name% --dry-run</info>
				  <info>php %command.full_name% --keep=3 --force</info>
				HELP);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		/** @var list<string> $connections */
		$connections = $input->getArgument('connections');
		if ($connections === []) {
			$connections = $this->databases;
		}

		$keep = $input->getOption('keep') === null ? $this->keepLastN : (int)$input->getOption('keep');
		if ($keep <= 0) {
			$io->warning('Keeping all backups, there is nothing to clean. Set --keep or migration_backup.keep_last_n_backups.');

			return Command::SUCCESS;
		}

		$dryRun = (bool)$input->getOption('dry-run');

		foreach ($connections as $connectionName) {
			$obsolete = $this->cleaner->clean($connectionName, $keep, true);

			if ($obsolete === []) {
				$io->writeln(sprintf('<info>%s</info>: nothing to remove, %d backup(s) kept.', $connectionName, $keep));
				continue;
			}

			$io->section(sprintf('%s: %d backup(s) to remove', $connectionName, count($obsolete)));
			$io->listing(array_map(
				static fn ($file): string => sprintf('%s (%s)', $file->filename, Helper::formatMemory($file->size)),
				$obsolete
			));

			if ($dryRun) {
				continue;
			}

			if (!$input->getOption('force')) {
				if (!$input->isInteractive()) {
					$io->error('Removing backups needs a confirmation. Re-run the command with --force or --dry-run.');

					return Command::FAILURE;
				}

				if (!$io->confirm(sprintf('Remove these backups of "%s"?', $connectionName), false)) {
					$io->warning(sprintf('Skipped %s.', $connectionName));
					continue;
				}
			}

			$removed = $this->cleaner->clean($connectionName, $keep);
			$io->success(sprintf('Removed %d backup(s) of %s.', count($removed), $connectionName));
		}

		if ($dryRun) {
			$io->note('Dry run, nothing was removed.');
		}

		return Command::SUCCESS;
	}
}
