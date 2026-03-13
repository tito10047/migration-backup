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

	private array $dbConfig;

	public function __construct(string $environment, bool $debug, array $dbConfig = []) {
		parent::__construct($environment, $debug);
		$this->dbConfig = $dbConfig ?: [
			'url' => 'sqlite:///%kernel.project_dir%/var/data.db',
		];
	}

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
		$container->extension('migration_backup', [
			'compress' => true,
			'compression_format' => 'gzip'
		]);
		$container->extension('doctrine', [
			'dbal' => $this->dbConfig
		]);
		$container->extension('doctrine_migrations', [
			"enable_profiler" => false,
			"organize_migrations" => 'BY_YEAR_AND_MONTH'
		]);
	}

	public function boot(): void {
		parent::boot();
		if (isset($this->dbConfig['url']) && str_starts_with($this->dbConfig['url'], 'sqlite:')) {
			$dbPath = str_replace('sqlite:///%kernel.project_dir%', $this->getProjectDir(), $this->dbConfig['url']);
			if (!file_exists(dirname($dbPath))) {
				mkdir(dirname($dbPath), 0777, true);
			}
			if (!file_exists($dbPath)) {
				touch($dbPath);
			}
		}
	}
}

