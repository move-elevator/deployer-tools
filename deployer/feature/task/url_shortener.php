<?php

namespace Deployer;

tasK('feature:urlshortener', function () {

    if (!input()->getOption('feature')) return;
    if (!isUrlShortener()) return;

    $symlinkDir = get('deploy_path') . "/current/" . get('web_path');

    runExtended("cd " . get('deploy_path_url_shortener') . " && ln -sf $symlinkDir " . get('feature'));
})
    ->select('type=feature-branch-deployment')
    ->desc('Add the feature branch symlink for the shortened url')
    ->once()
;


/**
 * Check if the url shortener function is activated
 *
 * @return bool
 */
function isUrlShortener(): bool
{
    return (has('feature_url_shortener') && get('feature_url_shortener'));
}

/**
 * Initialize the url shortener function and adjust deploy_path & public_urls
 * @param ?string $feature
 * @return void
 * @throws \Deployer\Exception\Exception
 */
function initUrlShortener(?string $feature = null): void
{
    if (!isUrlShortener()) return;

    debug('Adjust host configuration because of url shortener function');
    set('deploy_path_url_shortener', get('deploy_path'));
    set('deploy_path', get('deploy_path') . '/' . get('feature_url_shortener_path') . $feature);

    $publicUrls = [];
    foreach (get('public_urls') as $publicUrl) {
        $publicUrls[] = $publicUrl . $feature;
    }
    set('public_urls', $publicUrls);
}

/**
 * @param $feature
 * @return string
 */
function getUrlShortenerPath($feature): string
{
    return get('deploy_path_url_shortener') . "/$feature";
}

/**
 * Remove the url shortener symlink to the feature branch instance
 *
 * @param $feature
 * @return void
 * @throws \Deployer\Exception\Exception
 * @throws \Deployer\Exception\RunException
 * @throws \Deployer\Exception\TimeoutException
 */
function removeUrlShortenerPath($feature): void
{
    runExtended("rm -f " . getUrlShortenerPath($feature));
}
