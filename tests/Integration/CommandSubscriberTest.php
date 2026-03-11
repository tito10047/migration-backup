<?php
/*
 * This file is part of the Progressive Image Bundle.
 *
 * (c) Jozef Môstka <https://github.com/tito10047/progressive-image-bundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tito10047\MigrationBackup\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CommandSubscriberTest extends KernelTestCase{

	public function testBackup():void {

		self::bootKernel();
		$application = new Application(self::$kernel);

		$commandName = 'doctrine:migrations:migrate';

		$foundCommand = $application->find($commandName);

		$tester = new CommandTester($foundCommand);

		$tester->execute([
			'command' => $commandName,
			'--backup' => true,
		]);

	}

}