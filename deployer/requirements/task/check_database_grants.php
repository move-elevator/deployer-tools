<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:database_grants', function (): void {
    if (!get('requirements_check_database_grants_enabled')) {
        return;
    }

    $managerType = has('database_manager_type') ? get('database_manager_type') : null;

    if ($managerType === null) {
        addRequirementRow('Database grants', REQUIREMENT_SKIP, 'No database_manager_type configured');

        return;
    }

    if ($managerType === 'mittwald_api') {
        addRequirementRow('Database grants', REQUIREMENT_SKIP, 'Managed via Mittwald API');

        return;
    }

    if ($managerType === 'simple') {
        checkSimplePoolConnectivity();

        return;
    }

    // Root / default mode
    checkRootGrants();
})->hidden();

function checkRootGrants(): void
{
    $credentials = resolveDatabaseCredentials();

    if ($credentials === null) {
        addRequirementRow(
            'Database grants',
            REQUIREMENT_SKIP,
            'No credentials available (set database_user/database_password or DEPLOYER_CONFIG_DATABASE_PASSWORD)'
        );

        return;
    }

    $mysqlBin = has('mysql') ? get('mysql') : 'mysql';
    $connectInfo = sprintf('%s@%s:%d', $credentials['user'], $credentials['host'], $credentials['port']);

    try {
        $output = run(sprintf(
            '%s --connect-timeout=5 -u %s -p%s -h %s -P %d -N -e %s 2>&1',
            escapeshellarg($mysqlBin),
            escapeshellarg($credentials['user']),
            "'%secret%'",
            escapeshellarg($credentials['host']),
            $credentials['port'],
            escapeshellarg('SHOW GRANTS FOR CURRENT_USER()')
        ), secret: $credentials['password']);
    } catch (RunException) {
        addRequirementRow('Database: connectivity', REQUIREMENT_FAIL, "Cannot connect as $connectInfo");

        return;
    }

    addRequirementRow('Database: connectivity', REQUIREMENT_OK, "Connected as $connectInfo");

    $result = parseGlobalGrants($output);

    if ($result['ok']) {
        addRequirementRow('Database: global grants', REQUIREMENT_OK, 'All required grants on *.*');
    } else {
        addRequirementRow(
            'Database: global grants',
            REQUIREMENT_FAIL,
            'Missing on *.*: ' . implode(', ', $result['missing'])
        );
    }
}

function checkSimplePoolConnectivity(): void
{
    if (!has('database_pool')) {
        addRequirementRow('Database pool', REQUIREMENT_FAIL, 'database_pool not configured');

        return;
    }

    $pool = get('database_pool');

    if (empty($pool)) {
        addRequirementRow('Database pool', REQUIREMENT_FAIL, 'database_pool is empty');

        return;
    }

    addRequirementRow('Database pool', REQUIREMENT_OK, sprintf('%d database(s) configured', count($pool)));

    $mysqlBin = has('mysql') ? get('mysql') : 'mysql';

    foreach ($pool as $alias => $config) {
        $requiredKeys = ['database_user', 'database_password', 'database_name'];
        $missingKeys = array_diff($requiredKeys, array_keys(array_filter($config, 'is_string')));

        if (!empty($missingKeys)) {
            addRequirementRow(
                "Database pool: $alias",
                REQUIREMENT_FAIL,
                'Missing config: ' . implode(', ', $missingKeys)
            );

            continue;
        }

        $host = $config['database_host'] ?? '127.0.0.1';
        $port = (int) ($config['database_port'] ?? 3306);
        $password = $config['database_password'];

        // Resolve env var references (DEPLOYER_CONFIG_* pattern)
        if (str_starts_with($password, 'DEPLOYER_CONFIG_')) {
            $envValue = getenv($password);
            $password = is_string($envValue) && $envValue !== '' ? $envValue : '';
        }

        if ($password === '') {
            addRequirementRow("Database pool: $alias", REQUIREMENT_SKIP, 'Password not resolvable');

            continue;
        }

        try {
            run(sprintf(
                '%s --connect-timeout=5 -u %s -p%s -h %s -P %d -e %s %s 2>&1',
                escapeshellarg($mysqlBin),
                escapeshellarg($config['database_user']),
                "'%secret%'",
                escapeshellarg($host),
                $port,
                escapeshellarg('SELECT 1'),
                escapeshellarg($config['database_name'])
            ), secret: $password);

            addRequirementRow("Database pool: $alias", REQUIREMENT_OK, sprintf(
                '%s@%s:%d/%s',
                $config['database_user'],
                $host,
                $port,
                $config['database_name']
            ));
        } catch (RunException) {
            addRequirementRow("Database pool: $alias", REQUIREMENT_FAIL, sprintf(
                'Cannot connect as %s@%s:%d/%s',
                $config['database_user'],
                $host,
                $port,
                $config['database_name']
            ));
        }
    }
}
