# TYPO3 Deployment

The TYPO3 documentation describes the deployment and initialization process of a TYPO3 CMS application.

> The deployment package is inspired by [deployer-extended-typo3](https://github.com/sourcebroker/deployer-extended-typo3).

* [Installation](#installation)
* [Tasks](#tasks)
  + [Clearing the cache](#clearing-the-cache)
  + [Warming the cache](#warming-the-cache)
  + [Clearing and Warming the cache](#clearing-and-warming-the-cache)
  + [Updating the database](#updating-the-database)
  + [Additional Setup](#additional-setup)


The deployment workflow uses the deployer package [deployer-extended](https://github.com/sourcebroker/deployer-extended) as basis.

## Installation

The default configuration for TYPO3 applications is located at [set.php](../deployer/typo3/config/set.php) and can be overwritten optionally.

If you want to use the TYPO3 deployment functionalities add the belonging autoloader to your project `deploy.php`:

```php
require_once(__DIR__ . '/vendor/move-elevator/deployer-tools/deployer/typo3/autoload.php');
```

See an example configuration here: [typo3 example](../deployer/typo3/example/).

## Tasks

The following tasks are TYPO3 specific and extend the default deployer deployment:

### Default deployment

The default deployment task for TYPO3 applications can be found here  [deploy.php](../deployer/typo3/task/deploy.php)

### Clearing the cache

```bash
dep deploy:cache:clear
```

### Warming the cache

```bash
dep deploy:cache:warmup
```

> [!NOTE]
> This is just a simple curl request for the public URL configuration of the deployer host.

```bash
dep cache:warmup
```

> [!NOTE]
> This is using the [EXT:typo3-warming](https://github.com/eliashaeussler/typo3-warming) extension to warm up the cache.

### Clearing and warming the cache
Clearing and warming the cache in one combined task

```bash
dep deploy:cache:clear_and_warmup
```

### Updating the database

```bash
dep deploy:database:update
```

### Backup database

By default a database dump is created on deployments to prod environment. The default configuration file used by the task is:

```bash
'.deployment/db-sync-tool/backup-prod.yaml'
```
This can be overwritten by setting 'sync_database_backup_config'. For example:
```bash
set('sync_database_backup_config', __DIR__ . '/.deployment/db-sync-tool/some-configation.json');
```
To disable the database backup you can disable the task like this:
```bash
task('database:backup')->disable();
```

### Additional Setup

This command runs following TYPO3 CMS specific commands from the typo3_console:
- typo3cms install:fixfolderstructure
- typo3cms install:extensionsetupifpossible

```bash
dep deploy:additional_setup
```
