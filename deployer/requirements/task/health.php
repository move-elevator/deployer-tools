<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:health', function (): void {
    set('requirements_rows', []);

    if (!get('requirements_check_health_enabled')) {
        return;
    }

    // 1. PHP-FPM
    try {
        $phpVersion = trim(run('php -r "echo PHP_MAJOR_VERSION.\'.\'.PHP_MINOR_VERSION;" 2>/dev/null'));

        if (!preg_match('/^\d+\.\d+$/', $phpVersion)) {
            throw new RunException('php', 1, "Unexpected PHP version output: $phpVersion", '');
        }

        $fpmService = isServiceActive("php$phpVersion-fpm", 'php-fpm');

        if ($fpmService !== null) {
            addRequirementRow('PHP-FPM', REQUIREMENT_OK, "Active ($fpmService)");
        } else {
            addRequirementRow('PHP-FPM', REQUIREMENT_FAIL, 'Process not found');
        }
    } catch (RunException) {
        addRequirementRow('PHP-FPM', REQUIREMENT_SKIP, 'Could not determine PHP version');
    }

    // 2. Webserver
    $webserver = isServiceActive('nginx', 'apache2', 'httpd');

    if ($webserver !== null) {
        addRequirementRow('Webserver', REQUIREMENT_OK, "Active ($webserver)");
    } else {
        addRequirementRow('Webserver', REQUIREMENT_FAIL, 'No nginx, apache2 or httpd process found');
    }

    // 3. Database server
    $db = detectDatabaseProduct();
    $dbLabel = $db !== null ? $db['label'] : 'Database';
    $adminCmd = ($db !== null && $db['product'] === 'mariadb') ? 'mariadb-admin' : 'mysqladmin';
    $dbCredentials = resolveDatabaseCredentials();
    $dbChecked = false;

    // Try mysqladmin ping with credentials when available
    if ($dbCredentials !== null) {
        $pingCmd = sprintf(
            '%s ping --silent -h %s -P %d -u %s --password=%s 2>/dev/null',
            $adminCmd,
            escapeshellarg($dbCredentials['host']),
            $dbCredentials['port'],
            escapeshellarg($dbCredentials['user']),
            escapeshellarg($dbCredentials['password'])
        );

        try {
            run($pingCmd);
            $hostInfo = $dbCredentials['host'] === '127.0.0.1' || $dbCredentials['host'] === 'localhost'
                ? 'local'
                : $dbCredentials['host'];
            addRequirementRow('Database server', REQUIREMENT_OK, "$dbLabel responding ($hostInfo)");
            $dbChecked = true;
        } catch (RunException) {
            // Credentials available but ping failed — fall through
        }
    }

    // Fallback: try mysqladmin ping without credentials (uses ~/.my.cnf or socket)
    if (!$dbChecked) {
        try {
            run("$adminCmd ping --silent 2>/dev/null");
            addRequirementRow('Database server', REQUIREMENT_OK, "$dbLabel responding");
            $dbChecked = true;
        } catch (RunException) {
            // Admin tool not available — fall through to process check
        }
    }

    // Only check for local processes if the database host is local (or unknown)
    if (!$dbChecked) {
        $isRemoteDb = $dbCredentials !== null
            && $dbCredentials['host'] !== '127.0.0.1'
            && $dbCredentials['host'] !== 'localhost';

        if ($isRemoteDb) {
            addRequirementRow('Database server', REQUIREMENT_FAIL, sprintf(
                'Cannot reach %s on %s:%d',
                $dbLabel,
                $dbCredentials['host'],
                $dbCredentials['port']
            ));
        } else {
            $dbProcess = isServiceActive('mysqld', 'mariadbd');

            if ($dbProcess !== null) {
                addRequirementRow('Database server', REQUIREMENT_OK, "$dbLabel process running ($dbProcess)");
            } else {
                addRequirementRow('Database server', REQUIREMENT_FAIL, 'No mysqld or mariadbd process found');
            }
        }
    }

    // 4. HTTP response
    $url = get('requirements_health_url');

    try {
        $httpCode = (int) trim(run(
            sprintf("curl -s -o /dev/null -w '%%{http_code}' --max-time 5 %s 2>/dev/null", escapeshellarg($url))
        ));

        if ($httpCode >= 200 && $httpCode < 500) {
            addRequirementRow('HTTP response', REQUIREMENT_OK, "HTTP $httpCode from $url");
        } elseif ($httpCode === 0) {
            addRequirementRow('HTTP response', REQUIREMENT_FAIL, "No response from $url (connection refused or timeout)");
        } else {
            addRequirementRow('HTTP response', REQUIREMENT_FAIL, "HTTP $httpCode from $url");
        }
    } catch (RunException) {
        addRequirementRow('HTTP response', REQUIREMENT_SKIP, 'curl not available');
    }

    renderRequirementsTable();
})->desc('Check service health');
