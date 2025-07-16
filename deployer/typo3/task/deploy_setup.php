<?php

namespace Deployer;

task('deploy:additional_setup', function () {
    $activeDir = test('[ -e {{deploy_path}}/release ]') ?
        get('deploy_path') . '/release' :
        get('deploy_path') . '/current';
    runExtended('cd ' . $activeDir . ' &&  {{bin/php}} {{bin/typo3cms}} install:fixfolderstructure && {{bin/php}} {{bin/typo3cms}} install:extensionsetupifpossible');
});
