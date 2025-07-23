<?php

namespace Deployer;

// cache warmup
task('cache:warmup', function () {
    $siteConfig = has('site_config') ? get('site_config') : 'main';
    $activeDir = test('[ -e {{deploy_path}}/release ]') ?
        get('deploy_path') . '/release' :
        get('deploy_path') . '/current';
    runExtended('cd ' . $activeDir . ' && {{bin/php}} {{bin/typo3cms}} warming:cachewarmup -s ' . $siteConfig);
});
before('cache:warmup', 'feature:init');
