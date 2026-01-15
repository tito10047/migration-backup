<?php
/**
 * Created by PhpStorm.
 * User: Jozef Môstka
 * Date: 4. 8. 2022
 * Time: 9:56
 */

namespace Tito10047\MigrationBackup\EventSubscriber;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Filesystem\Filesystem;

class CommandSubscriber implements EventSubscriberInterface {


	/**
	 * @var Connection
	 */
	private $conn;
	private $commands = [];

	public function __construct(
		private readonly Filesystem      $fs,
		private readonly ManagerRegistry $registry,
		private readonly string          $backupPath,
		private readonly array           $databases,
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

		$input          = $event->getInput();
		assert($input instanceof Input);
        if (!$input->hasParameterOption('--backup')) {
			return;
		}

        $io = $event->getOutput();
		foreach ($this->databases as $database) {
			$params   = $this->registry->getConnection($database)->getParams();
			$filename = $this->backupPath . '/' . $database . '-' . date('Y-m-d-H-i-s') . '.sql';
			$this->dumpDatabase(
				$params['host'],
				$params['port'],
				$params['dbname'],
				$params['user'],
				$params['password'],
				$filename
			);
            $io->writeln('Backup of database ' . $database . ' created in ' . $filename);

		}
	}


	private function dumpDatabase(string $host, string $port, string $database, string $username, string $password, string $path): void {

		$cmd = sprintf('mysqldump -h %s -P %s -B %s -u %s --password=%s --hex-blob', $host,$port,$database, $username, $password);

		[$output, $exit_status] = $this->runCommand($cmd);

		if ($exit_status > 0) {
			throw new Exception('Could not dump database: ' . var_export($output, true));
		}

		$this->fs->dumpFile($path, implode("\n", $output));
	}

	protected function runCommand($command): array {
		$command .= " >&1";
		exec($command, $output, $exit_status);

		return [
			$output, $exit_status,
		];
	}

}
