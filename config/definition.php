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
		->arrayNode("database")->defaultValue(["default"])->scalarPrototype()->end()->end()
		->end()
		->end();
};
