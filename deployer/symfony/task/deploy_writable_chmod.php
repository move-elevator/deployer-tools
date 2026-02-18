<?php

namespace Deployer;

/**
 * Adjusts file and directory permissions for Symfony deployments.
 */
task('deploy:writable:chmod', function () {
    applyDeployPermissions();
})->desc('Adjust file and directory permissions for Symfony release');
