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

use Symfony\Component\Console\Tester\ApplicationTester;
use Tito10047\MigrationBackup\Tests\Fixture\TestKernel;

class CommandSubscriberTest extends KernelTestCase{

	protected static function createKernel(array $options = []): TestKernel {
		return new TestKernel(
			$options['environment'] ?? 'test',
			$options['debug'] ?? true,
			$options['db_config'] ?? []
		);
	}

	public function testBackupSqlite():void {
		self::bootKernel(['db_config' => ['url' => 'sqlite:///%kernel.project_dir%/var/data.db']]);
		$this->runBackupTest();
	}

	public function testBackupMysql(): void {
		$dbUrl = $_ENV['DATABASE_URL'] ?? null;
		if (!$dbUrl || !str_starts_with($dbUrl, 'mysql')) {
			$this->markTestSkipped('DATABASE_URL for mysql not found');
		}

		self::bootKernel(['db_config' => ['url' => $dbUrl]]);
		$this->runBackupTest();
	}

	public function testBackupPostgres(): void {
		$dbUrl = $_ENV['DATABASE_POSTGRES_URL'] ?? null;
		if (!$dbUrl || !str_starts_with($dbUrl, 'postgresql')) {
			$this->markTestSkipped('DATABASE_POSTGRES_URL for postgres not found');
		}

		self::bootKernel(['db_config' => ['url' => $dbUrl]]);
		$this->runBackupTest();
	}

	private function runBackupTest(): void {
		$application = new Application(self::$kernel);
		$application->setAutoExit(false);

		$commandName = 'doctrine:migrations:migrate';

		$tester = new ApplicationTester($application);

		$tester->run([
			'command' => $commandName,
			'--backup' => true,
			'--no-interaction' => true,
		]);
		$this->assertStringContainsString('Backup of database default created in', $tester->getDisplay());
	}

}