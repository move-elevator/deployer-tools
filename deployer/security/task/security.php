<?php

namespace Deployer;

use Deployer\Exception\GracefulShutdownException;
use Deployer\Exception\RunException;
use Symfony\Component\Console\Helper\Table;

const COMMAND_SYMFONY = 'symfony security:check --format=json --dir={{security_composer_path}}';
const COMMAND_COMPOSER = 'composer audit --format=json --working-dir={{security_composer_path}}';
const SECURITY_CONTEXT_COMPOSER = 'composer';
const SECURITY_CONTEXT_NPM = 'npm';

task('security:check', [
    'security:check:composer',
    'security:check:npm',
]);

task('security:check:composer', function () {
    checkComposerConsoleTools();
    debug('Checking for security vulnerabilities');
    try {
        $output = runLocally('{{security_composer_command}}');
    } catch (RunException $exception) {
        $output = ($exception->getOutput());
    }
    $vulnerabilities = json_decode($output, true);
    $formattedIssues = formatComposerIssues($vulnerabilities);

    if (empty($formattedIssues)) {
        writeln('<fg=green>[OK]</> No security vulnerabilities found.');
        return;
    }
    writeln('<fg=yellow>[WARN]</> ' . count($formattedIssues) . ' security vulnerabilities found.');
    printIssueTable($formattedIssues);
    checkIssueCache($formattedIssues, SECURITY_CONTEXT_COMPOSER);
    if (input()->getOption('notify')) {
        notifyIssues($formattedIssues);
    }
})
    ->once()
    ->desc('Checking for composer security vulnerabilities');

task('security:check:npm', function () {
    checkNpmConsoleTools();
    debug('Checking for security vulnerabilities');
    try {
        $output = runLocally('npm audit --json --only=prod --prefix {{security_npm_path}}');
    } catch (RunException $exception) {
        $output = ($exception->getOutput());
        throw new GracefulShutdownException($output);
    }
    $vulnerabilities = json_decode($output, true);
    $formattedIssues = formatNpmIssues($vulnerabilities);

    if (empty($formattedIssues)) {
        writeln('<fg=green>[OK]</> No security vulnerabilities found.');
        return;
    }
    writeln('<fg=yellow>[WARN]</> ' . count($formattedIssues) . ' security vulnerabilities found.');
    printIssueTable($formattedIssues);
    checkIssueCache($formattedIssues, SECURITY_CONTEXT_NPM);
    if (input()->getOption('notify')) {
        notifyIssues($formattedIssues, SECURITY_CONTEXT_NPM);
    }
})
    ->once()
    ->desc('Checking for npm security vulnerabilities');


/**
 * The function checks for the availability of Composer or Symfony console tools locally to check for
 * security issues.
 */
function checkComposerConsoleTools(): void
{
    $composerVersion = runLocally('composer --version');
    // e.g. "Composer version 2.5.5 2023-03-21 11:50:05"
    preg_match('/(\d+\.\d+\.\d+)/', $composerVersion, $matches);
    $version = $matches[1];

    if (version_compare($version, '2.4', '<')) {
        if (commandExistLocally('symfony')) {
            set('security_composer_command', COMMAND_SYMFONY);
        } else {
            throw new GracefulShutdownException('Neither composer >= 2.4 nor symfony console is available locally to check for security issues.');
        }
    } else {
        set('security_composer_command', COMMAND_COMPOSER);
    }
}


/**
 * The function checks if npm is installed locally and throws an exception if it is missing.
 */
function checkNpmConsoleTools(): void
{
    if (!commandExistLocally('npm')) {
        throw new GracefulShutdownException('npm is missing to check for security issues.');
    }
}

/**
 * The function formats an array of Composer issues into a specific format.
 *
 * @param ?array $issues An array of issues returned by a Composer security checker tool. The format of
 * the array may differ depending on the specific tool being used.
 *
 * @return array an array of formatted issues. The format of each issue includes the CVE, package name,
 * affected version, title, and link. The issues are obtained from the input array, which is an array
 * of issues returned by a Composer security check. The format of the input array depends on the value
 * of the 'security_composer_command' configuration parameter. If the value is 'symfony
 */
function formatComposerIssues(?array $issues): array
{
    $formattedIssues = [];
    if (get('security_composer_command') === COMMAND_SYMFONY) {
        foreach ($issues as $key => $issue) {

            if (!array_key_exists('advisories', $issues)) {
                return $formattedIssues;
            }

            foreach ($issue['advisories'] as $advisory) {
                $formattedIssue = [
                    'cve' => $advisory['cve'],
                    'package' => $key,
                    'version' => $advisory['version'],
                    'title' => $advisory['title'],
                    'link' => $advisory['link'],
                ];
                $formattedIssues[$advisory['cve']] = $formattedIssue;
            }
        }
    } else {
        if (!array_key_exists('advisories', $issues)) {
            return $formattedIssues;
        }
        foreach ($issues['advisories'] as $key => $advisories) {
            foreach ($advisories as $advisory) {
                $formattedIssue = [
                    'cve' => $advisory['cve'],
                    'package' => $key,
                    'version' => $advisory['affectedVersions'],
                    'title' => $advisory['title'],
                    'link' => $advisory['link'],
                ];
                $formattedIssues[$advisory['cve']] = $formattedIssue;
            }
        }
    }
    return $formattedIssues;
}

