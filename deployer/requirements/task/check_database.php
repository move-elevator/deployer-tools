<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:database', function (): void {
    if (!get('requirements_check_database_enabled')) {
        return;
    }

    // Try mariadb command first (newer MariaDB installations), then fall back to mysql
    $versionOutput = '';
    $clientFound = false;

    foreach (['mariadb', 'mysql'] as $command) {
        try {
            $versionOutput = trim(run("$command --version 2>/dev/null"));

            if ($versionOutput !== '') {
                $clientFound = true;

                break;
            }
        } catch (RunException) {
            continue;
        }
    }

    if (!$clientFound) {
        addRequirementRow('Database client', REQUIREMENT_SKIP, 'Neither mariadb nor mysql command available');

        return;
    }

    $minVersion = get('requirements_db_min_version');

    if (preg_match('/Distrib\s+([\d.]+)/', $versionOutput, $matches)
        || preg_match('/Ver\s+([\d.]+)/', $versionOutput, $matches)) {
        $actualVersion = $matches[1];
        $meets = version_compare($actualVersion, $minVersion, '>=');
        $info = $meets ? $actualVersion : "$actualVersion (required: >= $minVersion)";
        addRequirementRow(
            'Database client',
            $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL,
            $info
        );
    } else {
        addRequirementRow('Database client', REQUIREMENT_WARN, "Could not parse version: $versionOutput");
    }
})->hidden();
