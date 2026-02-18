<?php

declare(strict_types=1);

namespace Deployer;

task('requirements:check:env', function (): void {
    if (!get('requirements_check_env_enabled')) {
        return;
    }

    $envFile = get('requirements_env_file');
    $envPath = get('deploy_path') . '/shared/' . $envFile;
    $requiredVars = get('requirements_env_vars');

    // Check if shared .env exists
    if (!test("[ -f $envPath ]")) {
        addRequirementRow(
            "Env file: $envFile",
            REQUIREMENT_WARN,
            "Not found at shared/$envFile"
        );

        foreach ($requiredVars as $var) {
            addRequirementRow("Env var: $var", REQUIREMENT_SKIP, 'Env file not available');
        }

        return;
    }

    addRequirementRow("Env file: $envFile", REQUIREMENT_OK, "Exists at shared/$envFile");

    if (empty($requiredVars)) {
        return;
    }

    // Check required env variables
    $envVars = getSharedEnvVars();

    foreach ($requiredVars as $var) {
        if (array_key_exists($var, $envVars) && $envVars[$var] !== '') {
            addRequirementRow("Env var: $var", REQUIREMENT_OK, 'Set');
        } elseif (array_key_exists($var, $envVars)) {
            addRequirementRow("Env var: $var", REQUIREMENT_WARN, 'Set but empty');
        } else {
            addRequirementRow("Env var: $var", REQUIREMENT_FAIL, 'Missing');
        }
    }
})->hidden();
