<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

// Version detection runs on the remote server (via run()),
// but EOL API lookups run locally (via file_get_contents)
// to avoid outbound HTTP from production servers.
task('requirements:check:eol', function (): void {
    if (!get('requirements_check_eol_enabled')) {
        return;
    }

    $warnMonths = max(1, (int) get('requirements_eol_warn_months'));
    $timeout = (int) get('requirements_eol_api_timeout');

    // Check PHP EOL
    try {
        $phpCycle = trim(run('php -r "echo PHP_MAJOR_VERSION.\'.\'.PHP_MINOR_VERSION;" 2>/dev/null'));
    } catch (RunException) {
        $phpCycle = '';
    }

    if ($phpCycle !== '') {
        checkEolForProduct('PHP', 'php', $phpCycle, $warnMonths, $timeout);
    }

    // Check database EOL
    $db = detectDatabaseProduct();

    if ($db !== null) {
        checkEolForProduct($db['label'], $db['product'], $db['cycle'], $warnMonths, $timeout);
    }
})->hidden();
