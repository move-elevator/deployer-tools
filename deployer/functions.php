<?php

namespace Deployer;

use Deployer\Exception\RunException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @param $message
 * @return void
 */
function debug($message): void
{
    if (isVerbose() && !empty($message)) {
        writeln("<fg=yellow;options=bold>debug</> " . parse($message));
    }
}

/**
 * @return bool
 */
function isVerbose(): bool
{
    return in_array(output()->getVerbosity(), [OutputInterface::VERBOSITY_VERBOSE, OutputInterface::VERBOSITY_VERY_VERBOSE, OutputInterface::VERBOSITY_DEBUG], true);
}

/**
 * @return void
 */
function checkVerbosity(): void
{
    if (!empty(getenv('DEPLOYER_CONFIG_VERBOSE'))) {
        output()->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
    }
}

/**
 * Extend the deployer configuration with available environment variables (starting with "DEPLOYER_CONFIG_"):
 *
 * ENVIRONMENT_VARIABLE => deployer_configuration
 * e.g.
 * DEPLOYER_CONFIG_DATABASE_PASSWORD => database_password
 *
 * @return void
 * @throws \Deployer\Exception\Exception
 */
function prepareDeployerConfiguration(): void {
    $environmentVariables = getenv();

    debug('Extending deployer configuration with environment variables');
    foreach ($environmentVariables as $key => $value) {
        if (str_starts_with($key, 'DEPLOYER_CONFIG_')) {
            $configName = strtolower(str_replace('DEPLOYER_CONFIG_', '', $key));

            if (has($configName)) {
                debug("Attention: overwriting existing deployer configuration '$configName' with environment variable '$key' ");
            }
            set($configName, $value);
        }
    }
}

/**
 * Check if a subcommand is available, e.g. "php bin/console", "ckeditor:install"
 *
 * @param string $command
 * @param string $subcommand
 * @return bool
 * @throws \Deployer\Exception\Exception
 * @throws \Deployer\Exception\RunException
 * @throws \Deployer\Exception\TimeoutException
 */
function commandSupportSubcommand(string $command, string $subcommand): bool
{
    $check = run("( $command list 2>&1 || $command --list) | grep -- $subcommand || true");
    if (empty($check)) {
        return false;
    }
    return str_contains($check, $subcommand);
}

/**
 * Check if command exists locally
 *
 * @throws RunException
 */
function commandExistLocally(string $command): bool
{
    return testLocally("hash $command 2>/dev/null");
}

/**
 * Runs a remote command with the possibility to overwrite the default command options
 */
function runExtended(string $command, ?array $options = [], ?int $timeout = null, ?int $idle_timeout = null, ?string $secret = null, ?array $env = null, ?bool $real_time_output = null, ?bool $no_throw = null): string
{
    return run(
        $command,
        $options,
            $timeout ?? (int)get('run_timeout'),
            $idle_timeout ?? (int)get('run_idle_timeout'),
            $secret ?? (string)get('run_secret'),
            $env ?? (array)get('run_env'),
            $real_time_output ?? (bool)get('run_real_time_output'),
            $no_throw ?? (bool)get('run_no_throw')
    );
}

function getRecentDatabaseCacheDumpPath(): string
{
    return get('dev_tr_db_dump_dir') . '/' . getRecentDatabaseCacheDumpFilename() . '.sql';
}

function getRecentDatabaseCacheDumpFilename(): string
{
    return date('Y-m-d');
}

function recentDatabaseCacheDumpExists(): bool
{
    return test('[ -f ' . getRecentDatabaseCacheDumpPath() . ' ]');
}

function cleanUpDatabaseCacheDumps(int $days = 7): void
{
    $dbDumpDir = get('dev_tr_db_dump_dir');
    run("mkdir -p $dbDumpDir");

    // cleanup beforehand: delete all dump files with the above naming scheme older than 7 days
    run("find $dbDumpDir -name '*.sql' -mtime +$days -delete");
}

/**
 * Applies standardized file and directory permissions for a deployment.
 *
 * Handles:
 * - var/cache creation and writable permissions
 * - SGID-aware directory permissions for group inheritance
 * - Restrictive file permissions
 * - Shared directory baseline and writable permissions
 *
 * Config keys (with defaults):
 * - writable_chmod_mode_files: '644'
 * - writable_chmod_mode_dirs: '2755'
 * - writable_chmod_mode_writable_dirs: '2775'
 */
function applyDeployPermissions(): void
{
    $modeFiles = has('writable_chmod_mode_files') ? get('writable_chmod_mode_files') : '644';
    $modeDirs = has('writable_chmod_mode_dirs') ? get('writable_chmod_mode_dirs') : '2755';
    $modeWritableDirs = has('writable_chmod_mode_writable_dirs') ? get('writable_chmod_mode_writable_dirs') : '2775';

    // Ensure var/cache exists before any cache operation
    runExtended("cd {{ release_path }} && mkdir -p var/cache");

    // Set SGID + writable permissions on var directories
    runExtended("cd {{ release_path }} && chmod $modeWritableDirs var var/cache");

    // Set proper directory permissions with SGID for group inheritance, skip var
    runExtended("cd {{ release_path }} && find . -path \"./var\" -prune -o -type d -exec chmod $modeDirs {} +");

    // Set restrictive file permissions
    runExtended("cd {{ release_path }} && find . -type f -exec chmod $modeFiles {} +");

    // var/cache needs recursive writable permissions for webserver access
    runExtended("cd {{ release_path }} && chmod -R $modeWritableDirs var/cache");

    // Fix shared directory permissions (baseline)
    runExtended("cd {{ deploy_path }}/shared && find . -type d -exec chmod $modeDirs {} +");
    runExtended("cd {{ deploy_path }}/shared && find . -type f -exec chmod $modeFiles {} +");

    // Shared writable directories need broader permissions (recursive for subdirectories)
    foreach (get('shared_dirs') as $dir) {
        if (test("[ -d {{ deploy_path }}/shared/$dir ]")) {
            runExtended("find {{ deploy_path }}/shared/$dir -type d -exec chmod $modeWritableDirs {} +");
        }
    }
}