/**
 * The function formats an array of npm issues into a more readable format.
 *
 * @param array|null $issues The parameter `` is an array that contains information about
 * vulnerabilities in npm packages. Specifically, it has a key called `vulnerabilities` which contains
 * an array of objects, each representing a vulnerability.
 *
 * @return array an array of formatted npm issues.
 */
function formatNpmIssues(?array $issues): array
{
    $formattedIssues = [];

    if (!array_key_exists('vulnerabilities', $issues)) {
        return $formattedIssues;
    }

    foreach ($issues['vulnerabilities'] as $key => $issue) {
        foreach ($issue['via'] as $advisory) {
            if (!is_array($advisory)) continue;
            $formattedIssue = [
                'cve' => basename($advisory['url']),
                'severity' => coloredOutput($advisory['severity'], [
                    'critical' => 'red',
                    'high' => 'red',
                    'moderate' => 'yellow',
                    'low' => 'white',
                ]),
                'package' => $advisory['name'],
                'version' => $advisory['range'],
                'title' => $advisory['title'],
                'link' => $advisory['url'],
            ];
            $formattedIssues[basename($advisory['url'])] = $formattedIssue;
        }
    }
    return $formattedIssues;
}

/**
 * The function prints a table of security vulnerabilities using an array of issues.
 *
 * @param array $issues The parameter `` is an array of arrays, where each inner array represents
 * an issue and contains key-value pairs representing the details of that issue. The keys in each inner
 * array are the column headers for the table that will be printed.
 */
function printIssueTable(array $issues): void
{
    $keys = array_keys(reset($issues));
    (new Table(output()))
        ->setHeaderTitle("Security vulnerabilities")
        ->setHeaders($keys)
        ->setRows($issues)
        ->render();
}

/**
 * The function takes a string and an array of matches with corresponding colors, and returns the
 * string with the matched substrings colored.
 *
 * @param string $string The input string that needs to be formatted with colors.
 * @param array $matches The matches parameter is an array that contains the values to be matched and
 * their corresponding colors.
 * @param ?string $match The `` parameter is a string that represents the specific match that
 * should be highlighted with color in the output string. If it is null, then the entire input string
 * will be used as the match.
 *
 * @return string a string with the matched substring(s) colored according to the corresponding color
 * specified in the `` array.
 */
function coloredOutput(string $string, array $matches, ?string $match = null): string
{
    $match = is_null($match) ? $string : $match;
    foreach ($matches as $value => $color) {
        if ($value === $match) {
            $string = preg_replace("/($match)/i", "<fg=$color>$1</>", $string);
        }
    }
    return $string;
}

/**
 * The function checks for cached security issues and removes them from the list of issues if found.
 *
 * @param array $issues An array of issues to be checked for caching.
 * @param string$ type The type parameter is a string that specifies the type of security check being
 * performed. It is used to generate a unique cache file name for each type of security check.
 *
 * @return void Nothing is being returned, as the function has a return type of `void`.
 */
function checkIssueCache(array &$issues, string $type): void
{
    if (!get('security_use_cache')) return;
    $issuesCopy = $issues;
    $cacheFolder = __DIR__ . '/.cache/';
    $cacheFile = "$type.security_check.cache.json";
    $cacheFilePath = $cacheFolder . $cacheFile;
    if (!is_dir($cacheFolder)) mkdir($cacheFolder, 0755, true);
    if (file_exists($cacheFilePath)) {
        $cacheFileContent = file_get_contents($cacheFilePath);
        $cacheFileContent = $cacheFileContent ? json_decode($cacheFileContent, true) : [];
        $cacheFileKeys = array_keys($cacheFileContent);
        foreach ($cacheFileKeys as $cacheFileKey) {
            if (isset($issues[$cacheFileKey])) {
                unset($issues[$cacheFileKey]);
            }
        }
    }
    file_put_contents($cacheFilePath, json_encode($issuesCopy));
}

/**
 * The function notifies about security issues in a project by generating a message with details about
 * the issues.
 *
 * @param array $issues An array of security issues that have been detected in the project.
 * @param string $type The type parameter is a string that specifies the context of the security issue.
 * It has a default value of "SECURITY_CONTEXT_COMPOSER" and can be overridden with a different value.
 *
 * @return void nothing (void) if the `` array is empty.
 */
function notifyIssues(array $issues, string $type = SECURITY_CONTEXT_COMPOSER): void
{
    if (empty($issues)) return;
    $message = 'Im Projekt <strong>' . get('project') . '</strong> wurden die folgende(n) ' . count($issues) . ' <em>' . $type . '</em> Sicherheitslücke(n) entdeckt:';
    foreach ($issues as $cve => $issue) {
        $message .= sprintf(
            '<br/><br/><strong style="color:%s">%s</strong> %s<br/><em>%s</em> – <a href="%s">%s</a>',
            get('security_notification_color'),
            $issue['package'],
            $issue['version'],
            $issue['title'],
            $issue['link'],
            $cve,
        );
    }
    sendMessage($message, get('security_notification_color'));
}
