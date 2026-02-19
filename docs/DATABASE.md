# Database Management

The database management should support different server environments

## Root

This is the default database manager type. It uses the root user to create and delete databases. This is not recommended for production environments, but it is useful for local development or testing.

### Prerequisites

A database user needs the following grants **on global level (`*.*`)** to dynamically create and delete databases:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER,
      CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE,
      CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE
ON *.* TO `<user>`@`<host>`;
FLUSH PRIVILEGES;
```

> [!IMPORTANT]
> The grants must be set on `*.*` (all databases), not on specific databases.
> Otherwise the user cannot create new databases or manage tables within
> dynamically created databases.

### Configuration

```php
set('database_manager_type', 'root');
```

## Simple

This database manager type uses a simple configuration file to manage the databases. It can be used in environments where a privileged database user is not available. Therefor it is not possible to create and delete databases dynamically. So a pool of existing databases must be provided, which can be used for the deployment. The database manager will use the first available database from the pool for a new feature instance.

### Prerequirements

An number of configured databases in the pool, e.g. 10-20 databases.

### Configuration

```php
set('database_manager_type', 'simple')

set('database_pool', [
    'db1' => [
        'database_host' => '{{database_host}}',
        'database_port' => '{{database_port}}',
        'database_user' => 'db1_user',
        'database_password' => 'DEPLOYER_CONFIG_DB1_PASSWORD',
        'database_name' => 'db1',
        'database_charset' => '{{database_charset}}',
        'database_collation' => '{{database_collation}}',
    ],
    ...
]);
```

## Mittwald API

This database manager type uses the [Mittwald API](https://developer.mittwald.de/) to create and delete databases on Mittwald hosting environments. The database creation is asynchronous - after the API returns, it polls the MySQL user status until it reports "ready" and verifies TCP connectivity before proceeding.

> [!NOTE]
> The Mittwald API client is an optional dependency. Install it with `composer require mittwald/api-client`.

### Prerequirements

- A Mittwald project with API access
- An API token with database management permissions

### Configuration

```php
set('database_manager_type', 'mittwald_api');

set('mittwald_api_client', 'your-api-token');
set('mittwald_project_id', 'your-project-id');
```

### Optional settings

| Setting | Default | Description |
|---------|---------|-------------|
| `mittwald_database_version` | `8.4` | MySQL version |
| `mittwald_database_character_set` | `utf8mb4` | Character set |
| `mittwald_database_collation` | `utf8mb4_unicode_ci` | Collation |
| `mittwald_database_wait` | `30` | Polling interval in seconds |
| `mittwald_database_retries` | `20` | Max retry attempts |

### DNS flapping

After database creation the DNS entry for the database host (e.g. `mysql-xyz.pg-s-xxx.db.project.host`) may not be resolvable immediately and can flap intermittently. The feature sync task resolves the database hostname to an IP address in the `.env` file to bypass this issue.
