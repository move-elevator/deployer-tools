<?php

namespace Deployer;

task('dev:release:finish', function () {
    $version = getNewVersion();
    info("checkout branch: " . get('dev_default_branch'));
    runLocally('git checkout ' . get('dev_default_branch'));
    info("merge branch: \"release-$version\"");
    runLocally("git merge \"release-$version\"");

    setVersion($version);
    commitVersion($version, VERSION_TYPE_RELEASE);
    info("tag version: \"v{$version}\"");
    runLocally("git tag -a \"v{$version}\" -m \"{$version}\"");

    info("🚀 Successfully finished release $version");
    warning("⚠️ Please check your git log and verify all automated git commits before pushing them!");
    info("ℹ️ Don't forget to \"git push\"");
})
    ->desc('Merge release branch into default branch and release new version');
