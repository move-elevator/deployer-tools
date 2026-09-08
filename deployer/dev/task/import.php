<?php

namespace Deployer;

task('dev:import', function () {
    $dbDumpDir = get('dev_tr_db_dump_dir');
    $dbDumpFilename = getRecentDatabaseCacheDumpFilename();

    $dbSyncToolSync = get('dev_db_sync_tool_default_sync');

    $dbSyncTool = requireSyncTool('import');

    $dbSyncToolConfigPath = get('dev_db_sync_tool_config_path');
    runLocally(escapeshellarg($dbSyncTool) . " -f $dbSyncToolConfigPath/$dbSyncToolSync -y -i $dbDumpDir/$dbDumpFilename.sql", ['real_time_output' => true]);
})
    ->desc('Sync database with drush');
