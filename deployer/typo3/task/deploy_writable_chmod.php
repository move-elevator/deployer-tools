<?php

namespace Deployer;

/**
 * Adjusts file and directory permissions for TYPO3 deployments.
 *
 * Extends the shared permission logic with TYPO3-specific handling
 * for the CLI binary (vendor/bin/typo3 and vendor/bin/typo3cms).
 */
task('deploy:writable:chmod', function () {
    applyDeployPermissions();

    // Make TYPO3 CLI binary executable (supports both legacy and current binary names)
    foreach (['vendor/bin/typo3', 'vendor/bin/typo3cms'] as $binary) {
        if (test("[ -f {{ release_path }}/$binary ]")) {
            runExtended("chmod 0755 {{ release_path }}/$binary");
        }
    }
})->desc('Adjust file and directory permissions for TYPO3 release');
