<?php

namespace Deployer;

task('dev:release:reset', function () {
    /* get new version */
    $version = getNewVersion();

    info("checkout branch: " . get('dev_default_branch'));
    runLocally('git checkout ' . get('dev_default_branch') . ' --force');
    runLocally('git reset');

    info("remove new branch: \"release-$version\"");
    runLocally("git branch -D \"release-$version\"");

    invoke('dev:release:tabula_rasa');

    info("🧽 Reset back to " . get('dev_default_branch'));
})
    ->desc('Delete a new unreleased release and reset to default branch');
