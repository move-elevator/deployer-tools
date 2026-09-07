<?php

namespace Deployer;

/**
 * Release-local directories that are fully regenerated on every deploy (composer/npm install,
 * cache warmup) and therefore don't need to be part of a hosting backup.
 */
set('backup_exclude_dirs', [
    'vendor',
    'var/cache',
]);
