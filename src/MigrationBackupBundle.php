<?php

namespace Tito10047\MigrationBackup;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Tito10047\MigrationBackup\BackupManager;
use Tito10047\MigrationBackup\Driver\MysqlBackupDriver;
use Tito10047\MigrationBackup\Driver\SqliteBackupDriver;
use Tito10047\MigrationBackup\EventSubscriber\CommandSubscriber;
use Tito10047\MigrationBackup\Registry\BackupDriverRegistry;
use Tito10047\MigrationBackup\Registry\BackupDriverRegistryInterface;
use Tito10047\MigrationBackup\Resolver\ConnectionResolver;
use Tito10047\MigrationBackup\Resolver\ConnectionResolverInterface;
use Tito10047\MigrationBackup\Storage\LocalStorageProvider;
use Tito10047\MigrationBackup\Storage\StorageProviderInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class MigrationBackupBundle extends AbstractBundle {
	public function configure(DefinitionConfigurator $definition): void {
		$definition->import('../config/definition.php');
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
		$services = $container->services();

		$services->set(Filesystem::class);

		$services->set(ConnectionResolverInterface::class, ConnectionResolver::class)
			->args([service("doctrine")]);

		$services->set(BackupDriverRegistryInterface::class, BackupDriverRegistry::class)
			->args([tagged_iterator("migration_backup.driver")]);

		$services->set(StorageProviderInterface::class, LocalStorageProvider::class)
			->args([
				service(Filesystem::class),
				$config["backup_path"],
			]);

		$services->set(BackupManager::class)
			->args([
				service(ConnectionResolverInterface::class),
				service(BackupDriverRegistryInterface::class),
				service(StorageProviderInterface::class),
				service("event_dispatcher"),
				service(Filesystem::class),
				$config["backup_path"],
			]);

		$services->set(MysqlBackupDriver::class)
			->tag("migration_backup.driver")
			->args([service(Filesystem::class)]);

		$services->set(SqliteBackupDriver::class)
			->tag("migration_backup.driver")
			->args([service(Filesystem::class)]);

		$services->set(CommandSubscriber::class)
			->tag("kernel.event_subscriber")
			->args([
				service(BackupManager::class),
				$config["database"],
			]);
	}

	public function getAlias(): string {
		return "migration_backup";
	}


}