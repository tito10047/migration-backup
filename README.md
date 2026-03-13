# 🛡️ Migration Backup Bundle
[![Build Status](https://github.com/tito10047/migration-backup/actions/workflows/tests.yml/badge.svg)](https://github.com/tito10047/migration-backup/actions)
[![Latest Stable Version](https://img.shields.io/packagist/v/tito10047/migration-backup.svg)](https://packagist.org/packages/tito10047/migration-backup)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892bf.svg)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-%3E%3D%206.4-black?logo=symfony)](https://symfony.com/)
[![Coverage Status](https://coveralls.io/repos/github/tito10047/migration-backup/badge.svg?branch=v2)](https://coveralls.io/github/tito10047/migration-backup?branch=v2)

### Did you run a migration and it "crashed" in the middle? Welcome to hell. 🔥

You know the drill: you run `doctrine:migrations:migrate`, the third command out of ten fails, and you're left with a broken database. Revert doesn't work because half the changes were applied and half weren't. You don't know exactly what was executed and what wasn't. Manual repair is a nightmare, and if you had settings or data in the database that can't just be "re-run" via fixtures... well, good luck.

**Migration Backup Bundle is your rescue parachute.** It automatically backs up your database right before the first migration starts. If anything goes wrong, you have a clean restore point to return to immediately.

## ✨ Features

- 🚀 **Automatic backup** before running migrations.
- 🗜️ **Compression support**: Multi-format support (Gzip, Bzip2, Zstandard, Zip, LZ4) to reduce backup size.
- 🧹 **Automatic Cleanup**: Keep e.g. only the last 10 backups and save space.
- 🧩 **Extensible**: Easily add your own custom compressor.
- 🐘 **Multi-DB support**: Full support for **MySQL**, **PostgreSQL**, and **SQLite**.
- 🔔 **Events**: Ability to hook into your own logic (Slack notifications, logging, etc.).

## 📦 Installation

```bash
composer require tito10047/migration-backup
```

*(If you are not using Symfony Flex, don't forget to register the bundle in `config/bundles.php`)*

## ⚙️ Configuration

Create the file `config/packages/migration_backup.yaml`:

```yaml
migration_backup:
    # Directory for storing backups (default is %kernel.project_dir%/backup)
    backup_path: '%kernel.project_dir%/var/backups'
    
    # Which DB connections you want to back up (can be multiple)
    database: ['default']
    
    # How many last backups to keep (0 = all)
    keep_last_n_backups: 5
    
    # Should the backup be compressed?
    compress: true

    # Compression format to use (default: gzip)
    # Available options: gzip, bzip2, zstd, zip, lz4, none
    compression_format: 'gzip'

    # Paths to binaries (if not available globally in PATH)
    backup_binary: 'mysqldump'    # For MySQL
    pg_dump_binary: 'pg_dump'      # For PostgreSQL
```

## 🚀 Usage

The bundle does not activate itself automatically during every migration (to avoid slowing you down during development). To create a backup, just add the `--backup` flag (or the shortcut `-b`) to the command:

> **Note:** This bundle is primarily intended for the **development environment**, but there is nothing stopping you from using it in **production** as well.

```bash
php bin/console doctrine:migrations:migrate --backup
```

The console output will inform you of the success:
`Backup of database default created in /your/project/var/backups/default-2024-03-11-15-55-01.sql.gz`

## 🗜️ Compression

The bundle supports several compression formats. Each format requires its corresponding PHP extension to be installed:

| Format | Extension | File Extension | Recommendation |
| --- | --- | --- | --- |
| **Gzip** | `zlib` | `.gz` | Standard, well-balanced. |
| **Bzip2** | `bz2` | `.bz2` | Better compression ratio, slower. |
| **Zstandard** | `zstd` | `.zst` | Modern, fast with great compression. |
| **Zip** | `zip` | `.zip` | Highly compatible across OS. |
| **LZ4** | `lz4` | `.lz4` | Extremely fast compression. |
| **None** | - | - | No compression. |

If the required extension is missing, the bundle will throw a `RuntimeException` when attempting to use that format.

### Custom Compressor

You can implement your own compression logic by creating a class that implements `Tito10047\MigrationBackup\Compressor\CompressorInterface`.

Then, register your service and alias the `migration_backup.compressor` to it:

```yaml
# config/services.yaml
services:
    App\Backup\MyCustomCompressor:
        arguments: ['@symfony_filesystem_service']

    migration_backup.compressor:
        alias: App\Backup\MyCustomCompressor
```

Note: If you override the `migration_backup.compressor` service, the `compression_format` setting in `migration_backup.yaml` will be ignored. It's cleaner to set it to `none` to avoid confusion.

## 🛠️ Supported Databases

- **MySQL**: requires `mysqldump` to be installed.
- **PostgreSQL**: requires `pg_dump` to be installed.
- **SQLite**: standard file access is enough (automatically copies the `.db` file).

## 🪝 Events for Developers

The bundle triggers the following events that you can listen to:
- `Tito10047\MigrationBackup\Event\BackupStartedEvent`
- `Tito10047\MigrationBackup\Event\BackupFinishedEvent`
- `Tito10047\MigrationBackup\Event\BackupFailedEvent`

---
Developed for a peaceful sleep with every deploy. 😊
