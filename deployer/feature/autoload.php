<?php

namespace Deployer;

require_once(__DIR__ . '/config/options.php');
require_once(__DIR__ . '/config/set.php');

require_once(__DIR__ . '/task/feature_init.php');
require_once(__DIR__ . '/task/feature_setup.php');
require_once(__DIR__ . '/task/feature_stop.php');
require_once(__DIR__ . '/task/feature_notify.php');
require_once(__DIR__ . '/task/feature_sync.php');
require_once(__DIR__ . '/task/feature_sync_standalone.php');
require_once(__DIR__ . '/task/feature_list.php');
require_once(__DIR__ . '/task/feature_index.php');
require_once(__DIR__ . '/task/feature_cleanup.php');

require_once(__DIR__ . '/task/deploy.php');
