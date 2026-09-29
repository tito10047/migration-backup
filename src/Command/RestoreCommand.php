<?php

namespace Tito10047\MigrationBackup\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Tito10047\MigrationBackup\Dto\BackupFile;
use Tito10047\MigrationBackup\Exception\BackupNotFoundException;
use Tito10047\MigrationBackup\RestoreManager;

#[AsCommand(
	name: 'migration-backup:restore',
	description: 'Restores a database from a backup',
)]
class RestoreCommand extends Command {
	/**
	 * @param list<string> $databases
	 */
	public function __construct(
		private readonly RestoreManager $restoreManager,
		private readonly array          $databases,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->addArgument('connection', InputArgument::OPTIONAL, 'Connection to restore, the first configured one by default')
			->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Name of the backup file to restore')
			->addOption('latest', 'l', InputOption::VALUE_NONE, 'Restore the newest backup without asking which one')
			->addOption('target', 't', InputOption::VALUE_REQUIRED, 'Restore into this connection instead of the one the backup came from')
			->addOption('force', null, InputOption::VALUE_NONE, 'Do not ask for confirmation')
			->setHelp(<<<'HELP'
				The <info>%command.name%</info> command loads a backup back into the database.
				The stored backup file is never modified, it is decompressed in a working copy.

				  <info>php %command.full_name%</info>                       pick a backup from a list
				  <info>php %command.full_name% --latest --force</info>      the newest backup of the default connection
				  <info>php %command.full_name% --file=default-2024-03-11-15-55-01.sql.gz</info>
				  <info>php %command.full_name% legacy --latest --target=default</info>

				<comment>This overwrites the current content of the database.</comment>
				HELP);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		/** @var string|null $connectionName */
		$connectionName = $input->getArgument('connection');

		try {
			$backup = $this->chooseBackup($input, $io, $connectionName);
		} catch (BackupNotFoundException $e) {
			$io->error($e->getMessage());

			return Command::FAILURE;
		}

		if ($backup === null) {
			$io->warning('Aborted, nothing was restored.');

			return Command::FAILURE;
		}

		/** @var string|null $target */
		$target = $input->getOption('target');
		$target ??= $connectionName ?? $backup->connectionName;

		if (!$input->getOption('force')) {
			$question = sprintf('This overwrites the database behind the connection "%s" with %s. Continue?', $target, $backup->filename);

			if (!$input->isInteractive()) {
				$io->error($question . ' Re-run the command with --force.');

				return Command::FAILURE;
			}

			if (!$io->confirm($question, false)) {
				$io->warning('Aborted, nothing was restored.');

				return Command::FAILURE;
			}
		}

		try {
			$this->restoreManager->restore($backup, $target);
		} catch (Throwable $e) {
			$io->error(sprintf('Restore of %s failed: %s', $backup->filename, $e->getMessage()));

			return Command::FAILURE;
		}

		$io->success(sprintf('Database %s restored from %s', $target, $backup->filename));

		return Command::SUCCESS;
	}

	/**
	 * @return BackupFile|null null when the user cancelled the selection
	 *
	 * @throws BackupNotFoundException
	 */
	private function chooseBackup(InputInterface $input, SymfonyStyle $io, ?string $connectionName): ?BackupFile {
		/** @var string|null $filename */
		$filename = $input->getOption('file');
		if ($filename !== null) {
			return $this->restoreManager->get($filename);
		}

		$connectionName ??= $this->databases[0] ?? 'default';

		if ($input->getOption('latest') || !$input->isInteractive()) {
			return $this->restoreManager->latest($connectionName);
		}

		$files = $this->restoreManager->list($connectionName);
		if ($files === []) {
			throw BackupNotFoundException::forConnection($connectionName);
		}

		$choices = [];
		foreach ($files as $file) {
			$choices[$file->filename] = sprintf('%s (%s)', $file->createdAt->format('Y-m-d H:i:s'), $file->filename);
		}

		$chosen = $io->choice('Which backup do you want to restore?', array_values($choices), array_values($choices)[0]);

		$filename = array_search($chosen, $choices, true);

		return $filename === false ? null : $this->restoreManager->get($filename);
	}
}
