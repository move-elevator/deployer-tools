<?php

namespace Deployer;

task('deploy', [

    // Standard deployer task.
    'deploy:info',

    // Standard deployer task.
    'deploy:setup',

    // Read more on https://github.com/sourcebroker/deployer-extended#deploy-check-lock
    'deploy:check_lock',
    // Standard deployer task.
    'deploy:lock',

    // Standard deployer task.
    'deploy:release',

    // Transfer application to remote
    'rsync',

    // Standard deployer task.
    'deploy:shared',

    // Standard deployer task.
    'deploy:writable',

    // Adjust file and directory permissions (SGID, CLI binary, shared dirs).
    'deploy:writable:chmod',

    // Standard deployer task.
    'deploy:clear_paths',

    // custom database update
    'deploy:database:update',

    // custom task to do additional setup for framework or cms
    'deploy:additional_setup',

    // custom clear and warmup system specific caches
    'deploy:cache:clear_and_warmup',

    // Standard deployer task.
    'deploy:symlink',

    // Clear php cli cache.
    // Read more on https://github.com/sourcebroker/deployer-extended#cache-clear-php-cli
    'cache:clear_php_cli',

    // Clear frontend http cache.
    // Read more on https://github.com/sourcebroker/deployer-extended#cache-clear-php-http
    'cache:clear_php_http',

    // custom warmup task
    'deploy:warmup_frontend',

    // Standard deployer task.
    'deploy:unlock',

    // Standard deployer task.
    'deploy:cleanup',

    // Standard deployer task.
    'deploy:success',
])->desc('Deploy your project');;

after('deploy:failed', 'deploy:unlock');
