<?php

namespace Deployer;

# rollback
task('after_rollback', [
    'deploy:cache:clear',
    'deploy:cache:warmup'
]);
after('rollback', 'after_rollback');
