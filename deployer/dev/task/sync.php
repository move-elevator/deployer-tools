<?php

namespace Deployer;

task('dev:sync', function () {
    $currentBranch = runLocally('git branch --show-current');

    if (has('dev_db_sync_tool_use_current_branch') && get('dev_db_sync_tool_use_current_branch')) {
        $target = $currentBranch;
    } else {
        if (host('stage')->get('labels')['type'] === 'feature-branch-deployment') {
            on(host('stage'), function () {
                $currentBranch = runLocally('git branch --show-current');
                $target = !is_null(input()->getOption('feature')) ? input()->getOption('feature') : askChoice('Please select a sync origin', array_merge(
                    ["[current] ($currentBranch)", "[prod]"],
                    array_map(function ($array) {
                        return $array[2];
                    }, listFeatureInstances())
                ), 0);
                // @ToDo why is the normal set function of deployer not working here?
                Deployer::get()->config->set('dev_db_sync_tool_current_target', $target);
            });
            $target = get('dev_db_sync_tool_current_target');
            if (str_starts_with($target, '[current]')) {
                $target = $currentBranch;
            }

            if (str_starts_with($target, '[prod]')) {
                $target = 'prod';
            }
        } else {
            // support legacy non feature branch deployments
            $target = askChoice('Please select a sync origin', ['prod', 'stage']);
        }
    }

    $additionalOptions = '';
    if ($target !== 'prod' && $target !== 'stage') {
        $dbSyncToolSync = get('dev_db_sync_tool_default_sync');

        $dbSyncToolOriginPath = str_replace('<feature>', $target, get('dev_db_sync_tool_origin_path'));
        $additionalOptions = "--origin-path $dbSyncToolOriginPath";
    } else {
        $dbSyncToolSync = $target === 'prod' ? get('dev_db_sync_tool_prod_sync') : get('dev_db_sync_tool_default_sync');
    }

    info("sync database from remote: $target");

    $dbSyncToolConfigPath = get('dev_db_sync_tool_config_path');
    runLocally("db_sync_tool -f $dbSyncToolConfigPath/$dbSyncToolSync -y $additionalOptions", ['real_time_output' => true]);
    info("💽 Database from $target synced successfully");
})
    ->desc('Sync database with db-sync-tool');
