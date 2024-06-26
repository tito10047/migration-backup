# Doctrine Backup Bundle

This bundle provides a simple way to backup your database schema and data before running migrations.

## Usage

```bash
php bin/console doctrine:migrations:migrate --backup
```

## Configuration

```yaml
migration_backup:
    backup_path: '%kernel.project_dir%/var/migration_backup'
    database:
        - default
```

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Applications that use Symfony Flex

Open a command console, enter your project directory and execute:

```console
$ composer require tito10047/migration-backup
```

### Applications that don't use Symfony Flex

#### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require tito10047/migration-backup
```

#### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Tito10047\MigrationBackup\MigrationBackupBundle::class => ['all' => true],
];
```
