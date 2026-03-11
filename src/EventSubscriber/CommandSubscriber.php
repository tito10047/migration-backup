<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 4. 8. 2022
 * Time: 9:56
 */

namespace Tito10047\MigrationBackup\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tito10047\MigrationBackup\BackupManager;

class CommandSubscriber implements EventSubscriberInterface {

	public function __construct(
		private readonly BackupManager $backupManager,
		private readonly array         $databases,
	) {}

	public static function getSubscribedEvents(): array {
		return [
			ConsoleEvents::COMMAND => "onCommandStart",
		];
	}

	public function onCommandStart(ConsoleCommandEvent $event): void {
		$command = $event->getCommand();
		if ($command === null) {
			return;
		}
		if ($command->getName() != 'doctrine:migrations:migrate') {
			return;
		}

		// add --backup option to command definition
		if (!$command->getDefinition()->hasOption('backup')) {
			$command->addOption('backup', 'b', InputOption::VALUE_OPTIONAL, 'Backup database before migration', false);
		}

		$input = $event->getInput();
		assert($input instanceof Input);
		if (!$input->hasParameterOption('--backup')) {
			return;
		}

		$io = $event->getOutput();
		foreach ($this->databases as $database) {
			$path = $this->backupManager->backup($database);
			$io->writeln('Backup of database ' . $database . ' created in ' . $path);
		}
	}
}
