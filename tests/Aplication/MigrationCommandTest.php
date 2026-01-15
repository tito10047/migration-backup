<?php

namespace Tito10047\MigrationBackup\Tests\Aplication;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Console\Tester\CommandTester;
use Tito10047\MigrationBackup\Tests\App\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class MigrationCommandTest extends KernelTestCase
{
    private string $backupDir;
    private Filesystem $fs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupDir = __DIR__ . '/../App/migration_backup';
        $this->fs = new Filesystem();
        
        // Vyčistíme priečinok pred testom (okrem .keep)
        if ($this->fs->exists($this->backupDir)) {
            $finder = new Finder();
            $finder->files()->in($this->backupDir)->notName('.keep');
            foreach ($finder as $file) {
                $this->fs->remove($file->getRealPath());
            }
        }
    }

    public function testBackupCreated()
    {

        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);


        $tester = new ApplicationTester($application);
        $tester->run(array(
            'command' => 'doctrine:migrations:migrate',
            '--no-interaction' => true,
            '--all-or-nothing' => 0,
            '--backup' => true,
        ));

        $tester->assertCommandIsSuccessful();

        $finder = new Finder();
        $finder->files()->in($this->backupDir)->name('default-*.sql');
        
        $this->assertCount(1, $finder, "Záloha databázy by mala existovať na disku.");

    }

    public function testBackupNotCreated()
    {

        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);


        $tester = new ApplicationTester($application);
        $tester->run(array(
            'command' => 'doctrine:migrations:migrate',
            '--all-or-nothing' => 0,
            '--no-interaction' => true,
        ));

        $tester->assertCommandIsSuccessful();

        $finder = new Finder();
        if ($this->fs->exists($this->backupDir)) {
            $finder->files()->in($this->backupDir)->name('default-*.sql');
            $this->assertCount(0, $finder, "Záloha databázy by nemala existovať.");
        } else {
            $this->assertTrue(true);
        }

    }
}