<?php

namespace Tito10047\MigrationBackup\Tests\Fixture;

use LogicException;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * An output that looks like a console (so `SymfonyStyle` reaches for sections)
 * but cannot produce one.
 *
 * This is not an exotic case: Symfony 8.1's `TestOutput`, which `CommandTester`
 * uses, behaves exactly like this. A command that renders through a console
 * section is therefore untestable for everyone who uses it.
 */
class SectionlessConsoleOutput extends StreamOutput implements ConsoleOutputInterface {
	private OutputInterface $stderr;

	/**
	 * @param resource $stream
	 */
	public function __construct($stream) {
		parent::__construct($stream);

		$this->stderr = new StreamOutput(fopen('php://memory', 'w+'));
	}

	public function section(): ConsoleSectionOutput {
		throw new LogicException('ConsoleSectionOutput is not supported by ' . self::class . '.');
	}

	public function getErrorOutput(): OutputInterface {
		return $this->stderr;
	}

	public function setErrorOutput(OutputInterface $error): void {
		$this->stderr = $error;
	}

	public function fetch(): string {
		rewind($this->getStream());

		return (string)stream_get_contents($this->getStream());
	}
}
