# Requirements check

The requirements check validates that target servers meet the necessary prerequisites for a TYPO3 or Symfony deployment. This is particularly useful when working with external hosters where servers are managed by third-party administrators.

## General

All checks run remotely via SSH on the deployment target. The results are displayed in a summary table with status indicators (OK, FAIL, WARN, SKIP).

The default settings can be found within the [set.php](../deployer/requirements/config/set.php) file.

```bash
$ dep requirements:check [host]
```

## Checks

### Locales

Verifies that required system locales are available (default: `de_DE.utf8`, `en_US.utf8`).

### System packages

Checks for required CLI tools: rsync, curl, graphicsmagick, ghostscript, git, gzip, mariadb-client, unzip, patch, exiftool, composer.

### PHP extensions

Verifies that required PHP extensions are loaded. The list adapts automatically based on the configured `app_type` (typo3/symfony).

### PHP settings

Checks PHP CLI configuration values against expected minimums:

| Setting | Expected |
|---------|----------|
| max_execution_time | >= 240 |
| memory_limit | >= 512M |
| max_input_vars | >= 1500 |
| date.timezone | Europe/Berlin |
| post_max_size | >= 31M |
| upload_max_filesize | >= 30M |
| opcache.memory_consumption | >= 256 |

### Database client

Checks for the availability of the `mariadb` or `mysql` client and validates the version against client-specific minimums (MariaDB: >= 10.2.7, MySQL: >= 8.0.0).

### User and permissions

Validates that the SSH user belongs to the expected web server group (default: `www-data`) and that the deploy path has the correct owner, group, and permissions (default: `2770`).

### Environment file

Checks that the `.env` file exists in the shared directory and that all required environment variables are present. The required variables adapt automatically based on `app_type`.

## Configuration

All settings use the `requirements_` prefix and can be overridden in the consuming project:

```php
// Disable specific checks
set('requirements_check_database_enabled', false);

// Override PHP extensions list
set('requirements_php_extensions', ['curl', 'gd', 'mbstring', 'xml', 'intl']);

// Override PHP settings thresholds
set('requirements_php_settings', [
    'memory_limit' => '1G',
    'max_execution_time' => '300',
]);

// Override required system packages
set('requirements_packages', [
    'rsync' => 'rsync',
    'git' => 'git',
    'composer' => 'composer',
]);

// Override locales
set('requirements_locales', ['de_DE.utf8', 'en_US.utf8', 'fr_FR.utf8']);

// Override user group
set('requirements_user_group', 'apache');

// Override required env variables
set('requirements_env_vars', ['DATABASE_URL', 'APP_SECRET']);
```

## Extending with custom checks

Custom checks can be added via Deployer's `before()` hook:

```php
task('requirements:check:my_vhost', function () {
    addRequirementRow('Apache VHost', \Deployer\REQUIREMENT_OK, 'Configured');
})->hidden();

before('requirements:check:summary', 'requirements:check:my_vhost');
```
