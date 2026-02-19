<?php

declare(strict_types=1);

namespace Deployer;

task('requirements:check', [
    'requirements:check:locales',
    'requirements:check:packages',
    'requirements:check:image_processing',
    'requirements:check:php_extensions',
    'requirements:check:php_settings',
    'requirements:check:database',
    'requirements:check:database_grants',
    'requirements:check:user',
    'requirements:check:env',
    'requirements:check:eol',
    'requirements:check:summary',
])->desc('Check server requirements');

task('requirements:check:summary', function (): void {
    renderRequirementsTable();
})->hidden();
