<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $ormConfig = [
        'auto_generate_proxy_classes' => true,
        'enable_lazy_ghost_objects' => true,
        'report_fields_where_declared' => true,
        'validate_xml_mapping' => true,
        'naming_strategy' => 'doctrine.orm.naming_strategy.underscore_number_aware',
        'auto_mapping' => true,
        'mappings' => [
            'App' => [
                'type' => 'attribute',
                'is_bundle' => false,
                'dir' => '%kernel.project_dir%/tests/App/Entity',
                'prefix' => 'Tito10047\MigrationBackup\Tests\App\Entity',
                'alias' => 'App',
            ],
        ],
        'controller_resolver' => [
            'auto_mapping' => false,
        ],
    ];

    if (PHP_VERSION_ID >= 80400) {
        $ormConfig['enable_native_lazy_objects'] = true;
    }

    $containerConfigurator->extension('doctrine', [
        'dbal' => [
            'url' => '%env(resolve:DATABASE_URL)%',
            'profiling_collect_backtrace' => '%kernel.debug%',
            'use_savepoints' => true,
        ],
        'orm' => $ormConfig,
    ]);

    if ($containerConfigurator->env() === 'test') {
        $containerConfigurator->extension('doctrine', [
            'dbal' => [
                'dbname_suffix' => '_test%env(default::TEST_TOKEN)%',
            ],
        ]);
    }
};
