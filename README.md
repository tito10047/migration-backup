# 🛡️ Migration Backup Bundle
[![Build Status](https://github.com/tito10047/migration-backup/actions/workflows/tests.yml/badge.svg)](https://github.com/tito10047/migration-backup/actions)
[![Latest Stable Version](https://img.shields.io/packagist/v/tito10047/migration-backup.svg)](https://packagist.org/packages/tito10047/migration-backup)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892bf.svg)](https://php.net)
[![Symfony Version](https://img.shields.io/badge/Symfony-%3E%3D%206.4-black?logo=symfony)](https://symfony.com/)
[![Coverage Status](https://coveralls.io/repos/github/tito10047/migration-backup/badge.svg)](https://coveralls.io/github/tito10047/migration-backup)

### Did you run a migration and it "crashed" in the middle? Welcome to hell. 🔥

You know the drill: you run `doctrine:migrations:migrate`, the third command out of ten fails, and you're left with a broken database. Revert doesn't work because half the changes were applied and half weren't. You don't know exactly what was executed and what wasn't. Manual repair is a nightmare, and if you had settings or data in the database that can't just be "re-run" via fixtures... well, good luck.

**Migration Backup Bundle is your rescue parachute.** It automatically backs up your database right before the first migration starts. If anything goes wrong, you have a clean restore point to return to immediately.

## ✨ Features

- 🚀 **Automatic backup** before running migrations.
- ⏪ **Restore**: Load any backup back into the database with a single command.
- 📋 **Listing**: See what you can restore, with date, size and compression format.
- 🗜️ **Compression support**: Multi-format support (Gzip, Bzip2, Zstandard, Zip, LZ4) to reduce backup size.
- 🧹 **Cleanup**: Automatic after every backup, or on demand (with a dry run).
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

    # Clients used for restoring
    mysql_binary: 'mysql'          # For MySQL
    psql_binary: 'psql'            # For PostgreSQL
```

## 🚀 Usage

The bundle does not activate itself automatically during every migration (to avoid slowing you down during development). To create a backup, just add the `--backup` flag (or the shortcut `-b`) to the command:

> **Note:** This bundle is primarily intended for the **development environment**, but there is nothing stopping you from using it in **production** as well.

```bash
php bin/console doctrine:migrations:migrate --backup
```

The console output will inform you of the success:
`Backup of database default created in /your/project/var/backups/default-2024-03-11-15-55-01.sql.gz`

## 🧰 Commands

The bundle ships four commands, so the parachute is not only packed but can also be opened.

### Back up right now

```bash
php bin/console migration-backup:backup             # all configured connections
php bin/console migration-backup:backup default     # just this one
```

### See what you can restore

```bash
php bin/console migration-backup:list
php bin/console migration-backup:list default
```

```
 ------------ --------------------- -------- ------------- --------------------------------------
  Connection   Created               Size     Compression   File
 ------------ --------------------- -------- ------------- --------------------------------------
  default      2024-03-13 09:12:44   1.4 MiB  gzip          default-2024-03-13-09-12-44.sql.gz
  default      2024-03-11 15:55:01   1.4 MiB  gzip          default-2024-03-11-15-55-01.sql.gz
 ------------ --------------------- -------- ------------- --------------------------------------
```

Backups are ordered by the timestamp in their file name, so copying the files around
(and changing their modification time) does not change which one is the newest.

### 🔥 Restore

```bash
# pick a backup from a list and confirm
php bin/console migration-backup:restore

# the newest backup of the default connection, no questions asked
php bin/console migration-backup:restore --latest --force

# a specific file
php bin/console migration-backup:restore --file=default-2024-03-11-15-55-01.sql.gz

# a backup of another connection, loaded into the default one
php bin/console migration-backup:restore legacy --latest --target=default
```

- **The stored backup file is never modified** — it is copied to a temporary working
  copy, decompressed there, and the working copy is removed afterwards.
- The compression format is taken from the file name, not from your configuration:
  a `.sql.gz` backup restores fine even after you switch to `zstd`.
- **Restoring overwrites the current content of the database.** Without `--force`
  the command asks for confirmation; in a non-interactive shell (CI, cron) it
  refuses to run unless `--force` is given.

### 🧹 Clean up old backups

```bash
php bin/console migration-backup:clean --dry-run    # show what would be removed
php bin/console migration-backup:clean --keep=3 --force
```

Without `--keep` the command uses `keep_last_n_backups` from the configuration.

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

To make backups in your format restorable as well, also implement
`Tito10047\MigrationBackup\Compressor\DecompressorInterface` and tag the service with
`migration_backup.compressor`. Compressors written before restoring existed keep working
for backups; restoring a file in their format fails with a clear message instead.

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

| Database | Backup needs | Restore needs |
| --- | --- | --- |
| **MySQL** | `mysqldump` | `mysql` |
| **PostgreSQL** | `pg_dump` | `psql` |
| **SQLite** | file access (copies the `.db` file) | file access (copies it back) |

### Custom Storage

`Tito10047\MigrationBackup\Storage\StorageProviderInterface` is enough for creating
backups. Listing, cleaning and restoring need a storage that can also be browsed, so
if you replace the storage provider service (its id is the interface name), implement
`Tito10047\MigrationBackup\Storage\ListableStorageProviderInterface` — it extends
`StorageProviderInterface` and adds `list()`, `get()`, `fetch()` and `remove()`.

## 🪝 Events for Developers

The bundle triggers the following events that you can listen to:
- `Tito10047\MigrationBackup\Event\BackupStartedEvent`
- `Tito10047\MigrationBackup\Event\BackupFinishedEvent`
- `Tito10047\MigrationBackup\Event\BackupFailedEvent`
- `Tito10047\MigrationBackup\Event\RestoreStartedEvent`
- `Tito10047\MigrationBackup\Event\RestoreFinishedEvent`
- `Tito10047\MigrationBackup\Event\RestoreFailedEvent`

---
Developed for a peaceful sleep with every deploy. 😊
