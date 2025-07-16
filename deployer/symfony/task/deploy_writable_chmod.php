<?php

namespace Deployer;

/**
 * This task will adjust the dir/file rights
 * ToDo: check deploy:writable to solve this?
 */
task('deploy:writable:chmod', function() {
    runExtended("cd {{ release_path }} && chmod 775 var var/cache ../../shared/var/log");
    runExtended("cd {{ release_path }} && find . -path \"./var\" -prune -o -type d -exec chmod 755 {} +");
    runExtended("cd {{ release_path }} && find . -type f -exec chmod 644 {} +");
    runExtended("cd {{ release_path }} && chmod -R 775 var/cache");
})->desc('Adjust file rights for release directory');
