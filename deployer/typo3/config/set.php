<?php

namespace Deployer;

require 'recipe/typo3.php';

set('default_timeout', 900);
set('keep_releases', 2);

// TYPO3
set('app_type', 'typo3');
set('web_path', 'public/');
set('bin/typo3cms', './vendor/bin/typo3cms');
set('debug_log_path', 'var/log');
set('debug_log_regex_pattern', '/^(\w+,\s\d+\s\w+\s\d+\s\d+:\d+:\d+\s\+\d+)\s\[(\w+)\]\s(.+?):\s(.+)/');

set('shared_dirs', [
    '{{web_path}}fileadmin',
    '{{web_path}}uploads',
    'var/session',
    'var/log',
    'var/lock',
    'var/charset',
    'var/transient',
]);

set('shared_files', [
    '.env'
]);

set('writable_mode', 'chmod');
set('writable_chmod_mode', '2770');
set('writable_recursive', false);
set('writable_dirs',  [
    '{{web_path}}typo3conf',
    '{{web_path}}typo3temp',
    '{{web_path}}uploads',
    '{{web_path}}fileadmin',
    'var/session',
    'var/log',
    'var/lock',
    'var/charset',
    'var/transient',
]);

set('composer_options', '--verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader --no-scripts');

set('run_real_time_output', true);

// Look on https://github.com/sourcebroker/deployer-extended#buffer-start for docs
set('buffer_config', function () {
    return [
        'index.php' => [
            'entrypoint_filename' => get('web_path') . 'index.php',
        ],
        'typo3/index.php' => [
            'entrypoint_filename' => get('web_path') . 'typo3/index.php',
        ],
        'typo3/install.php' => [
            'entrypoint_filename' => get('web_path') . 'typo3/install.php',
        ]
    ];
});

/**
 * Rsync settings
 */
set('rsync_default_excludes', [
    '.Build',
    '.git',
    '.gitlab',
    '.ddev',
    '.deployer',
    '.idea',
    '.DS_Store',
    '.gitlab-ci.yml',
    '.npm',
    'package.json',
    'package-lock.json',
    'node_modules/',
    'var/session',
    'var/log',
    'var/lock',
    'var/charset',
    'var/transient',
    'public/fileadmin/',
    'public/typo3temp/',
]);

set('feature_index_app_type', 'typo3');

/**
 * Env Keys
 */
set('env_key_database_passwort', 'TYPO3_CONF_VARS__DB__Connections__Default__password');
set('env_key_database_name', 'TYPO3_CONF_VARS__DB__Connections__Default__dbname');
