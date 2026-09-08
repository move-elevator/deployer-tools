<?php

namespace Deployer;

task('database:backup', function () {

    $optionalVerbose = isVerbose() ? '-v' : '';

    $dbSyncTool = get('db_sync_tool');

    if (false === $dbSyncTool) {
        debug('Skipping database backup, db_sync_tool was disabled');
        return;
    }

    if (syncToolAvailableLocally($dbSyncTool)) {
        $useRsync = usingPhpSyncTool($dbSyncTool) ? '' : '--use-rsync';
        info('Generating a database backup');
        runLocally("$dbSyncTool -f {{sync_database_backup_config}} $useRsync -y $optionalVerbose");
    } else {
        debug("Skipping database backup, $dbSyncTool not available");
    }

})
    ->once()
    ->desc('Generating a database backup');
