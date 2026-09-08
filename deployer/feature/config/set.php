<?php

namespace Deployer;


/**
 * Feature Deployment
 */
set('mysql', 'mysql');

set('project', 'kickstarter');

set('database_host', '127.0.0.1');
set('database_port', 3306);

// ToDo: actually not configurable because of the feature index app
set('feature_directory_path', '.fbd/');

#set('database_collation', null);
#set('database_charset', null);

/**
 * Feature Sync
 */
#set('feature_sync_config', null);
// resolves to vendor/bin/sync-tool (php-sync-tool) when available locally, falling back
// to the legacy db_sync_tool PATH binary; set to false, to disable db sync
set('db_sync_tool', function () {
    return resolveSyncTool('db_sync_tool');
});
// legacy-only: php-sync-tool syncs files via --with-files on the db_sync_tool call above,
// so this is unused once db_sync_tool resolves to it; set to false, to disable file sync
set('file_sync_tool', 'file_sync_tool');


set('feature_sync_target_path', null);
set('feature_sync_target_path_files', null);

/**
 * Feature Notification
 */
set('feature_msteams_color', '#0097A7');
set('feature_msteams_text', 'Der Branch **[{{feature}}]({{public_url}})** wurde bereitgestellt.');


/**
 * Feature Url Shortener
 */
set('feature_url_shortener', true);
set('feature_url_shortener_path', '{{feature_directory_path}}instances/');

/**
 * Feature Index
 */
set('feature_index_title', 'DEMO');
set('feature_index_app_path', '');
//set('feature_index_additional_links', [
//    'title' => 'path'
//]);
//set('feature_index_app_type', 'symfony');
set('feature_index_jira_browse', 'https://acme.atlassian.net/browse/');
set('feature_index_jira_api', 'https://acme.atlassian.net/rest/api/1/issue/');
set('feature_index_jira_auth', '');
set('feature_index_git_branch', '');

/**
 * Feature Stop
 */
set('feature_stop_disallowed_names', [
  'main',
  'master'
]);

/**
 * Database Manager
 */
set('database_manager_type', 'default');

// Resolve the Mittwald database hostname to its IP and pin it in .env (workaround for DNS
// flapping of freshly created databases). Disabled by default, since pinning the IP breaks
// when Mittwald rotates IPs or enforces TLS against the hostname. Enable only if affected by
// DNS flapping.
set('mittwald_resolve_host_to_ip', false);
