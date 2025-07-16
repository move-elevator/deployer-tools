<?php

namespace Deployer;

/**
 * Workaround for simplifying the autoloading process
 */
$vendorRoot = is_dir(__DIR__ . '/../../../../../vendor') ? __DIR__ . '/../../../../..' : __DIR__ . '/../../../..';

require_once($vendorRoot . '/vendor/sourcebroker/deployer-loader/autoload.php');
new \MoveElevator\DeployerTools\Symfony\Loader();

require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/autoload.php');

require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/functions.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/build_assets.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/deploy_assets_install.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/deploy_cache_clear.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/deploy_cache_warmup.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/deploy_ckeditor_install.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/deploy_database.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/deploy_writable_chmod.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/feature_sync_standalone.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/rollback.php');

require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/config/set.php');

require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/symfony/task/deploy.php');
