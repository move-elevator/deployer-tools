<?php

namespace Deployer;

require_once('feature_init.php');

task('feature:index', function () {

    checkVerbosity();

    $path = get('deploy_path') . '/index.php';
    $upload = true;
    if (test("[[ -f $path ]]")) {
        $upload = askConfirmation("A index.php file already exists: " . $path . " \n Do you really want to override the file?", true);
    }
    if ($upload) {
        renderIndexTemplate();
    }
})->desc('Provide an index.php file for a simple feature branch overview on the remote system');


/**
 * Render the remote index.php file as feature instance overview
 *
 * @throws \Deployer\Exception\RunException
 * @throws \Deployer\Exception\Exception
 * @throws \Deployer\Exception\TimeoutException
 */
function renderIndexTemplate(): void
{
    $arguments = [
        'DEPLOYER_CONFIG_FEATURE_INDEX_TITLE' => get('feature_index_title'),
        'DEPLOYER_CONFIG_FEATURE_INDEX_APP_PATH' => get('feature_index_app_path'),
        'DEPLOYER_CONFIG_FEATURE_INDEX_APP_TYPE' => get('feature_index_app_type'),
        'DEPLOYER_CONFIG_FEATURE_INDEX_JIRA_BROWSE' => get('feature_index_jira_browse'),
        'DEPLOYER_CONFIG_FEATURE_INDEX_JIRA_API' => get('feature_index_jira_api'),
        'DEPLOYER_CONFIG_FEATURE_INDEX_JIRA_AUTH' => get('feature_index_jira_auth'),
        'DEPLOYER_CONFIG_FEATURE_INDEX_GIT_BRANCH' => get('feature_index_git_branch'),
    ];

    debug('Preparing index template');

    // Add additional links
    if (has('feature_index_additional_links')) {
        $preparedLink = [];
        foreach (get('feature_index_additional_links') as $title => $link) {
            $preparedLink[] = "$title|$link";
        }
        $arguments['DEPLOYER_CONFIG_FEATURE_INDEX_ADDITIONAL_LINKS'] = implode(",", $preparedLink);
    }

    // Upload index files
    $featureDirectoryPath = get('deploy_path') . '/' . get('feature_directory_path');
    upload(__DIR__ . '/../dist/index' ,$featureDirectoryPath);
    upload(__DIR__ . '/../dist/index.php' ,$featureDirectoryPath . 'index.php');
    runExtended("cd " . get('deploy_path') . " && ln -sf " . $featureDirectoryPath . "index.php index.php");
    uploadTemplate(__DIR__ . '/../dist/index.json.dist', '/' . get('feature_directory_path') . 'index.json', $arguments);
    // ToDo: fix permissions
    runExtended("cd {{deploy_path}} && chmod 644 {{feature_directory_path}}index.* && chmod 775 {{feature_directory_path}}index/ && chmod -R 755 {{feature_directory_path}}index/assets && chmod -R 755 {{feature_directory_path}}index/src && chmod 755 {{feature_directory_path}}index/autoload.php");

}
