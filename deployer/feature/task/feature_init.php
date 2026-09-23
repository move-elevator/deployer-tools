<?php

namespace Deployer;

use MoveElevator\DeployerTools\Utility\FeatureUtility;

require_once('url_shortener.php');
require_once('feature_list.php');
require_once(__DIR__ . '/../../functions.php');

task('feature:init', function () {
    checkVerbosity();
    if (!featureRequested()) {
        debug('No feature given, staying on the base instance');
        return;
    }
    // extend deploy path / public url
    initFeature();
})
    ->select('type=feature-branch-deployment')
    ->once()
    ->desc('Initialize a feature branch');

task('feature:select', function () {
    checkVerbosity();
    // extend deploy path / public url, asking for the feature if none was given
    initFeature();
})
    ->select('type=feature-branch-deployment')
    ->once()
    ->desc('Select a feature branch and initialize it');


/**
 * Initialize a feature branch
 * (!: needed for all deployer tasks considering a feature branch)
 *
 * @param ?string $feature
 * @return ?string
 * @throws \Deployer\Exception\Exception
 * @throws \Deployer\Exception\RunException
 * @throws \Deployer\Exception\TimeoutException
 * @throws \Exception
 */
function initFeature(?string $feature = null): ?string
{
    debug('Initializing feature instance');
    set('deploy_base_path', get('deploy_path'));
    // check if feature was already initialized
    if (has('feature_initialized') && get('feature_initialized')) return get('feature');;

    prepareDeployerConfiguration();
    // use feature variable or feature input option or ask for feature branch
    // (?: would discard a caller-provided "0", which is a valid instance name)
    if (null === $feature || '' === trim($feature)) {
        $feature = featureRequested() ? input()->getOption('feature') : askChoice('Please select a feature branch', array_map(function ($array) {
            return $array[2];
        }, listFeatureInstances()));
    }
    // branch names may contain path separators ("feature/ABC-12"), the instance name must stay flat
    $normalizedFeature = getFeatureName($feature);

    if (isFeatureSubdomainMode()) {
        guardAgainstLegacyFeatureDirectory($feature, $normalizedFeature);
    }

    $feature = $normalizedFeature;
    set('feature', $feature);

    if (isUrlShortener()) {
        // initialize the url shortener function
        initUrlShortener($feature);
    } else {
        // extend deploy path with feature directory
        set('deploy_path', get('deploy_path') . '/' . $feature);
    }

    if (isFeatureSubdomainMode()) {
        // the instance gets its own subdomain instead of a subpath (feature_url_pattern);
        // the app is served from its root, so npm_variables keeps its blank default instead
        // of a FEATURE_BRANCH_PATH_PUBLIC path prefix
        set('public_urls', [getFeatureSubdomainUrl($feature)]);
    } elseif (isUrlShortener()) {
        set('npm_variables', 'FEATURE_BRANCH_PATH_PUBLIC=/' . $feature . ' ');
    } else {
        // extend public url path with feature path and specific web path
        $publicUrls = [];
        foreach (get('public_urls') as $publicUrl) {
            $publicUrls[] = $publicUrl . $feature . '/current/' . get('web_path');
        }
        set('public_urls', $publicUrls);
        set('npm_variables', 'FEATURE_BRANCH_PATH_PUBLIC=/' . $feature . '/current/' . get('web_path') . ' ');
    }
    set('feature_initialized', true);
    return $feature;
}

/**
 * Fail closed instead of silently addressing a different instance: switching a host to
 * feature_url_pattern changes the hostname-safe instance name for any branch that used
 * uppercase letters, dots or underscores (e.g. "TEST-01" becomes "test-01"). Without this
 * guard, an existing directory under the pre-switch name would be orphaned while a second,
 * empty instance gets created next to it under the new name.
 *
 * @param ?string $rawFeature the identifier as given (branch name or --feature value)
 * @throws \RuntimeException if a legacy (pre-subdomain-mode) instance directory still
 *                            exists under the raw identifier's path-mode name
 */
function guardAgainstLegacyFeatureDirectory(?string $rawFeature, string $hostnameSafeFeature): void
{
    $legacyFeature = FeatureUtility::normalize($rawFeature);

    if ('' === $legacyFeature || $legacyFeature === $hostnameSafeFeature) {
        return;
    }

    $legacyPath = isUrlShortener()
        ? get('deploy_base_path') . '/' . get('feature_url_shortener_path') . $legacyFeature
        : get('deploy_base_path') . '/' . $legacyFeature;

    if (test("[[ -d $legacyPath ]]")) {
        throw new \RuntimeException(
            "A legacy feature instance directory \"$legacyPath\" for \"$legacyFeature\" still exists from before feature_url_pattern was enabled. " .
            "Remove it first against the previous configuration (e.g. \"feature:stop\" or \"feature:cleanup\"), then retry - it will be initialized as \"$hostnameSafeFeature\"."
        );
    }
}
