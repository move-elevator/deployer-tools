<?php

namespace Deployer;

// Cache Directory Tagging Specification (https://bford.info/cachedir/), honored by
// Mittwald's hosting backups as well as tools like rsync/borg/restic --exclude-caches.
const CACHEDIR_TAG_SIGNATURE = 'Signature: 8a477f597d28d172789f06886806bc55';

task('backup:exclude_cache', function () {
    foreach (get('backup_exclude_dirs') as $dir) {
        $dir = trim($dir, '/');

        if ('' === $dir || in_array('..', explode('/', $dir), true) || in_array('.', explode('/', $dir), true)) {
            warning("Skipping invalid backup_exclude_dirs entry: \"$dir\"");
            continue;
        }

        if (!test("[ -d '{{ release_path }}/$dir' ]")) {
            continue;
        }

        run("echo '" . CACHEDIR_TAG_SIGNATURE . "' > '{{ release_path }}/$dir/CACHEDIR.TAG'");
        debug("Tagged {{ release_path }}/$dir as excluded from backups (CACHEDIR.TAG)");
    }
})
    ->desc('Tag regenerable cache directories to exclude them from hosting backups')
;

before('deploy:symlink', 'backup:exclude_cache');
