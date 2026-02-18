<?php

declare(strict_types=1);

namespace Deployer;

use Deployer\Exception\RunException;

task('requirements:check:locales', function (): void {
    if (!get('requirements_check_locales_enabled')) {
        return;
    }

    try {
        $availableLocales = strtolower(run('locale -a 2>/dev/null'));
    } catch (RunException) {
        addRequirementRow('Locales', REQUIREMENT_SKIP, 'Could not retrieve locale list');

        return;
    }

    foreach (get('requirements_locales') as $locale) {
        $normalizedLocale = strtolower($locale);
        $altLocale = str_replace('.utf8', '.utf-8', $normalizedLocale);

        if (str_contains($availableLocales, $normalizedLocale)
            || str_contains($availableLocales, $altLocale)) {
            addRequirementRow("Locale: $locale", REQUIREMENT_OK, 'Available');
        } else {
            addRequirementRow("Locale: $locale", REQUIREMENT_FAIL, 'Not available');
        }
    }
})->hidden();
