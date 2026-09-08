<?php

namespace Deployer;

// resolves to vendor/bin/sync-tool (php-sync-tool) when available locally, falling back
// to the legacy db_sync_tool PATH binary; set to false, to disable db backup
set('db_sync_tool', function () {
    return resolveSyncTool('db_sync_tool');
});
#set('sync_database_backup_config', null);
