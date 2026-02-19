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

        if (!$found) {
            addRequirementRow("Package: $displayName", REQUIREMENT_FAIL, "Command '$command' not found");

            continue;
        }

        // Composer has version validation against a minimum
        if ($command === 'composer') {
            try {
                $versionOutput = trim(run('composer --version 2>/dev/null'));
                $minVersion = get('requirements_composer_min_version');

                if (preg_match('/Composer\s+(?:version\s+)?([\d.]+)/', $versionOutput, $matches)) {
                    $actualVersion = $matches[1];
                    $meets = version_compare($actualVersion, $minVersion, '>=');
                    $info = $meets
                        ? "Composer $actualVersion"
                        : "Composer $actualVersion (required: >= $minVersion)";
                    addRequirementRow("Package: $displayName", $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL, $info);

                    continue;
                }
            } catch (RunException) {
                // Fall through to generic version detection
            }
        }

        // Try to retrieve version for informational display
        $version = detectPackageVersion($command);

        if ($version !== null) {
            addRequirementRow("Package: $displayName", REQUIREMENT_OK, "$displayName $version");
        } else {
            addRequirementRow("Package: $displayName", REQUIREMENT_OK, 'Installed');
        }
    }
})->hidden();
