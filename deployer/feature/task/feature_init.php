<?php

namespace Deployer;

require_once('url_shortener.php');
require_once('feature_list.php');
require_once(__DIR__ . '/../../functions.php');

task('feature:init', function () {
    checkVerbosity();
    // extend deploy path / public url
    initFeature();
})
    ->select('type=feature-branch-deployment')
    ->once()
    ->desc('Initialize a feature branch');


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
    $feature = $feature ?: (!is_null(input()->getOption('feature')) ? input()->getOption('feature') : askChoice('Please select a feature branch', array_map(function ($array) {
        return $array[2];
    }, listFeatureInstances())));
    set('feature', $feature);

    if (isUrlShortener()) {
        // initialize the url shortener function
        initUrlShortener($feature);
        set('npm_variables', 'FEATURE_BRANCH_PATH_PUBLIC=/' . $feature . ' ');
    } else {
        // extend deploy path with feature directory
        set('deploy_path', get('deploy_path') . '/' . $feature);

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
