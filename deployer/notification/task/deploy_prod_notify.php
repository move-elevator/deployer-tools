<?php

namespace Deployer;

require_once('ms_teams.php');

task('deploy:prod:notify', function () {

    set('deploy_target', (has('labels') && get('labels')['name']) ? get('labels')['name'] : get('alias'));
    set('public_url', get('public_urls')[0]);
    set('deploy_version', getAppVersion());

    sendMessage(get('deploy_prod_notify_text'), get('deploy_prod_notify_color'));
})->desc('Send deployment notification');


/**
 * Get the app version from composer.json file
 *
 * @return mixed|string
 */
function getAppVersion() {
    $composerFile = file_get_contents(get('app_path') . '/composer.json');
    if ($composerFile) {
        $composerArray = \json_decode($composerFile, true);
        if ($composerArray) {
            if (array_key_exists('version', $composerArray)) {
                return $composerArray['version'];
            }
        }
    }
    return '?';
}
