# Database Management

The database management should support different server environments

## Root

This is the default database manager type. It uses the root user to create and delete databases. This is not recommended for production environments, but it is useful for local development or testing.

### Prerequirements

You need a database user with the following grants to dynamically create and delete new databases:

- `SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE`

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

## API

ToDo

### Configuration

```php
set('database_manager_type', 'api');
```
