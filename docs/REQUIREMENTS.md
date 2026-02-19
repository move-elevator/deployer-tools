# Requirements check

The requirements check validates that target servers meet the necessary prerequisites for a TYPO3 or Symfony deployment. This is particularly useful when working with external hosters where servers are managed by third-party administrators.

## General

All checks run remotely via SSH on the deployment target. The results are displayed in a summary table with status indicators (OK, FAIL, WARN, SKIP).

The default settings can be found within the [set.php](../deployer/requirements/config/set.php) file.

```bash
$ dep requirements:check [host]
```

To display a human-readable list of all requirements (without running remote checks):

```bash
$ dep requirements:list [host]
```

## Checks

### Locales

Verifies that required system locales are available (default: `de_DE.utf8`, `en_US.utf8`).

### System packages

Checks for required CLI tools: rsync, curl, ghostscript, git, gzip, mariadb-client, unzip, patch, exiftool, composer (with version validation >= 2.1.0).

### Image processing

Checks for GraphicsMagick (>= 1.3, recommended) or ImageMagick (>= 6.0) with version validation. Either one is sufficient.

### PHP version

Validates the PHP version against the configured minimum (TYPO3: >= 8.2.0, default: >= 8.1.0).

### PHP extensions

Verifies that required PHP extensions are loaded. The list adapts automatically based on the configured `app_type` (typo3/symfony).

### PHP settings

Checks PHP CLI configuration values against expected minimums:

| Setting | Expected |
|---------|----------|
| max_execution_time | >= 240 |
| memory_limit | >= 512M |
| max_input_vars | >= 1500 |
| pcre.jit | >= 1 |
| date.timezone | Europe/Berlin |
| post_max_size | >= 31M |
| upload_max_filesize | >= 30M |
| opcache.memory_consumption | >= 256 |

### Database client

Checks for the availability of the `mariadb` or `mysql` client and validates the version against client-specific minimums (MariaDB: >= 10.4.3, MySQL: >= 8.0.17).

### Database grants

Validates database user permissions based on the configured `database_manager_type`. This check requires the feature deployment autoload to be loaded.

**Root mode** (`default` / `root`): Connects to the database server and verifies that all required grants are set on global level (`*.*`):

```sql
SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER,
CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE,
CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE
```

`ALL PRIVILEGES ON *.*` is also accepted.

**Simple mode** (`simple`): Validates pool configuration and tests connectivity to each pool database.

**Mittwald API** (`mittwald_api`): Skipped (database management is handled via API).

Credential resolution (no interactive prompt):

1. Deployer config (`database_user`, `database_password`)
2. Environment variable `DEPLOYER_CONFIG_DATABASE_PASSWORD`
3. Shared `.env` file (TYPO3: `TYPO3_CONF_VARS__DB__Connections__Default__*`, Symfony: `DATABASE_URL`)

If no credentials can be resolved, the check is skipped.

### User and permissions

Validates that the SSH user belongs to the expected web server group (default: `www-data`) and that the deploy path has the correct owner, group, and permissions (default: `2770`).

### Environment file

Checks that the `.env` file exists in the shared directory and that all required environment variables are present. The required variables adapt automatically based on `app_type`.

### End-of-life (EOL)

Checks installed PHP and database (MariaDB/MySQL) versions against the [endoflife.date](https://endoflife.date) API. The API call runs locally from the deployer machine.

| Condition | Status | Example |
|-----------|--------|---------|
| End of Life | FAIL | `End of Life since 2024-12-31` |
| EOL approaching | WARN | `EOL in 3 month(s) (2026-06-30)` |
| Security support only | WARN | `Security support only, EOL 2027-12-31` |
| Fully maintained | OK | `Maintained until 2028-12-31` |
| API unreachable | SKIP | `Could not reach endoflife.date API` |

The warning threshold is configurable (default: 6 months before EOL).

## Health check

A standalone task that verifies critical services are running on the target host. This is useful as a quick smoke test before or after deployment.

```bash
$ dep requirements:health [host]
```

The task checks four categories:

| Check | Method | OK | FAIL |
|-------|--------|----|------|
| PHP-FPM | systemctl / pgrep for `php<version>-fpm` | Process active | Process not found |
| Webserver | systemctl / pgrep for nginx, apache2, httpd | Process active | No process found |
| Database server | `mysqladmin ping` / `mariadb-admin ping` with process fallback | Responding or process running | No process found |
| HTTP response | `curl` against configured URL | HTTP 2xx–4xx | HTTP 5xx, timeout, or connection refused |

Service detection uses `systemctl is-active` with a `pgrep` fallback for systems without systemd.

## Configuration

All settings use the `requirements_` prefix and can be overridden in the consuming project:

```php
// Disable specific checks
set('requirements_check_database_enabled', false);
set('requirements_check_image_processing_enabled', false);

// Override PHP minimum version
set('requirements_php_min_version', '8.3.0');

// Override PHP extensions list
set('requirements_php_extensions', ['pdo', 'session', 'xml', 'mbstring', 'intl']);

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

// Override database minimum versions
set('requirements_mariadb_min_version', '10.6.0');
set('requirements_mysql_min_version', '8.0.30');

// Override image processing minimum versions
set('requirements_graphicsmagick_min_version', '1.3.30');
set('requirements_imagemagick_min_version', '7.0');

// Override composer minimum version
set('requirements_composer_min_version', '2.6.0');

// Override locales
set('requirements_locales', ['de_DE.utf8', 'en_US.utf8', 'fr_FR.utf8']);

// Override user group
set('requirements_user_group', 'apache');

// Override required env variables
set('requirements_env_vars', ['DATABASE_URL', 'APP_SECRET']);

// EOL check configuration
set('requirements_check_eol_enabled', true);
set('requirements_eol_warn_months', 6);   // Warn X months before EOL
set('requirements_eol_api_timeout', 5);   // API timeout in seconds

// Database grants check
set('requirements_check_database_grants_enabled', true);

// Health check
set('requirements_check_health_enabled', true);
set('requirements_health_url', 'https://example.com');
```

## Extending with custom checks

Custom checks can be added via Deployer's `before()` hook:

```php
task('requirements:check:my_vhost', function () {
    addRequirementRow('Apache VHost', \Deployer\REQUIREMENT_OK, 'Configured');
})->hidden();

before('requirements:check:summary', 'requirements:check:my_vhost');
```
