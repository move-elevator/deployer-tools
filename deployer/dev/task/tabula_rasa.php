<?php

namespace Deployer;

use Deployer\Exception\Exception;

task('dev:tr', [
    'dev:tabula_rasa:composer_install_app',
    'dev:tabula_rasa:composer_install_ci',
    'dev:tabula_rasa:npm_build',
    'dev:tabula_rasa:db_sync',
])->desc('Reset all changes, install dependencies and sync');

task('dev:release:tabula_rasa', function () {
    $modifiedFiles = runLocally("git status -uno -s");
    if ($modifiedFiles) {
        throw new Exception("Please commit modified files before starting a new release", 1711460221);
    }

    info("checkout branch: " . get('dev_default_branch'));
    runLocally('git pull');
    runLocally('git remote prune origin');
    runLocally('git checkout ' . get('dev_default_branch'));
    runLocally('git reset');

    invoke('dev:tr');
})
    ->desc('Tabula rasa for release process');

task('dev:tabula_rasa:composer_install_app', function () {
    composerInstall("app");
})
    ->desc('Install composer dependencies for app');

task('dev:tabula_rasa:composer_install_ci', function () {
    composerInstall("ci");
})
    ->desc('Install composer dependencies for ci');

task('dev:tabula_rasa:npm_build', function () {
    info("npm ci");
    runLocally("npm ci --prefix " . get('npm_path_app'));
    info("npm build");
    runLocally("npm run build --prefix " . get('npm_path_app'));
})
    ->desc('Install npm dependencies and run build process');

task('dev:tabula_rasa:db_sync', function () {
    if (input()->getOption('no-db-sync')) return;

    set('dev_db_sync_tool_use_current_branch', true);

    // check if there is already a dump of the current day
    if (!input()->getOption('cache-db')) {
        invoke('dev:sync');
        return;
    }

    if (recentDatabaseCacheDumpExists()) {
        warning('Database dump already existing for today. Importing it...');
        invoke('dev:import');
        return;
    }

    invoke('dev:sync');

    info('Caching database to ' . getRecentDatabaseCacheDumpPath() . '...');
    invoke('dev:dump');
})
    ->desc('Sync database with db-sync-tool');

function composerInstall(string $mode = "app"): void {
    info("composer install ($mode)");
    runLocally("composer install --working-dir " . get("composer_path_$mode"));
}
