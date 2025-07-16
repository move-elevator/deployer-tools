<?php

namespace Deployer;

const VERSION_TYPE_BUILD = 'build';
const VERSION_TYPE_RELEASE = 'release';

task('dev:release:start_new_release', function () {
    $version = get('new_version');

    /* new release version */
    setVersion($version . '-RC');

    /* new git branch */
    info("create new branch: \"release-$version\"");
    runLocally("git checkout -b \"release-$version\"");

    commitVersion($version . '-RC');
})
    ->desc('Start a new release');

function setVersion(string $version): void {
    info("set new version: \"{$version}\"");
    runLocally("composer config version {$version} --working-dir " . get('composer_path_app'));
    runLocally("composer update nothing -q " . get('composer_app_release_options') . " --working-dir " . get('composer_path_app'));
    runLocally("composer validate --working-dir " . get('composer_path_app'));
}

function commitVersion(string $version, string $type = VERSION_TYPE_BUILD): void {
    info("commit new version: \"{$version}\"");
    commit(get('dev_git_message_new_version_' . $type) . " " . $version, [get('composer_path_app') . "/composer.json", get('composer_path_app') . "/composer.lock"]);
}
