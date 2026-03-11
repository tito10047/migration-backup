<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

/**
 * @link https://symfony.com/doc/current/bundles/best_practices.html#configuration
 */
return static function (DefinitionConfigurator $definition): void {
	$definition
		->rootNode()
		->children()
		->scalarNode('backup_path')->defaultValue('%kernel.project_dir%/backup')->end()
		->scalarNode('backup_binary')->defaultValue('mysqldump')->end()
		->scalarNode('pg_dump_binary')->defaultValue('pg_dump')->end()
		->arrayNode("database")->defaultValue(["default"])->scalarPrototype()->end()->end()
		->integerNode('keep_last_n_backups')->defaultValue(0)->info('Number of backups to keep. 0 means keep all.')->end()
		->booleanNode('compress')->defaultFalse()->info('Compress backup with gzip.')->end()
		->end()
		->end();
};
