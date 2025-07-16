<?php

namespace Deployer;

task('dev:dump', function () {
    $target = runLocally('git branch --show-current');

    $dbDumpDir = get('dev_tr_db_dump_dir');
    $dbDumpFilename = getRecentDatabaseCacheDumpFilename();

    run("mkdir -p $dbDumpDir");

    // cleanup beforehand: delete all dump files with the above naming scheme older than 7 days
    cleanUpDatabaseCacheDumps();

    $dbSyncToolSync = get('dev_db_sync_tool_default_sync');

    $dbSyncToolOriginPath = str_replace('<feature>', $target, get('dev_db_sync_tool_origin_path'));
    $additionalOptions = "--origin-path $dbSyncToolOriginPath";

    $dbSyncToolConfigPath = get('dev_db_sync_tool_config_path');
    runLocally("db_sync_tool -f $dbSyncToolConfigPath/$dbSyncToolSync -y -kd $dbDumpDir -dn $dbDumpFilename $additionalOptions", ['real_time_output' => true]);
})
    ->desc('Sync database with drush');
