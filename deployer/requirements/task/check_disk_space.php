<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:disk_space', function (): void {
    if (!get('requirements_check_disk_space_enabled')) {
        return;
    }

    $warnPercent = (int) get('requirements_disk_space_warn_percent');
    $failPercent = (int) get('requirements_disk_space_fail_percent');

    checkDiskSpaceAtPath('Disk space: webspace', get('requirements_disk_space_webspace_path'), $warnPercent, $failPercent);

    $credentials = resolveDatabaseCredentials();

    if (null === $credentials) {
        addRequirementRow('Disk space: database', REQUIREMENT_SKIP, 'No database credentials available');

        return;
    }

    if (!in_array($credentials['host'], ['127.0.0.1', 'localhost'], true)) {
        addRequirementRow(
            'Disk space: database',
            REQUIREMENT_SKIP,
            "Database host ({$credentials['host']}) not reachable from the deploy target"
        );

        return;
    }

    $datadir = detectDatabaseDatadir($credentials);

    if (null === $datadir) {
        addRequirementRow('Disk space: database', REQUIREMENT_SKIP, 'Could not determine database data directory');

        return;
    }

    checkDiskSpaceAtPath('Disk space: database', $datadir, $warnPercent, $failPercent);
})->hidden();

/**
 * @param array{user: string, password: string, host: string, port: int} $credentials
 */
function detectDatabaseDatadir(array $credentials): ?string
{
    try {
        $output = trim(runMysqlQuery($credentials, "SHOW VARIABLES LIKE 'datadir'"));
    } catch (RunException) {
        return null;
    }

    // Output format: "datadir\t/var/lib/mysql/", possibly preceded by mysql client warning lines
    // on stderr (merged into stdout by runMysqlQuery()), so only the last line is relevant.
    $lines = explode("\n", $output);
    $columns = explode("\t", trim((string) end($lines)));
    $path = $columns[1] ?? null;

    return ('' !== $path && null !== $path) ? $path : null;
}
