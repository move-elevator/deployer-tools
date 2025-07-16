<?php

namespace Deployer;

require_once('feature_init.php');


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
