<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:php_settings', function (): void {
    if (!get('requirements_check_php_settings_enabled')) {
        return;
    }

    // Retrieve and validate PHP version
    try {
        $phpVersion = trim(run('php -r "echo PHP_VERSION;" 2>/dev/null'));
        $minVersion = get('requirements_php_min_version');
        $meets = version_compare($phpVersion, $minVersion, '>=');
        $info = $meets ? $phpVersion : "$phpVersion (required: >= $minVersion)";
        addRequirementRow('PHP version', $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL, $info);
    } catch (RunException) {
        addRequirementRow('PHP version', REQUIREMENT_SKIP, 'Could not determine PHP version');
    }

    foreach (get('requirements_php_settings') as $setting => $expected) {
        try {
            $actual = trim(run(sprintf(
                "php -r \"echo ini_get('%s');\" 2>/dev/null",
                $setting
            )));
        } catch (RunException) {
            addRequirementRow("PHP: $setting", REQUIREMENT_SKIP, 'Could not read setting');

            continue;
        }

        if ($actual === '') {
            addRequirementRow("PHP: $setting", REQUIREMENT_WARN, "Not set (expected: $expected)");

            continue;
        }

        $meets = meetsPhpRequirement($actual, (string) $expected, $setting);
        $isComparison = in_array($setting, REQUIREMENT_BYTE_SETTINGS, true)
            || in_array($setting, REQUIREMENT_NUMERIC_SETTINGS, true);
        $info = $meets
            ? $actual
            : ($isComparison ? "$actual (expected: >= $expected)" : "$actual (expected: $expected)");
        addRequirementRow(
            "PHP: $setting",
            $meets ? REQUIREMENT_OK : REQUIREMENT_FAIL,
            $info
        );
    }
})->hidden();
