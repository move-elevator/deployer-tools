<?php

namespace Deployer;

set('npm', function () {
    if (commandExist('npm')) {
        return which('npm');
    }
    return 'npm';
});

set('npm_variables','');
