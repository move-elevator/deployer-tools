<?php

declare(strict_types=1);

namespace Deployer;

// Accumulator
set('requirements_rows', []);

// Enable/disable per check category
set('requirements_check_locales_enabled', true);
set('requirements_check_packages_enabled', true);
set('requirements_check_image_processing_enabled', true);
set('requirements_check_php_extensions_enabled', true);
set('requirements_check_php_settings_enabled', true);
set('requirements_check_database_enabled', true);
set('requirements_check_user_enabled', true);
set('requirements_check_env_enabled', true);
set('requirements_check_eol_enabled', true);
set('requirements_check_database_grants_enabled', true);
set('requirements_check_health_enabled', true);

// Locales
set('requirements_locales', ['de_DE.utf8', 'en_US.utf8']);

// System packages (display name => CLI command)
set('requirements_packages', [
    'rsync' => 'rsync',
    'curl' => 'curl',
    'ghostscript' => 'gs',
    'git' => 'git',
    'gzip' => 'gzip',
    'mariadb-client' => 'mysql',
    'unzip' => 'unzip',
    'patch' => 'patch',
    'exiftool' => 'exiftool',
    'composer' => 'composer',
]);

// PHP minimum version (framework-aware)
set('requirements_php_min_version', function (): string {
    if (has('app_type') && get('app_type') === 'typo3') {
        return '8.2.0';
    }

    return '8.1.0';
});

// PHP extensions (framework-aware)
set('requirements_php_extensions', function (): array {
    if (has('app_type') && get('app_type') === 'typo3') {
        return [
            'pdo',
            'session',
            'xml',
            'filter',
            'tokenizer',
            'mbstring',
            'intl',
            'pdo_mysql',
            'fileinfo',
            'gd',
            'zip',
            'openssl',
            'curl',
            'apcu',
        ];
    }

    return [
        'pdo',
        'session',
        'xml',
        'filter',
        'tokenizer',
        'mbstring',
        'intl',
        'pdo_mysql',
        'curl',
        'gd',
        'zip',
    ];
});

// PHP settings
set('requirements_php_settings', [
    'max_execution_time' => '240',
    'memory_limit' => '512M',
    'max_input_vars' => '1500',
    'pcre.jit' => '1',
    'date.timezone' => 'Europe/Berlin',
    'post_max_size' => '31M',
    'upload_max_filesize' => '30M',
    'opcache.memory_consumption' => '256',
]);

// Database
set('requirements_mariadb_min_version', '10.4.3');
set('requirements_mysql_min_version', '8.0.17');

// Image processing
set('requirements_graphicsmagick_min_version', '1.3');
set('requirements_imagemagick_min_version', '6.0');

// Composer
set('requirements_composer_min_version', '2.1.0');

// End-of-life check (endoflife.date API)
set('requirements_eol_warn_months', 6);
set('requirements_eol_api_timeout', 5);

// Health check
set('requirements_health_url', 'http://localhost');

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
