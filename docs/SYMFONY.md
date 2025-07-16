# Symfony Deployment

The symfony documentation describes the deployment and initialization process of a symfony application.

> The deployment package is inspired by [deployer-extended-symfony5](https://github.com/sourcebroker/deployer-extended-symfony5).

* [Installation](#installation)
* [Tasks](#tasks)
  + [Default deployment](#default-deployment)
  + [Clearing the cache](#clearing-the-cache)
  + [Warming the cache](#warming-the-cache)
  + [Updating the database](#updating-the-database)
  + [Installing assets](#installing-assets)
  + [Adjust the writable permissions](#adjust-the-writable-permissions)
* [Notes](#notes)


The deployment workflow uses the deployer package [deployer-extended](https://github.com/sourcebroker/deployer-extended) as basis.

## Installation

The default configuration for symfony applications is located at [set.php](../deployer/symfony/config/set.php) and can be overwritten optionally.

If you want to use the symfony deployment functionalities add the belonging autoloader to your project `deploy.php`:

```php
require_once(__DIR__ . '/vendor/move-elevator/deployer-tools/deployer/symfony/autoload.php');
```

See an example configuration here: [symfony example](../deployer/symfony/example/).

## Tasks

The following tasks are symfony specific and extend the default deployer deployment:

### Default deployment

The default deployment task for symfony applications can be found here  [deploy.php](../deployer/symfony/task/deploy.php)

```bash
$ dep deploy [host]
```

### Clearing the cache

The symfony cache can be cleared with the following task:

```bash
$ dep deploy:cache:clear
```

### Warming the cache

The symfony cache can be warmed with the following task:

```bash
dep deploy:cache:warmup
```

### Updating the database

The symfony database can be updated with the following task:

```bash
$ dep deploy:database:update
```

> Notice: The updating method can be configured with the `deploy_database_update_method` parameter (`migrations_migrate`|`schema_update`).

### Installing assets

The symfony assets can be installed with the following task:

```bash
$ dep deploy:assets:install
```

### Installing ckeditor

The ckeditor assets can be installed with the following task:

```bash
$ dep deploy:ckeditor:install
```

### Adjust the writable permissions

The permissions for the symfony application can be adjusted with the following task:

```bash
$ dep deploy:writable:chmod
```

## Notes

All deployment tasks can be executed within the ci pipeline or locally. It is recommended to deploy an application via the continuous workflow, the local deployment is just for testing purpose.
