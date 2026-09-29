<?php

namespace Tito10047\MigrationBackup\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tito10047\MigrationBackup\Dto\BackupFile;
use Tito10047\MigrationBackup\RestoreManager;

#[AsCommand(
	name: 'migration-backup:list',
	description: 'Lists the stored backups, newest first',
)]
class ListBackupsCommand extends Command {
	public function __construct(
		private readonly RestoreManager $restoreManager
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->addArgument('connection', InputArgument::OPTIONAL, 'Show only backups of this connection')
			->setHelp(<<<'HELP'
				The <info>%command.name%</info> command shows what can be restored:

				  <info>php %command.full_name%</info>
				  <info>php %command.full_name% default</info>
				HELP);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$io = new SymfonyStyle($input, $output);

		/** @var string|null $connectionName */
		$connectionName = $input->getArgument('connection');

		$files = $this->restoreManager->list($connectionName);

		if ($files === []) {
			$io->warning($connectionName === null
				? 'No backup has been created yet.'
				: sprintf('No backup of the connection "%s" has been created yet.', $connectionName));

			return Command::SUCCESS;
		}

		$io->table(
			['Connection', 'Created', 'Size', 'Compression', 'File'],
			array_map(static fn (BackupFile $file): array => [
				$file->connectionName,
				$file->createdAt->format('Y-m-d H:i:s'),
				Helper::formatMemory($file->size),
				$file->isCompressed() ? ltrim($file->compressionExtension, '.') : '-',
				$file->filename,
			], $files)
		);

		return Command::SUCCESS;
	}
}
