<?php

namespace Deployer;

use Symfony\Component\Console\Helper\Table;

task('feature:list', function () {

    checkVerbosity();

    debug('Collection statistic information');

    $directoryStats = listFeatureInstances();
    $table = [];

    // build up statistic table for output
    foreach ($directoryStats as $stat) {
        // only regard directories
        if (strtolower($stat[0]) !== 'directory' && strtolower($stat[0]) !== 'verzeichnis') continue;

        $publicUrl = get('public_urls')[0] . $stat[2];
        if (!isUrlShortener()) {
            $publicUrl .= '/current/' . get('web_path');
        }
        $table[] = [
            $stat[2],
            date("d.m.Y H:i" , (int)$stat[1]),
            $publicUrl
        ];
    }

    /**
     * +----------------+------------------ stage ------------------------------------+
     * | Feature Branch | Modification Date | Public URL                               |
     * +----------------+-------------------+-----------------------------------------+
     * | feature-start  | 20.01.2023 15:07  | https://test.example.local/feature-start   |
     * | test           | 20.01.2023 17:45  | https://test.example.local/test            |
     * +----------------+-------------------+-----------------------------------------+
     */
    (new Table(output()))
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(["Feature Branch", 'Modification Date', 'Public URL'])
        ->setRows($table)
        ->render();

})
    ->select('type=feature-branch-deployment')
    ->desc('List all available feature branch instances')
;

/**
 * Get a list with statistic information of all available feature branches
 *
 * @throws \Deployer\Exception\Exception
 * @throws \Deployer\Exception\RunException
 * @throws \Deployer\Exception\TimeoutException
 */
function listFeatureInstances(): array {
    if (isUrlShortener()) {
        $path = get('deploy_path') . '/' . get('feature_url_shortener_path');
    } else {
        $path = get('deploy_path');
    }

    // fetch statistic information about feature branch directories
    //  > stat -c '%F %Y %n ' *
    //  > directory 1674227229 feature-start
    $directoryStats = runExtended("cd $path && stat -c '%F %Y %n ' *", real_time_output: false);
    $directoryStats = explode("\n", $directoryStats);
    return array_map(static function($item){ return explode(" ", $item);}, $directoryStats);
}
