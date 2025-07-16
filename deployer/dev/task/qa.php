<?php

namespace Deployer;

task('dev:release:qa:php', function () {
    if (!checkStepIsEnabled('dev:release:qa:php')) { return; }
    runLocally("composer check --working-dir " . get('composer_path_app'));
})
    ->desc('QA php');

task('dev:release:qa:npm', function () {
    if (!checkStepIsEnabled('dev:release:qa:npm')) { return; }
    runLocally("npm run lint ---prefix " . get('npm_path_app'));
})
    ->desc('QA npm');
