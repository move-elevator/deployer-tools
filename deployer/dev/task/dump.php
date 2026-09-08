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

    $dbSyncTool = requireSyncTool('dump');

    // php-sync-tool has no -kd/-dn equivalent yet (konradmichalik/php-sync-tool#33); until it
    // does, the dump lands wherever the project's own sync-tool YAML config's "dump_dir" says,
    // not necessarily $dbDumpDir
    $dumpLocationOptions = usingPhpSyncTool($dbSyncTool) ? '' : "-kd $dbDumpDir -dn $dbDumpFilename";

    $dbSyncToolConfigPath = get('dev_db_sync_tool_config_path');
    runLocally(escapeshellarg($dbSyncTool) . " -f $dbSyncToolConfigPath/$dbSyncToolSync -y $dumpLocationOptions $additionalOptions", ['real_time_output' => true]);
})
    ->desc('Sync database with drush');
