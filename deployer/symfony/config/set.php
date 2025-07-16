<?php

namespace Deployer;

/**
 * Default configuration for symfony application
 */
set('app_type', 'symfony');
set('web_path', 'public/');
set('debug_log_path', 'var/log');
set('debug_log_regex_pattern', '/^\[(.*?)\]\s(\w+)\s:\s(.+?)\s{.*?}$/');

set('default_timeout', 900);

set('keep_releases', 2);

set('shared_dirs', [
        'var/log'
    ]
);

set('shared_files', [
        '.env.local'
    ]
);

set('writable_dirs', [
    'var',
    'public/upload'
]);

set('writable_mode', 'chmod');
set('writable_chmod_mode', '0770'); // todo maybe change to '2770'
set('writable_chmod_recursive', false);

set('bin/console', function () {
    $activePath = get('deploy_path') . '/' . (test('[ -L {{deploy_path}}/release ]') ? 'release' : 'current');
    return parse("$activePath/bin/console");
});

set('console_options', function () {
    return '--no-interaction';
});

set('composer_options', function () {
    return '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader --no-scripts';
});

set('run_real_time_output', true);

set('clear_paths', [
    '.git',
    '.gitignore',
    '.gitattributes',
]);

// Look on https://github.com/sourcebroker/deployer-extended#buffer-start for docs
set('buffer_config', function () {
    return [
        'index.php' => [
            'entrypoint_filename' => get('web_path') . 'index.php',
        ],
    ];
});

// Look https://github.com/sourcebroker/deployer-extended-media for docs
set('media',
    [
        'filter' => [
            '+ /public/',
            '+ /public/media/',
            '+ /public/media/**',
            '+ /public/upload/',
            '+ /public/upload/**',
            '- *'
        ]
    ]);

set('feature_index_app_type', 'symfony');

// Prod deployment, add backup
task('database:backup')->select('prod');
before('deploy:database:update', 'database:backup');
set('sync_database_backup_config', __DIR__ . '/.deployment/db-sync-tool/backup-prod.yaml');
