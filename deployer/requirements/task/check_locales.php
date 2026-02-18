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

    $localeLines = array_map('trim', explode("\n", $availableLocales));

    foreach (get('requirements_locales') as $locale) {
        $normalizedLocale = strtolower($locale);
        $altLocale = str_replace('.utf8', '.utf-8', $normalizedLocale);

        $found = in_array($normalizedLocale, $localeLines, true)
            || in_array($altLocale, $localeLines, true);

        if ($found) {
            addRequirementRow("Locale: $locale", REQUIREMENT_OK, 'Available');
        } else {
            addRequirementRow("Locale: $locale", REQUIREMENT_FAIL, 'Not available');
        }
    }
})->hidden();
