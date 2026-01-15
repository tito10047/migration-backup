<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $ormConfig = [
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

    if (class_exists(\Composer\InstalledVersions::class)
        && \Composer\InstalledVersions::isInstalled('doctrine/orm')
        && version_compare(\Composer\InstalledVersions::getVersion('doctrine/orm'), '3.0.0', '<')
    ) {
        $ormConfig['auto_generate_proxy_classes'] = true;
        $ormConfig['enable_lazy_ghost_objects'] = true;
        $ormConfig['report_fields_where_declared'] = true;
        $ormConfig['validate_xml_mapping'] = true;
    }

    if (PHP_VERSION_ID >= 80400) {
        $ormConfig['enable_native_lazy_objects'] = true;
    }

    $dbalConfig = [
        'url' => '%env(resolve:DATABASE_URL)%',
        'profiling_collect_backtrace' => '%kernel.debug%',
    ];

    if (class_exists(\Composer\InstalledVersions::class)
        && \Composer\InstalledVersions::isInstalled('doctrine/persistence')
        && version_compare(\Composer\InstalledVersions::getVersion('doctrine/persistence'), '4.0.0', '<')
    ) {
        $dbalConfig['use_savepoints'] = true;
    }

    $containerConfigurator->extension('doctrine', [
        'dbal' => $dbalConfig,
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
