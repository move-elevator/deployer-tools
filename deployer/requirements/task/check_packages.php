<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:packages', function (): void {
    if (!get('requirements_check_packages_enabled')) {
        return;
    }

    foreach (get('requirements_packages') as $displayName => $command) {
        if (is_int($displayName)) {
            $displayName = $command;
        }

        try {
            $found = test("command -v $command >/dev/null 2>&1");
        } catch (RunException) {
            addRequirementRow("Package: $displayName", REQUIREMENT_SKIP, 'Check failed');

            continue;
        }

        if ($found) {
            addRequirementRow("Package: $displayName", REQUIREMENT_OK, 'Installed');
        } else {
            addRequirementRow("Package: $displayName", REQUIREMENT_FAIL, "Command '$command' not found");
        }
    }
})->hidden();
