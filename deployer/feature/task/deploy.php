<?php

namespace Deployer;

/**
 * Default extensions for deploy tasks
 */

/*
 * deploy:info resolves {{release_name}} and deployer caches it for the rest of the
 * run. Without this hook the counter is read from the base path and the cached name
 * then collides in deploy:release, so the feature instance has to exist by now.
 */
before('deploy:info', 'feature:init');

before('rollback', 'feature:select');
before('deploy:unlock', 'feature:init');
before('feature:sync', 'feature:init');
before('deploy:setup', 'feature:setup');
before('feature:notify', 'feature:init');
before('deploy:success', 'feature:notify');
after('deploy:clear_paths', 'feature:sync');
before('feature:sync', 'feature:wait_for_database');
before('deploy:database:update', 'feature:wait_for_database');
after('deploy:symlink', 'feature:urlshortener');
before('debug:db', 'feature:select');
before('debug:ssh', 'feature:select');
before('debug:log:app', 'feature:select');
