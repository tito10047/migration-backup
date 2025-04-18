<?php

namespace Tito10047\MigrationBackup\Tests\Aplication;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;
use Tito10047\MigrationBackup\Tests\App\KernelTestCase;

class MigrationCommandTest extends KernelTestCase
{
    public function testBackupCreated()
    {

        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);


        $tester = new ApplicationTester($application);
        $tester->run(array(
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
            '--backup' => true,
        ));

        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringContainsString('Backup of database default created', $output);

    }

    public function testBackupNotCreated()
    {

        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);


        $tester = new ApplicationTester($application);
        $tester->run(array(
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
        ));

        $tester->assertCommandIsSuccessful();

        $output = $tester->getDisplay();
        $this->assertStringNotContainsString('Backup of database default created', $output);

    }
}