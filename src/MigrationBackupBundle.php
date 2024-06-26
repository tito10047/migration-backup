<?php

namespace Tito10047\MigrationBackup;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Tito10047\MigrationBackup\EventSubscriber\CommandSubscriber;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html
 */
class MigrationBackupBundle extends AbstractBundle {
	public function configure(DefinitionConfigurator $definition): void {
		$definition->import('../config/definition.php');
	}

	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void {
		$container
			->services()
			->set(CommandSubscriber::class)
			->tag('kernel.event_subscriber')
			->args([
				"\$fs"         => service(Filesystem::class),
				"\$registry"   => service("doctrine"),
				"\$backupPath" => $config["backup_path"],
				"\$databases"  => $config["database"],
			]);
	}

	public function getAlias(): string {
		return "migration_backup";
	}


}