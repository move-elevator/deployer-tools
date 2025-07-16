<?php

namespace Deployer;

task('deploy:ckeditor:install', function () {
    if (commandSupportSubcommand("{{bin/php}} {{bin/console}}", "ckeditor:install")) {
        runExtended("{{bin/php}} {{bin/console}} ckeditor:install --clear=drop {{console_options}}");
    } else {
        debug('ckeditor not installed, skipping command');
    }
})->desc('Install symfony bundle ckeditor');
