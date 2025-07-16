<?php

namespace Deployer;

task('feature:sync:standalone', [
    'feature:sync',
    'deploy:database:update',
    'deploy:cache:clear'
]);
