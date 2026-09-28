<?php

namespace MoveElevator\FeatureIndex\Utility;

use MoveElevator\FeatureIndex\Api\JiraApi;
use MoveElevator\FeatureIndex\Model\Entry;
use MoveElevator\FeatureIndex\Service\ConfigReader;

class EntryUtility
{
    protected JiraApi $jiraApi;
    protected ConfigReader $configReader;
    protected array $config;
    // subdomain-mode instance names are lowercased, so category/issue detection below must
    // match case-insensitively there; computed once instead of per preg_match() call
    protected string $regexFlags;

    public function __construct()
    {
        $this->configReader = new ConfigReader();
        $this->config = $this->configReader->initConfig();
        $this->jiraApi = new JiraApi($this->config['jira']['api'], $this->config['jira']['auth']);
        $this->regexFlags = $this->isSubdomainMode() ? 'i' : '';
    }

    /**
     * @param string $name
     * @param string $basePath
     * @return \MoveElevator\FeatureIndex\Model\Entry
     */
    public function generateEntry(string $name, string $basePath): Entry
    {
        $entry = new Entry($name);

        $entry->setLastUpdated(date('d.m.Y', $this->getLastDeployTimestamp($basePath . '/' . $name)));
        $entry->setCategory($this->getEntryCategory($name));
        $entry->setTag($this->getEntryTag($name));
        $entry->setIssue($this->getEntryIssue($name));

        $this->jiraApi->checkIssue($entry);

        return $entry;
    }

    /**
     * @param array $array
     * @return mixed
     */
    public function sortDirectoryEntries(array $array): array
    {
        // alphabetic order
        asort($array);
        // custom order
        usort($array, function ($a, $b) {
            $order = ['main', 'master', 'stage', 'test', 'release'];
            $pos_a = $this->searchArrayLike($a->getName(), $order);
            $pos_b = $this->searchArrayLike($b->getName(), $order);
            return $pos_a - $pos_b;
        });
        return $array;
    }

    /**
     * @param \MoveElevator\FeatureIndex\Model\Entry $entry
     * @return string
     */
    public function getIssueLink(Entry $entry): string
    {

        $configReader = new ConfigReader();
        $config = $configReader->initConfig();

        return $entry->getIssue() ? $config['jira']['browse'] . $entry->getIssue() : '';
    }

    /**
     * Deployer appends one JSON line per release to .dep/releases_log, its last created_at is the
     * last deployment. Falls back to the directory mtime for instances without a release yet.
     */
    private function getLastDeployTimestamp(string $instancePath): int
    {
        $releasesLog = $instancePath . '/.dep/releases_log';
        $lines = is_readable($releasesLog) ? file($releasesLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : false;
        $lastRelease = $lines ? json_decode((string)end($lines), true) : null;
        $timestamp = is_array($lastRelease) ? strtotime((string)($lastRelease['created_at'] ?? '')) : false;

        return $timestamp !== false ? $timestamp : filemtime($instancePath);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getEntryCategory(string $name): string
    {
        if (preg_match('/(release)-(\d+\.\d+\.\d+)/' . $this->regexFlags, $name)) return 'release';
        if (preg_match('/([A-Z]+)-(\d+)/' . $this->regexFlags, $name)) return 'feature';
        return $name;
    }

    /**
     * @param string $name
     * @return string
     */
    private function getEntryTag(string $name): string
    {
        if (preg_match('/(release)-(\d+\.\d+\.\d+)/' . $this->regexFlags, $name, $version)) return 'v' . $version[2];
        return '';
    }

    /**
     * @param string $name
     * @return string
     */
    private function getEntryIssue(string $name): string
    {
        if (preg_match('/([A-Z]+)-(\d+)/' . $this->regexFlags, $name, $issue)) {
            // subdomain-mode instance names are lowercased, restore the Jira issue key case
            return $this->isSubdomainMode() ? strtoupper($issue[0]) : $issue[0];
        }
        return '';
    }

    /**
     * Whether instance names are hostname-safe (lowercased) rather than the original branch
     * casing, i.e. feature_url_pattern is configured (see IOService::getEntryAppPath()).
     */
    private function isSubdomainMode(): bool
    {
        return !empty($this->config['featureUrlPattern'] ?? '');
    }

    /**
     * @param string $haystack
     * @param array $array
     * @return int
     */
    private function searchArrayLike(string $haystack, array $array): int
    {
        foreach ($array as $key => $needle) {
            if (strpos($haystack, $needle) === 0) {
                return $key;
            }
        }
        return 999;
    }

}
