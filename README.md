# Doctrine Backup Bundle

This bundle provides a simple way to backup your database schema and data before running migrations.

## Usage

```bash
php bin/console doctrine:migrations:migrate --backup
```

## Configuration

```yaml
when@dev:
    migration_backup:
        backup_path: '%kernel.project_dir%/var/migration_backup'
        database:
            - default
```

## Installation

```console
$ composer require tito10047/migration-backup
```

```php
// config/bundles.php
return [
    // ...
    Tito10047\MigrationBackup\MigrationBackupBundle::class => ['dev' => true],
];
```
