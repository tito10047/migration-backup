<?php
/*
 * This file is part of the Progressive Image Bundle.
 *
 * (c) Jozef Môstka <https://github.com/tito10047/progressive-image-bundle>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tito10047\MigrationBackup\Tests\Fixture;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
class TestKernel extends Kernel
{
	use MicroKernelTrait;

	public function registerBundles(): iterable {
		yield new \Symfony\Bundle\FrameworkBundle\FrameworkBundle();
		yield new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle();
		yield new \Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle();
		yield new \Tito10047\MigrationBackup\MigrationBackupBundle();
	}


	protected function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void {
		$builder->loadFromExtension('framework', [
			'test' => true,
		]);
		$container->extension('migration_backup', []);
		$container->extension('doctrine', [
			'dbal' => [
				'url' => 'sqlite:///%kernel.project_dir%/var/data.db',
			]
		]);
		$container->extension('doctrine_migrations', [
			"enable_profiler" => false,
			"organize_migrations" => 'BY_YEAR_AND_MONTH'
		]);
	}

	public function boot(): void {
		parent::boot();
		$dbPath = $this->getProjectDir() . '/var/data.db';
		if (!file_exists(dirname($dbPath))) {
			mkdir(dirname($dbPath), 0777, true);
		}
		if (!file_exists($dbPath)) {
			touch($dbPath);
		}
	}
}

