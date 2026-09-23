<?php

namespace Deployer;

use Symfony\Component\Console\Helper\Table;

require_once('feature_init.php');
require_once('feature_list.php');
require_once('feature_stop.php');

task('feature:cleanup', function () {

    $gitBranches = listRemoteGitBranches();
    // stat's "%F" contains a space for files and symlinks ("regular file"), which would shift the
    // name out of index 2, so only directories are considered feature instances
    $remoteInstances = array_values(array_map(
        static fn (array $item) => $item[2],
        array_filter(listFeatureInstances(), static fn (array $item) => $item[0] === 'directory'),
    ));

    $comparison = [];
    foreach ($gitBranches as $branch) {
        $featureName = getFeatureName($branch);
        if (in_array($featureName, $remoteInstances, true)) {
            // feature instance is in sync
            $comparison[] = [
                "<fg=green>$featureName</>",
                "<fg=green>$featureName</>"
            ];
        } else {
            // git branch has no feature instance
            $comparison[] = [
                "<fg=yellow>$featureName</>",
                ""
            ];
        }
        if (($index = array_search($featureName, $remoteInstances, false)) !== false) {
            unset($remoteInstances[$index]);
        }
    }
    foreach ($remoteInstances as $instance) {
        // git branch is gone, feature instance should be deleted
        $comparison[] = [
            "",
            "<fg=red>$instance</>"
        ];
    }
    (new Table(output()))
        ->setHeaderTitle(currentHost()->getAlias())
        ->setHeaders(["Remote Git Branch", 'Remote Feature Instance'])
        ->setRows($comparison)
        ->render();


    if (!empty($remoteInstances)) {
        $force = (bool)input()->getOption('force-cleanup');
        $delete = $force || askConfirmation("Do you want to cleanup all remote feature instances which haven't an according git branch anymore? (marked as <fg=red>red</>)", false);

        if ($delete) {
            $deployPath = get('deploy_path');

            foreach ($remoteInstances as $instance) {
                // set variable to false in order to force a reinitialize before deletion
                set('feature_initialized', false);
                // reset deploy_path
                set('deploy_path', $deployPath);

                initFeature($instance);
                deleteFeature($instance, !$force);
            }
        }
    } else {
        info("Everything seems in sync <fg=green>✔</>");
    }

})
    ->select('type=feature-branch-deployment')
    ->desc('Compare remote git branches with remote feature instances and provides a cleanup for all untracked feature instances on the remote server')
;

/**
 * Queries the remote directly instead of reading local remote-tracking branches, which are
 * incomplete in CI checkouts (detached HEAD, single-ref fetch) and would mark every instance
 * as untracked.
 */
function listRemoteGitBranches(): array
{
    $output = runLocally('git ls-remote --heads origin');
    $branches = [];
    foreach (explode("\n", $output) as $line) {
        if (preg_match('#\srefs/heads/(.+)$#', trim($line), $matches)) {
            $branches[] = $matches[1];
        }
    }

    if (empty($branches)) {
        throw new \RuntimeException('No remote git branches found via "git ls-remote --heads origin", aborting cleanup to avoid deleting every feature instance.');
    }

    return $branches;
}
