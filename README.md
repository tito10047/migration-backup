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
- 🗜️ **Gzip compression** for drastic reduction in backup file size.
- 🧹 **Automatic Cleanup**: Keep e.g. only the last 10 backups and save space.
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
    
    # Should the backup be compressed using gzip?
    compress: true

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
