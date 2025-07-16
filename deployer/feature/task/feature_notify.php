<?php

namespace Deployer;

require_once(__DIR__ . '/../../notification/task/ms_teams.php');


task('feature:notify', function () {

    if ((has('feature_setup') && !get('feature_setup')) || !input()->getOption('feature')) return;
    checkVerbosity();

    set('public_url', get('public_urls')[0]);
    sendMessage(get('feature_msteams_text'), get('feature_msteams_color'));

})
    ->select('type=feature-branch-deployment')
    ->desc('Notify about successful feature branch deployment')
;
