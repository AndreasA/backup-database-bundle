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
