<?php

declare(strict_types=1);

namespace Deployer;

task('requirements:list', function (): void {
    $appType = has('app_type') ? get('app_type') : 'default';
    $label = strtoupper($appType);

    writeln('');
    writeln("<fg=cyan;options=bold>Server Requirements ($label)</>");
    writeln(str_repeat('=', 50));

    // PHP
    if (get('requirements_check_php_settings_enabled') || get('requirements_check_php_extensions_enabled')) {
        writeln('');
        writeln('<fg=yellow;options=bold>PHP</>');
        writeln(sprintf('  Version:      >= %s', get('requirements_php_min_version')));

        if (get('requirements_check_php_extensions_enabled')) {
            writeln(sprintf('  Extensions:   %s', implode(', ', get('requirements_php_extensions'))));
        }

        if (get('requirements_check_php_settings_enabled')) {
            writeln('  Settings:');

            foreach (get('requirements_php_settings') as $setting => $value) {
                $isComparison = in_array($setting, REQUIREMENT_BYTE_SETTINGS, true)
                    || in_array($setting, REQUIREMENT_NUMERIC_SETTINGS, true);
                $operator = $isComparison ? '>=' : '=';
                writeln(sprintf('    %-30s %s %s', $setting, $operator, $value));
            }
        }
    }

    // Database
    if (get('requirements_check_database_enabled')) {
        writeln('');
        writeln('<fg=yellow;options=bold>Database</>');
        writeln(sprintf('  MariaDB:      >= %s', get('requirements_mariadb_min_version')));
        writeln(sprintf('  MySQL:        >= %s', get('requirements_mysql_min_version')));
    }

    // Image Processing
    if (get('requirements_check_image_processing_enabled')) {
        writeln('');
        writeln('<fg=yellow;options=bold>Image Processing</>');
        writeln(sprintf(
            '  GraphicsMagick >= %s (recommended) or ImageMagick >= %s',
            get('requirements_graphicsmagick_min_version'),
            get('requirements_imagemagick_min_version')
        ));
    }

    // System Packages
    if (get('requirements_check_packages_enabled')) {
        writeln('');
        writeln('<fg=yellow;options=bold>System Packages</>');

        $packages = get('requirements_packages');
        $packageNames = [];

        foreach ($packages as $displayName => $command) {
            $name = is_int($displayName) ? $command : $displayName;

            if ($command === 'composer') {
                $name .= sprintf(' (>= %s)', get('requirements_composer_min_version'));
            }

            $packageNames[] = $name;
        }

        writeln('  ' . implode(', ', $packageNames));
    }

    // Locales
    if (get('requirements_check_locales_enabled')) {
        writeln('');
        writeln('<fg=yellow;options=bold>Locales</>');
        writeln('  ' . implode(', ', get('requirements_locales')));
    }

    // User & Permissions
    if (get('requirements_check_user_enabled')) {
        writeln('');
        writeln('<fg=yellow;options=bold>User & Permissions</>');
        writeln(sprintf('  Group:        %s', get('requirements_user_group')));
        writeln(sprintf('  Permissions:  %s (deploy path)', get('requirements_deploy_path_permissions')));
    }

    // End-of-life checks
    if (get('requirements_check_eol_enabled')) {
        writeln('');
        writeln('<fg=yellow;options=bold>End-of-Life Checks</>');
        writeln('  PHP and database versions checked against endoflife.date API');
        writeln(sprintf('  Warning threshold: %d months before EOL', (int) get('requirements_eol_warn_months')));
    }

    // Environment Variables
    if (get('requirements_check_env_enabled')) {
        $envVars = get('requirements_env_vars');

        if (!empty($envVars)) {
            writeln('');
            writeln('<fg=yellow;options=bold>Environment Variables</>');
            writeln(sprintf('  File: {{deploy_path}}/shared/%s', get('requirements_env_file')));

            foreach ($envVars as $var) {
                writeln("  - $var");
            }
        }
    }

    writeln('');
})->desc('List server requirements');
