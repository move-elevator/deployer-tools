<?php

namespace Deployer;

task('dev:release:test:acceptance', function () {
    if (!checkStepIsEnabled('dev:release:test:acceptance')) { return; }
    info('ToDo');
})
    ->desc('Run acceptance tests');
