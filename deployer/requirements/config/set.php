<?php

declare(strict_types=1);

namespace Deployer;

// Accumulator
set('requirements_rows', []);

// Enable/disable per check category
set('requirements_check_locales_enabled', true);
set('requirements_check_packages_enabled', true);
set('requirements_check_php_extensions_enabled', true);
set('requirements_check_php_settings_enabled', true);
set('requirements_check_database_enabled', true);
set('requirements_check_user_enabled', true);
set('requirements_check_env_enabled', true);

// Locales
set('requirements_locales', ['de_DE.utf8', 'en_US.utf8']);

// System packages (display name => CLI command)
set('requirements_packages', [
    'rsync' => 'rsync',
    'curl' => 'curl',
    'graphicsmagick' => 'gm',
    'ghostscript' => 'gs',
    'git' => 'git',
    'gzip' => 'gzip',
    'mariadb-client' => 'mysql',
    'unzip' => 'unzip',
    'patch' => 'patch',
    'exiftool' => 'exiftool',
    'composer' => 'composer',
]);

// PHP extensions (framework-aware)
set('requirements_php_extensions', function (): array {
    if (has('app_type') && get('app_type') === 'typo3') {
        return [
            'curl',
            'gd',
            'mbstring',
            'soap',
            'xml',
            'zip',
            'intl',
            'apcu',
            'pdo',
            'pdo_mysql',
            'json',
            'fileinfo',
            'openssl',
        ];
    }

    return [
        'curl',
        'gd',
        'mbstring',
        'xml',
        'zip',
        'intl',
        'pdo',
        'pdo_mysql',
    ];
});

// PHP settings
set('requirements_php_settings', [
    'max_execution_time' => '240',
    'memory_limit' => '512M',
    'max_input_vars' => '1500',
    'date.timezone' => 'Europe/Berlin',
    'post_max_size' => '31M',
    'upload_max_filesize' => '30M',
    'opcache.memory_consumption' => '256',
]);

// Database
set('requirements_db_min_version', '10.2.7');

// User / permissions
set('requirements_user_group', 'www-data');
set('requirements_deploy_path_permissions', '2770');

// Env file
set('requirements_env_file', '.env');
set('requirements_env_vars', function (): array {
    if (has('app_type') && get('app_type') === 'typo3') {
        return [
            'TYPO3_CONF_VARS__DB__Connections__Default__host',
            'TYPO3_CONF_VARS__DB__Connections__Default__dbname',
            'TYPO3_CONF_VARS__DB__Connections__Default__user',
            'TYPO3_CONF_VARS__DB__Connections__Default__password',
        ];
    }

    return [];
});
