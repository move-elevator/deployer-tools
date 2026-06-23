<?php

namespace Deployer;

task('database:backup', function () {

    $optionalVerbose = isVerbose() ? '-v' : '';

    if (false === get('db_sync_tool')) {
        debug('Skipping database backup, db_sync_tool was disabled');
        return;
    }

    if (commandExistLocally("{{db_sync_tool}}")) {
        info('Generating a database backup');
        runLocally("{{db_sync_tool}} -f {{sync_database_backup_config}} --use-rsync -y $optionalVerbose");
    } else {
        debug("Skipping database backup, {{db_sync_tool}} not available");
    }

})
    ->once()
    ->desc('Generating a database backup');
