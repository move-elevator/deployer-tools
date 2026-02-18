<?php

namespace Deployer;

/**
 * Workaround for simplifying the autoloading process
 */
$vendorRoot = is_dir(__DIR__ . '/../../../../../vendor') ? __DIR__ . '/../../../../..' : __DIR__ . '/../../../..';

require_once($vendorRoot . '/vendor/sourcebroker/deployer-loader/autoload.php');
new \MoveElevator\DeployerTools\TYPO3\Loader();

require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/autoload.php');

require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/functions.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/config/set.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/task/deploy_writable_chmod.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/task/deploy.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/task/deploy_cache.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/task/deploy_database.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/task/deploy_setup.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/task/cache_warmup.php');
require_once($vendorRoot . '/vendor/move-elevator/deployer-tools/deployer/typo3/task/feature_scheduler.php');
