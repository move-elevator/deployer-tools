<?php

namespace Deployer;

task('deploy:cache:warmup', function () {
    runExtended('{{bin/php}} {{bin/console}} cache:warmup {{console_options}}');
})->desc('Warm up symfony cache');
