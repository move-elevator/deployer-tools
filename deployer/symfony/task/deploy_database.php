<?php

namespace Deployer;

use MoveElevator\DeployerTools\Utility\EnvUtility;

task('deploy:database:update', function () {
    switch (get('deploy_database_update_method', 'migrations_migrate')) {
        case 'migrations_migrate':
            runExtended('{{bin/php}} {{bin/console}} doctrine:migrations:migrate {{console_options}} --allow-no-migration');
            break;
        case 'schema_update':
            runExtended('{{bin/php}} {{bin/console}} doctrine:schema:update {{console_options}} --force');
            break;
    }
})->desc('Update symfony database');

function getDatabasePasswordForSymfony(): string|bool
{
    $vars = EnvUtility::getRemoteEnvVars('/.env.local');
    if (array_key_exists('DATABASE_URL', $vars)) {
        return parse_url($vars['DATABASE_URL'], PHP_URL_PASS);
    }
    return false;
}

function getDatabaseNameForSymfony(): string|bool
{
    $vars = EnvUtility::getRemoteEnvVars('/.env.local');
    if (array_key_exists('DATABASE_URL', $vars)) {
        return parse_url($vars['DATABASE_URL'], PHP_URL_PATH);
    }
    return false;
}
