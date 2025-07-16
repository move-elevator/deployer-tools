<?php

namespace Deployer;

require_once(__DIR__ . '/deployer/functions.php');


/*
 * notification
 */
require_once(__DIR__ . '/deployer/notification/config/set.php');
require_once(__DIR__ . '/deployer/notification/task/ms_teams.php');
require_once(__DIR__ . '/deployer/notification/task/deploy_prod_notify.php');

/*
 * build
 */
require_once(__DIR__ . '/deployer/build/autoload.php');

/*
 * rsync
 */
require_once(__DIR__ . '/deployer/rsync/config/set.php');

/*
 * sync
 */
require_once(__DIR__ . '/deployer/sync/config/set.php');
require_once(__DIR__ . '/deployer/sync/task/database_backup.php');

/*
 * security
 */
require_once(__DIR__ . '/deployer/security/config/set.php');
require_once(__DIR__ . '/deployer/security/config/options.php');
require_once(__DIR__ . '/deployer/security/task/security.php');

/*
 * dev
 */
require_once(__DIR__ . '/deployer/dev/functions.php');
require_once(__DIR__ . '/deployer/dev/config/set.php');
require_once(__DIR__ . '/deployer/dev/config/options.php');
require_once(__DIR__ . '/deployer/dev/task/composer_update.php');
require_once(__DIR__ . '/deployer/dev/task/dump.php');
require_once(__DIR__ . '/deployer/dev/task/import.php');
require_once(__DIR__ . '/deployer/dev/task/npm_update.php');
require_once(__DIR__ . '/deployer/dev/task/qa.php');
require_once(__DIR__ . '/deployer/dev/task/release_finish.php');
require_once(__DIR__ . '/deployer/dev/task/release_reset.php');
require_once(__DIR__ . '/deployer/dev/task/start_new_release.php');
require_once(__DIR__ . '/deployer/dev/task/sync.php');
require_once(__DIR__ . '/deployer/dev/task/tabula_rasa.php');
require_once(__DIR__ . '/deployer/dev/task/test.php');
require_once(__DIR__ . '/deployer/dev/task/release.php');

/*
 * debug
 */
require_once(__DIR__ . '/deployer/debug/config/set.php');
require_once(__DIR__ . '/deployer/debug/task/debug_db.php');
require_once(__DIR__ . '/deployer/debug/task/debug_ssh.php');
require_once(__DIR__ . '/deployer/debug/task/debug_log_app.php');
