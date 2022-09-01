# Backup database bundle

A Symfony bundle that provides a command to easily backup databases.

**Note**: For now only MySQL is supported.

## Requirements

- See `composer.json` for most dependencies.
- A Unix-like OS.
- The following commands:
  - `mkfifo`
  - `mysqldump`
  - `bzip2`

## Setup

- Add `AndreasA\BackupDatabaseBundle\BackupDatabaseBundle::class => ['all' => true],` to `config/bundles.php`, if it has not been added by `symfony/flex` already.
- Add the file `config/packages/andreasa_backup_database.yaml` with a content like (example configuration):
  ```yaml
  andreasa_backup_database:
      database_url: '%env(resolve:DATABASE_URL)%'
      target_directory: '%kernel.project_dir%/var/backup'
      mysql:
          ignored_tables:
              - 'cache_items'
          options:
              - 'default-character-set=utf8mb4'
              - 'hex-blob'
              - 'no-tablespaces'
              - 'opt'
              - 'routines'
              - 'triggers'
          platform_specific_options:
              - 'column-statistics=0'
              - 'skip-column-statistics'
  ```

## Configuration

- `database_url`: The URL to the database.
- `target_directory`: The directory where the backups are placed. Those files include the current date and time in their filenames.
- `mysql`: Specific options for MySQL backups.
  - `ignored_tables`: Tables to be ignored during backup. Can also be set to `[]`, if no tables should be ignored.
  - `options`: These options are used during backup and provided using the MySQL configuration file syntax. The `mysqldump` command has to support these options. Can also be set to `[]`, if no additional options are necessary.
  - `platform_specific_options`: Basically the same as `options` but here the bundle first checks if the `mysqldump` command supports these options. This is relevant, if you do not know the used `mysqldump` version. Can also be set to `[]`, if no platform specific options are necessary.
