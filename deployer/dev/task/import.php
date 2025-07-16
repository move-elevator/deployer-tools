<?php

namespace Deployer;

task('dev:import', function () {
    $dbDumpDir = get('dev_tr_db_dump_dir');
    $dbDumpFilename = getRecentDatabaseCacheDumpFilename();

    $dbSyncToolSync = get('dev_db_sync_tool_default_sync');

    $dbSyncToolConfigPath = get('dev_db_sync_tool_config_path');
    runLocally("db_sync_tool -f $dbSyncToolConfigPath/$dbSyncToolSync -y -i $dbDumpDir/$dbDumpFilename.sql", ['real_time_output' => true]);
})
    ->desc('Sync database with drush');
