<?php

namespace Deployer;

require_once('feature_init.php');


/**
 * Verifies that the database host is reachable from the remote server before syncing.
 * This is necessary because DNS for newly created databases (e.g. Mittwald) can be
 * intermittent, and the time between feature:setup and feature:sync may cause DNS flapping.
 */
function waitForDatabaseHost(): void
{
    if (!has('database_host')
        || '127.0.0.1' === get('database_host')
        || 'localhost' === get('database_host')
    ) {
        return;
    }

    $hostname = get('database_host');
    $port = (int) get('database_port', 3306);
    $waitingTime = (int) get('mittwald_database_wait', 30);
    $maxRetries = (int) get('mittwald_database_retries', 20);

    while ($maxRetries > 0) {
        try {
            info("Verifying database host {$hostname}:{$port} before sync, remaining attempts: {$maxRetries}");
            $check = sprintf(
                'echo @fsockopen("%s", %d, $errno, $errstr, 5) ? "1" : "";',
                $hostname,
                $port
            );
            $result = run("php -r " . escapeshellarg($check));
            if ('1' === trim($result)) {
                info("Database host {$hostname}:{$port} is reachable.");
                return;
            }
        } catch (\Throwable $e) {
            debug("Pre-sync connectivity check failed: " . $e->getMessage());
        }

        if ($maxRetries > 1) {
            sleep($waitingTime);
        }
        $maxRetries--;
    }

    throw new \RuntimeException(
        "Database host {$hostname}:{$port} is not reachable before sync after all attempts."
    );
}


task('feature:wait_for_database', function () {
    if ((has('feature_setup') && !get('feature_setup')) || !input()->getOption('feature')) return;
    waitForDatabaseHost();
})
    ->select('type=feature-branch-deployment')
    ->once()
    ->desc('Wait for database host to be reachable')
;


task('feature:sync', function () {

    if ((has('feature_setup') && !get('feature_setup')) || !input()->getOption('feature')) return;

    $feature = initFeature();
    $synced = false;
    $optionalVerbose = isVerbose() ? '-v' : '';

    /*
     * db_sync_tool
     * https://github.com/jackd248/db-sync-tool
     */
    if (get('db_sync_tool') !== false) {
        if (commandExistLocally("{{db_sync_tool}}")) {
            info('Synching database');
            runLocally("{{db_sync_tool}} -f {{feature_sync_config}} --target-path {{feature_sync_target_path}} --use-rsync -y $optionalVerbose");
            $synced = true;
        } else {
            debug("Skipping database sync, command \”{{db_sync_tool}}\” not available");
        }
    } else {
        debug("Skipping database sync, db_sync_tool was disabled");
    }

    /*
     * file_sync_tool
     * https://github.com/jackd248/file-sync-tool
     */
    if (get('file_sync_tool') !== false) {
        if (commandExistLocally("{{file_sync_tool}}")) {
            info('Synching files');
            runLocally("{{file_sync_tool}} -f {{feature_sync_config}} --files-target {{feature_sync_target_path_files}} $optionalVerbose");
            $synced = true;
        } else {
            debug("Skipping file sync, command \”{{file_sync_tool}}\” not available");
        }
    } else {
        debug("Skipping file sync, file_sync_tool was disabled");
    }

    if ($synced) info("feature branch <fg=magenta;options=bold>$feature</> was successfully synced");
})
    ->select('type=feature-branch-deployment')
    ->desc('Sync a feature branch')
;
