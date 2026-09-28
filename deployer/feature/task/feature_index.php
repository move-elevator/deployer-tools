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
    $config = [
        'projectName' => get('feature_index_title'),
        'defaultApplicationPath' => get('feature_index_app_path'),
        'applicationType' => get('feature_index_app_type'),
        'jira' => [
            'browse' => get('feature_index_jira_browse'),
            'api' => get('feature_index_jira_api'),
            'auth' => get('feature_index_jira_auth'),
        ],
        'git' => [
            'branch' => get('feature_index_git_branch'),
        ],
        'additionalLinks' => has('feature_index_additional_links') ? get('feature_index_additional_links') : [],
        'featureUrlPattern' => isFeatureSubdomainMode() ? get('feature_url_pattern') : '',
    ];

    debug('Preparing index template');

    // the index lives in the web root: earlier versions stored the config (including the Jira
    // credentials) as plain index.json and cached raw Jira responses below index/var/, both readable
    // over HTTP, so remove them before uploading the PHP based replacements
    runExtended("cd {{deploy_path}} && rm -f {{feature_directory_path}}index.json");
    // the cache is written by the web server user, which the deploy user may lack permissions for
    if (!test("cd {{deploy_path}} && rm -rf {{feature_directory_path}}index/var")) {
        warning("Could not remove the legacy Jira cache {{deploy_path}}/{{feature_directory_path}}index/var, please remove it manually");
    }

    // Upload index files
    $featureDirectoryPath = get('deploy_path') . '/' . get('feature_directory_path');
    upload(__DIR__ . '/../dist/index' ,$featureDirectoryPath);
    upload(__DIR__ . '/../dist/index.php' ,$featureDirectoryPath . 'index.php');
    runExtended("cd " . get('deploy_path') . " && ln -sf " . $featureDirectoryPath . "index.php index.php");
    uploadIndexConfig($config, $featureDirectoryPath . 'index.config.php');
    // ToDo: fix permissions
    runExtended("cd {{deploy_path}} && chmod 644 {{feature_directory_path}}index.* && chmod 775 {{feature_directory_path}}index/ && chmod -R 755 {{feature_directory_path}}index/assets && chmod -R 755 {{feature_directory_path}}index/src && chmod 755 {{feature_directory_path}}index/autoload.php");

}

/**
 * Store the index config as PHP file, so the web server executes it instead of serving the
 * credentials it contains as plain text
 *
 * @param array<string, mixed> $config
 * @throws \Deployer\Exception\RunException
 * @throws \Deployer\Exception\Exception
 * @throws \Deployer\Exception\TimeoutException
 */
function uploadIndexConfig(array $config, string $remoteTarget): void
{
    // unique temp file outside the project, it holds credentials and hosts may be deployed in parallel
    $temporaryFileName = tempnam(sys_get_temp_dir(), 'deployer-index-config');
    try {
        file_put_contents($temporaryFileName, "<?php\n\nreturn " . var_export($config, true) . ";\n");
        upload($temporaryFileName, $remoteTarget);
    } finally {
        unlink($temporaryFileName);
    }
}
