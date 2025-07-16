<?php

namespace Deployer;

task('deploy:cache:clear', function () {
    runExtended('{{bin/php}} {{bin/console}} cache:clear {{console_options}} --no-warmup');
})->desc('Clear symfony cache');
