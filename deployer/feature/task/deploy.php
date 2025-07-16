<?php

namespace Deployer;

/**
 * Default extensions for deploy tasks
 */

before('rollback', 'feature:init');
before('deploy:unlock', 'feature:init');
before('feature:sync', 'feature:init');
before('deploy:setup', 'feature:setup');
before('feature:notify', 'feature:init');
before('deploy:success', 'feature:notify');
after('deploy:clear_paths', 'feature:sync');
after('deploy:symlink', 'feature:urlshortener');
before('feature:sync', 'feature:init');
before('debug:db', 'feature:init');
before('debug:ssh', 'feature:init');
before('debug:log:app', 'feature:init');
