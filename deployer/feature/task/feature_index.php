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
        'backendPath' => has('feature_index_backend_path') ? get('feature_index_backend_path') : '',
        'staleDays' => (int)get('feature_index_stale_days'),
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
    // Apache only, nginx needs the equivalent rules in the server block (see docs/WEBSERVER.md)
    upload(__DIR__ . '/../dist/index.htaccess' ,$featureDirectoryPath . '.htaccess');
    runExtended("cd " . get('deploy_path') . " && ln -sf " . $featureDirectoryPath . "index.php index.php");
    uploadIndexConfig($config, $featureDirectoryPath . 'index.config.php');
    // ToDo: fix permissions
    // index/var/ is uploaded by the deploy user, but the web server user writes the Jira cache below it
    runExtended("cd {{deploy_path}} && chmod 644 {{feature_directory_path}}index.* {{feature_directory_path}}.htaccess && chmod 775 {{feature_directory_path}}index/ {{feature_directory_path}}index/var && chmod -R 755 {{feature_directory_path}}index/assets && chmod -R 755 {{feature_directory_path}}index/src && chmod 755 {{feature_directory_path}}index/autoload.php");
    restrictIndexConfigPermissions($featureDirectoryPath . 'index.config.php', $config['jira']['auth'] !== '');

}

/**
 * The config holds the Jira credentials, so hide it from other local users: owned by the deploy
 * user and the web server group with mode 640, which works whether PHP runs as the deploy user or
 * as the web server user. Without a matching group the file stays 644, a narrower mode would lock
 * out the web server.
 *
 * @throws \Deployer\Exception\RunException
 * @throws \Deployer\Exception\Exception
 * @throws \Deployer\Exception\TimeoutException
 */
function restrictIndexConfigPermissions(string $configPath, bool $containsCredentials): void
{
    $group = has('requirements_user_group') ? get('requirements_user_group') : '';
    if ($group !== '' && test('chgrp ' . escapeshellarg($group) . ' ' . escapeshellarg($configPath))) {
        runExtended('chmod 640 ' . escapeshellarg($configPath));
        return;
    }

    if ($containsCredentials) {
        warning("$configPath contains the Jira credentials and stays readable for all local users, set requirements_user_group to the web server group the deploy user belongs to");
    }
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
    if ($temporaryFileName === false) {
        throw error('Could not create a temporary file for the index config');
    }
    try {
        // an incomplete file would replace the working config on the host
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        if (file_put_contents($temporaryFileName, $content) !== strlen($content)) {
            throw error("Could not write the index config to $temporaryFileName");
        }
        upload($temporaryFileName, $remoteTarget);
    } finally {
        unlink($temporaryFileName);
    }
}
