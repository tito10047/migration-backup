<?php

namespace Tito10047\MigrationBackup\Tests\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Tito10047\MigrationBackup\BackupManager;
use Tito10047\MigrationBackup\EventSubscriber\CommandSubscriber;

class CommandSubscriberTest extends TestCase {
	public function testOnCommandStartWithBackupOption(): void {
		$manager   = $this->createMock(BackupManager::class);
		$databases = ['default'];
		$subscriber = new CommandSubscriber($manager, $databases);

		$command = $this->createMock(Command::class);
		$command->method('getName')->willReturn('doctrine:migrations:migrate');
		$command->method('getDefinition')->willReturn(new InputDefinition());

		$input = $this->createMock(InputInterface::class);
		$input->method('hasParameterOption')->with('--backup')->willReturn(true);

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->once())
			->method('writeln')
			->with($this->stringContains('Backup of database default created in stored_path'));

		$event = new ConsoleCommandEvent($command, $input, $output);

		$manager->expects($this->once())
			->method('backup')
			->with('default')
			->willReturn('stored_path');

		$subscriber->onCommandStart($event);
	}

	public function testOnCommandStartWithoutBackupOption(): void {
		$manager   = $this->createMock(BackupManager::class);
		$databases = ['default'];
		$subscriber = new CommandSubscriber($manager, $databases);

		$command = $this->createMock(Command::class);
		$command->method('getName')->willReturn('doctrine:migrations:migrate');
		$command->method('getDefinition')->willReturn(new InputDefinition());

		$input = $this->createMock(InputInterface::class);
		$input->method('hasParameterOption')->with('--backup')->willReturn(false);

		$output = $this->createMock(OutputInterface::class);
		$output->expects($this->never())->method('writeln');

		$event = new ConsoleCommandEvent($command, $input, $output);

		$manager->expects($this->never())->method('backup');

		$subscriber->onCommandStart($event);
	}
}
