<?php

namespace Deployer;

# general
set('dev_default_branch', 'main');
set('composer_path_app', '.');
set('composer_app_release_options', '--no-scripts --no-plugins');
set('composer_path_ci', '.');
set('npm_path_app', '.');
set('dev_git_message_new_version_build', 'build: version');
set('dev_git_message_new_version_release', 'release: version');
set('dev_git_message_composer_update_app', 'chore(app): composer update');
set('dev_git_message_composer_update_ci', 'chore(ci): composer update');
set('dev_git_message_npm_update', 'chore(app): npm update');
set('dev_git_commit_options', '--no-verify');
set('dev_semver_regex', '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/');
set('dev_composer_regex', '/- Upgrading ([\w\/-]+) \((v?\d+\.\d+\.\d+ => v?\d+\.\d+\.\d+)\)/');
set('dev_npm_regex', '/^ (\S+)\s+(\^\d+\.\d+\.\d+)\s+→\s+(\^\d+\.\d+\.\d+)$/m');
set('dev_npm_audit_regex', '/\b(?:found\s+)?(\d+)\s+vulnerabilities\b/');
set('dev_db_sync_tool_config_path', './.deployment/db-sync-tool');
set('dev_db_sync_tool_default_sync', 'sync-stage-to-local.yaml');
set('dev_db_sync_tool_prod_sync', 'sync-prod-to-local.yaml');
set('dev_db_sync_tool_credential_path', '/shared/.env');
set('dev_db_sync_tool_origin_path', function () {
    $stage = host('stage');
    $path = $stage->get('deploy_path');

    if (has('feature_url_shortener') && get('feature_url_shortener')) {
        $path .= '/' . get('feature_url_shortener_path');
    }
    $path .= '<feature>' . get('dev_db_sync_tool_credential_path');
    return $path;
});
set('dev_tr_db_dump_dir', function () {
    return run('pwd') . '/.deployment/tr-db-dumps/' . runLocally('git branch --show-current');
});

localhost('local');
