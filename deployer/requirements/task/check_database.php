<?php

declare(strict_types=1);

namespace Deployer;

task('requirements:check:database', function (): void {
    if (!get('requirements_check_database_enabled')) {
        return;
    }

    $db = detectDatabaseProduct();

    if ($db === null) {
        addRequirementRow('Database client', REQUIREMENT_SKIP, 'Neither mariadb nor mysql command available');

        return;
    }

    $minVersion = $db['product'] === 'mariadb'
        ? get('requirements_mariadb_min_version')
        : get('requirements_mysql_min_version');

    $meets = version_compare($db['version'], $minVersion, '>=');
    $info = $meets
        ? "{$db['label']} {$db['version']}"
        : "{$db['label']} {$db['version']} (required: >= $minVersion)";

    addRequirementRow('Database client', $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL, $info);
})->hidden();
