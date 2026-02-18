<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:php_extensions', function (): void {
    if (!get('requirements_check_php_extensions_enabled')) {
        return;
    }

    try {
        $loadedModules = strtolower(run('php -m 2>/dev/null'));
    } catch (RunException) {
        addRequirementRow('PHP Extensions', REQUIREMENT_SKIP, 'Could not retrieve PHP modules');

        return;
    }

    $moduleLines = array_map('trim', explode("\n", $loadedModules));

    foreach (get('requirements_php_extensions') as $extension) {
        $found = in_array(strtolower($extension), $moduleLines, true);

        if ($found) {
            addRequirementRow("PHP ext: $extension", REQUIREMENT_OK, 'Loaded');
        } else {
            addRequirementRow("PHP ext: $extension", REQUIREMENT_FAIL, 'Not loaded');
        }
    }
})->hidden();
